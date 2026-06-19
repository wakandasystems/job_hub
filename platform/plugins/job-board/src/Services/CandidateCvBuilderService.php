<?php

namespace Botble\JobBoard\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Botble\JobBoard\Models\CandidateAlertCvBuilderAiLog;
use Botble\JobBoard\Models\CandidateAlertCvBuilderSession;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

class CandidateCvBuilderService
{
    private const MODEL = 'gpt-4o-mini';

    private const MODEL_PRICING_PER_MILLION = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4o' => [2.50, 10.00],
    ];

    public static function questions(): array
    {
        return [
            'What is your full name as it should appear on the CV?',
            'What phone number, email address, and town/city should appear on the CV?',
            'What job title or type of work are you looking for?',
            'Write a short personal profile: what are you good at, and what kind of worker are you?',
            'List your highest education first. Include school/college, qualification, field of study, and years.',
            'List your work experience. For each job include company, job title, dates, and what you did.',
            'List internships, attachments, volunteer work, or projects if you have any.',
            'List your strongest skills, tools, software, machines, or languages you can use.',
            'List certificates, licences, trainings, or awards you have.',
            'Which languages do you speak, and what level?',
            'Who can be listed as references? Include name, role/company, phone, and email if available. If not, say "Available on request".',
            'Is there anything else important for your CV, such as achievements, leadership, availability, or preferred location?',
        ];
    }

    public function buildFromTranscript(CandidateAlertCvBuilderSession $session, string $transcript, ?int $adminId = null): array
    {
        $apiKey = (string) (setting('openai_api_key') ?: config('services.openai.key') ?: env('OPENAI_API_KEY'));

        if ($apiKey === '') {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $requestPayload = $this->buildOpenAiPayload($session, $transcript);
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        try {
            $response = Http::timeout(90)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($endpoint, $requestPayload);
        } catch (Throwable $exception) {
            $this->logOpenAiRequest($session, $adminId, $endpoint, $requestPayload, null, 'failed', $exception->getMessage());
            throw $exception;
        }

        $log = $this->logOpenAiRequest(
            $session,
            $adminId,
            $endpoint,
            $requestPayload,
            $response,
            $response->successful() ? 'success' : 'failed',
            $response->successful() ? null : Str::limit($response->body(), 1000, '')
        );

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI CV builder failed: HTTP ' . $response->status());
        }

        $cv = $this->decodeCvJson($response);

        if (! is_array($cv)) {
            throw new \RuntimeException('OpenAI returned an invalid CV JSON response.');
        }

        $cv = $this->sanitizeCv($cv, $session);
        $paths = $this->storeDocuments($session, $cv);

        $session->forceFill([
            'conversation_text' => $transcript,
            'structured_cv' => $cv,
            'docx_path' => $paths['docx_path'],
            'pdf_path' => $paths['pdf_path'],
            'status' => 'completed',
            'error_message' => null,
            'completed_at' => now(),
        ])->save();

        return [
            'session' => $session->fresh(),
            'cv' => $cv,
            'ai_log' => $log->fresh(),
        ];
    }

    private function buildOpenAiPayload(CandidateAlertCvBuilderSession $session, string $transcript): array
    {
        $systemPrompt = <<<'PROMPT'
You are a professional CV writer for African job seekers.

Turn a WhatsApp interview transcript into a clean, truthful CV.

Rules:
- Return ONLY valid JSON.
- Do not invent employers, schools, qualifications, dates, references, phone numbers, or emails.
- If information is missing, use an empty string or empty array.
- Rewrite informal answers into professional CV language.
- Keep responsibilities concise and action-oriented.
- Use British English spelling.
- Put "Available on request" for references only if the candidate said references are not available.

JSON shape:
{
  "full_name": "",
  "headline": "",
  "phone": "",
  "email": "",
  "location": "",
  "summary": "",
  "skills": [],
  "experience": [
    {
      "job_title": "",
      "company": "",
      "location": "",
      "start_date": "",
      "end_date": "",
      "responsibilities": []
    }
  ],
  "education": [
    {
      "institution": "",
      "qualification": "",
      "field": "",
      "start_year": "",
      "end_year": ""
    }
  ],
  "projects": [
    {
      "name": "",
      "description": ""
    }
  ],
  "certifications": [],
  "languages": [],
  "references": [
    {
      "name": "",
      "role": "",
      "company": "",
      "phone": "",
      "email": ""
    }
  ],
  "notes_for_admin": []
}
PROMPT;

        $questions = collect($session->questions ?: self::questions())
            ->map(fn ($question, $index) => ($index + 1) . '. ' . $question)
            ->implode("\n");

        $userPrompt = <<<PROMPT
Candidate name from admin record: {$session->candidate_name}
Candidate WhatsApp number from admin record: {$session->whatsapp_number}

Questions that were sent:
{$questions}

Pasted WhatsApp transcript:
{$transcript}
PROMPT;

        return [
            'model' => self::MODEL,
            'temperature' => 0.2,
            'max_tokens' => 2200,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];
    }

    private function logOpenAiRequest(
        CandidateAlertCvBuilderSession $session,
        ?int $adminId,
        string $endpoint,
        array $requestPayload,
        ?Response $response,
        string $status,
        ?string $errorMessage
    ): CandidateAlertCvBuilderAiLog {
        $promptTokens = (int) ($response?->json('usage.prompt_tokens', 0) ?? 0);
        $completionTokens = (int) ($response?->json('usage.completion_tokens', 0) ?? 0);
        $totalTokens = (int) ($response?->json('usage.total_tokens', $promptTokens + $completionTokens) ?? 0);

        return CandidateAlertCvBuilderAiLog::query()->create([
            'session_id' => $session->getKey(),
            'admin_id' => $adminId,
            'ai_provider' => 'openai',
            'ai_model' => self::MODEL,
            'endpoint' => $endpoint,
            'status' => $status,
            'request_payload' => $requestPayload,
            'response_payload' => $response?->json(),
            'response_headers' => $response ? $this->sanitizeHeaders($response->headers()) : null,
            'prompt_tokens' => $promptTokens ?: null,
            'completion_tokens' => $completionTokens ?: null,
            'total_tokens' => $totalTokens ?: null,
            'estimated_cost_usd' => $this->estimateCost(self::MODEL, $promptTokens, $completionTokens),
            'processing_ms' => (int) ($response?->header('openai-processing-ms') ?: 0) ?: null,
            'error_message' => $errorMessage,
        ]);
    }

    private function decodeCvJson(Response $response): ?array
    {
        $content = trim((string) $response->json('choices.0.message.content', ''));

        if ($content === '') {
            return null;
        }

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        }

        $decoded = json_decode(trim($content), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function sanitizeCv(array $cv, CandidateAlertCvBuilderSession $session): array
    {
        $cv['full_name'] = Str::limit(trim((string) ($cv['full_name'] ?? $session->candidate_name)), 150, '');
        $cv['headline'] = Str::limit(trim((string) ($cv['headline'] ?? '')), 150, '');
        $cv['phone'] = Str::limit(trim((string) ($cv['phone'] ?? $session->whatsapp_number)), 60, '');
        $cv['email'] = Str::limit(trim((string) ($cv['email'] ?? '')), 150, '');
        $cv['location'] = Str::limit(trim((string) ($cv['location'] ?? '')), 150, '');
        $cv['summary'] = Str::limit(trim((string) ($cv['summary'] ?? '')), 1200, '');

        foreach (['skills', 'certifications', 'languages', 'notes_for_admin'] as $key) {
            $cv[$key] = collect($cv[$key] ?? [])
                ->map(fn ($value) => Str::limit(trim((string) $value), 180, ''))
                ->filter()
                ->values()
                ->all();
        }

        foreach (['experience', 'education', 'projects', 'references'] as $key) {
            $cv[$key] = collect($cv[$key] ?? [])
                ->filter(fn ($row) => is_array($row))
                ->values()
                ->all();
        }

        return $cv;
    }

    private function storeDocuments(CandidateAlertCvBuilderSession $session, array $cv): array
    {
        $baseDir = 'candidate-cv-builder/session-' . $session->getKey();
        $slug = Str::slug((string) ($cv['full_name'] ?: $session->candidate_name ?: 'candidate-cv')) ?: 'candidate-cv';
        $docxPath = "{$baseDir}/{$slug}.docx";
        $pdfPath = "{$baseDir}/{$slug}.pdf";

        Storage::disk('local')->put($docxPath, $this->renderDocx($cv));

        $pdf = Pdf::loadView('plugins/job-board::candidate-alerts.cv-builder-pdf', [
            'cv' => $cv,
            'generatedAt' => now()->format('d M Y'),
        ])->setPaper('a4');

        Storage::disk('local')->put($pdfPath, $pdf->output());

        return compact('docxPath', 'pdfPath') + [
            'docx_path' => $docxPath,
            'pdf_path' => $pdfPath,
        ];
    }

    private function renderDocx(array $cv): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP ZipArchive extension is required to generate DOCX files.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'cv-docx-');
        $zip = new ZipArchive();

        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create DOCX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->docxContentTypes());
        $zip->addFromString('_rels/.rels', $this->docxRels());
        $zip->addFromString('word/_rels/document.xml.rels', $this->docxDocumentRels());
        $zip->addFromString('word/styles.xml', $this->docxStyles());
        $zip->addFromString('word/document.xml', $this->docxDocument($cv));
        $zip->close();

        $contents = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $contents;
    }

    private function docxDocument(array $cv): string
    {
        $body = [];
        $body[] = $this->docxParagraph((string) ($cv['full_name'] ?? 'Candidate CV'), 'Title');
        $body[] = $this->docxParagraph(implode(' | ', array_filter([
            $cv['headline'] ?? '',
            $cv['phone'] ?? '',
            $cv['email'] ?? '',
            $cv['location'] ?? '',
        ])), 'Subtitle');

        $this->appendSection($body, 'Professional Profile', array_filter([(string) ($cv['summary'] ?? '')]));
        $this->appendSection($body, 'Key Skills', $cv['skills'] ?? [], true);

        $experience = [];
        foreach ($cv['experience'] ?? [] as $row) {
            $heading = implode(' - ', array_filter([
                $row['job_title'] ?? '',
                $row['company'] ?? '',
            ]));
            $dates = implode(' to ', array_filter([$row['start_date'] ?? '', $row['end_date'] ?? '']));
            $experience[] = trim($heading . ($dates ? " ({$dates})" : ''));
            foreach ((array) ($row['responsibilities'] ?? []) as $line) {
                $experience[] = '• ' . $line;
            }
        }
        $this->appendSection($body, 'Work Experience', $experience);

        $education = [];
        foreach ($cv['education'] ?? [] as $row) {
            $education[] = implode(' - ', array_filter([
                $row['qualification'] ?? '',
                $row['field'] ?? '',
                $row['institution'] ?? '',
                trim(implode(' - ', array_filter([$row['start_year'] ?? '', $row['end_year'] ?? '']))),
            ]));
        }
        $this->appendSection($body, 'Education', $education);

        $projects = [];
        foreach ($cv['projects'] ?? [] as $row) {
            $projects[] = trim(($row['name'] ?? '') . (($row['description'] ?? '') ? ': ' . $row['description'] : ''));
        }
        $this->appendSection($body, 'Projects and Volunteer Work', $projects);
        $this->appendSection($body, 'Certifications and Training', $cv['certifications'] ?? [], true);
        $this->appendSection($body, 'Languages', $cv['languages'] ?? [], true);

        $references = [];
        foreach ($cv['references'] ?? [] as $row) {
            $references[] = implode(' | ', array_filter([
                $row['name'] ?? '',
                $row['role'] ?? '',
                $row['company'] ?? '',
                $row['phone'] ?? '',
                $row['email'] ?? '',
            ]));
        }
        $this->appendSection($body, 'References', $references);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" mc:Ignorable="w14 wp14">'
            . '<w:body>' . implode('', $body)
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1008" w:right="1008" w:bottom="1008" w:left="1008" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr>'
            . '</w:body></w:document>';
    }

    private function appendSection(array &$body, string $title, array $lines, bool $bullets = false): void
    {
        $lines = array_values(array_filter(array_map(fn ($line) => trim((string) $line), $lines)));

        if ($lines === []) {
            return;
        }

        $body[] = $this->docxParagraph($title, 'Heading1');

        foreach ($lines as $line) {
            $body[] = $this->docxParagraph(($bullets && ! str_starts_with($line, '•') ? '• ' : '') . $line);
        }
    }

    private function docxParagraph(string $text, ?string $style = null): string
    {
        $styleXml = $style ? '<w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr>' : '';

        return '<w:p>' . $styleXml . '<w:r><w:t xml:space="preserve">' . $this->xml($text) . '</w:t></w:r></w:p>';
    }

    private function docxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>';
    }

    private function docxRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
    }

    private function docxDocumentRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';
    }

    private function docxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:sz w:val="34"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:rPr><w:color w:val="666666"/><w:sz w:val="20"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:pPr><w:spacing w:before="220" w:after="80"/></w:pPr><w:rPr><w:b/><w:sz w:val="24"/></w:rPr></w:style></w:styles>';
    }

    private function sanitizeHeaders(array $headers): array
    {
        return collect($headers)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), ['authorization', 'set-cookie'], true))
            ->all();
    }

    private function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        [$inputRate, $outputRate] = self::MODEL_PRICING_PER_MILLION[$model] ?? self::MODEL_PRICING_PER_MILLION['gpt-4o-mini'];

        return round((($promptTokens * $inputRate) + ($completionTokens * $outputRate)) / 1000000, 6);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
