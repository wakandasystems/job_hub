<?php

namespace Botble\JobBoard\Services;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\JobImageGeneratorService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SocialPublisherService
{
    public function publishJob(Job $job): array
    {
        // Silently skip jobs that are not fully approved — no notification sent.
        if (! $job->moderation_status || (string) $job->moderation_status !== \Botble\JobBoard\Enums\ModerationStatusEnum::APPROVED) {
            return [];
        }

        $results = [];

        $automations = SocialAutomation::query()
            ->where('is_active', true)
            ->get();

        foreach ($automations as $automation) {
            try {
                $posted = match ($automation->platform) {
                    'facebook' => $this->postToFacebook($automation, $job),
                    'linkedin' => $this->postToLinkedIn($automation, $job),
                    'whatsapp' => $this->postToWhatsApp($automation, $job),
                    'telegram' => $this->postToTelegram($automation, $job),
                    default    => false,
                };

                $results[] = [
                    'automation' => $automation->name,
                    'platform'   => $automation->platform,
                    'success'    => $posted,
                    'error'      => null,
                ];
            } catch (Throwable $e) {
                $results[] = [
                    'automation' => $automation->name,
                    'platform'   => $automation->platform,
                    'success'    => false,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function buildJobMessage(Job $job): string
    {
        $excerpt = Str::limit(strip_tags((string) $job->description), 280);
        $url     = route('public.job', $job->slugable?->key ?? $job->id);
        $company = $job->company?->name ?? '';
        $location = $job->address ?? 'Zambia';

        $lines = ["🔔 New Job: {$job->name}"];

        if ($company) {
            $lines[] = "🏢 {$company}";
        }

        $lines[] = "📍 {$location}";

        if ($excerpt) {
            $lines[] = '';
            $lines[] = $excerpt;
        }

        $lines[] = '';
        $lines[] = "🔗 Apply: {$url}";

        return implode("\n", $lines);
    }

    public function buildAiImagePrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $country  = trim((string) ($job->country?->name ?? 'Zambia'));

        // Company logo URL — include only when the company has an actual uploaded logo
        $companyLogoUrl = null;
        if ($job->company && ! empty($job->company->logo)) {
            try {
                $companyLogoUrl = RvMedia::getImageUrl($job->company->logo);
            } catch (Throwable) {}
        }

        // Map country name to its flag colors for a subtle design accent
        $flagColors = $this->getFlagColors($country);

        // Build a details line to embed in the image
        $details = [];
        if ($company) {
            $details[] = "Company: {$company}";
        }
        $details[] = "Location: {$location}";

        try {
            if (! $job->hide_salary && $job->salary_text) {
                $salary = (string) $job->salary_text;
                if (! in_array(strtolower($salary), ['attractive', 'negotiable', 'competitive'])) {
                    $details[] = "Salary: {$salary}";
                }
            }
        } catch (Throwable) {}

        if ($deadline) {
            $details[] = "Deadline: " . $deadline->format('M j, Y');
        }

        $jobTypes = $job->jobTypes->pluck('name')->filter()->implode(' / ');
        if ($jobTypes) {
            $details[] = "Type: {$jobTypes}";
        }

        $detailsText = implode(' | ', $details);

        $prompt  = "Generate a professional job advertisement image for Wakanda Jobs (wakandajobs.com) — an African job platform.";
        $prompt .= " The job being advertised is: {$title}";
        if ($company) {
            $prompt .= " at {$company}";
        }
        $prompt .= ".";

        $prompt .= " Make the image ultra-realistic, professional, and trustworthy — like a Fortune 500 recruitment ad.";

        // Company logo — colours, placement, and branding
        if ($companyLogoUrl) {
            $prompt .= " IMPORTANT BRANDING: The company '{$company}' has an official logo available at this URL: {$companyLogoUrl}";
            $prompt .= " Fetch that logo and do the following: (1) Place it accurately in the top-right corner — do NOT invent or guess the logo, use only the real one from that URL.";
            $prompt .= " (2) Extract the dominant colours from that logo and use them as the primary colour palette for the ENTIRE image — the background, environment, surfaces, props, and any banners or graphic elements should all reflect those brand colours.";
            $prompt .= " (3) Dress the Black African professionals in clothing that incorporates or complements those brand colours, so the people feel part of the company's visual identity.";
            $prompt .= " (4) Any decorative banners, overlays, or graphic shapes in the image should also use the company logo and its colours, making the whole composition feel like a branded, personalised advertisement for '{$company}'.";
        } else {
            if ($company) {
                $prompt .= " There is no company logo available for '{$company}', so render a clean professional text badge or monogram for the company name in the top-right corner.";
            }
            $prompt .= " Use the Wakanda Jobs purple/violet colour palette as the primary design theme.";
        }

        $prompt .= " Feature Black African professionals dressed appropriately for the role in the scene.";
        $prompt .= " Include the Wakanda Jobs logo (attached) in the top-left corner.";
        if ($companyLogoUrl) {
            $prompt .= " The Wakanda Jobs logo should be smaller and secondary to the company branding — this image should feel like a '{$company}' ad first.";
        }

        // Flag accent
        if ($flagColors) {
            $prompt .= " Add a subtle, tasteful country flag accent for {$country}: a thin horizontal band at the very bottom of the image using the flag colors {$flagColors}.";
            $prompt .= " The flag colors should be understated — a quiet nod to the country, not a loud centerpiece.";
        }

        $prompt .= " The image must clearly display the following text overlay: Job Title: {$title}";
        if ($company) {
            $prompt .= " | Company: {$company}";
        }
        $prompt .= " | {$detailsText}.";
        $prompt .= " Add a 'Apply Now at wakandajobs.com' call-to-action at the bottom.";
        $prompt .= " Overall feel: modern, clean, corporate, inspiring confidence.";
        $prompt .= " IMPORTANT — IMAGE DIMENSIONS: Generate this image at exactly 1080 × 1920 pixels, 9:16 portrait aspect ratio, optimised for TikTok, Instagram Stories, Facebook Stories, and WhatsApp Status. Do NOT generate a landscape or square image.";

        return $prompt;
    }

    public function buildStoryboardPrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ''));
        $country  = trim((string) ($job->country?->name ?? ''));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $deadlineStr = $deadline ? $deadline->format('M j, Y') : '';
        $url      = route('public.job', $job->slugable?->key ?? $job->id);

        $salaryLine = '';
        try {
            if (! $job->hide_salary && $job->salary_text) {
                $s = (string) $job->salary_text;
                if (! in_array(strtolower($s), ['attractive', 'negotiable', 'competitive'])) {
                    $salaryLine = "Salary: {$s}";
                }
            }
        } catch (\Throwable) {}

        $excerpt = trim(\Illuminate\Support\Str::limit(strip_tags(mb_convert_encoding((string) ($job->description ?: $job->content), 'UTF-8', 'UTF-8')), 300));

        $companyLine  = $company  ? "Company  : {$company}" : '';
        $locationLine = $location ? "Location : {$location}" : '';
        $salaryBlock  = $salaryLine ? "{$salaryLine}" : '';
        $deadlineBlock = $deadlineStr ? "Deadline : {$deadlineStr}" : '';
        $salaryOverlay = $salaryLine ? "\n  Text overlay: \"{$salaryLine}\"" : '';
        $deadlineOverlay = $deadlineStr ? "\n  Text overlay: \"Apply before {$deadlineStr}\"" : '';
        $flagAccent   = $country ? "  Bottom accent: {$country} flag colour stripe" : '';
        $companyAt    = $company ? " at {$company}" : '';
        $locationOf   = $location ? " | {$location}" : '';

        // Extract up to 2 benefit bullets from description
        $benefits = [];
        if ($excerpt) {
            $sentences = preg_split('/[.•\n]+/', $excerpt);
            foreach ($sentences as $s) {
                $s = trim($s);
                if (strlen($s) > 15 && strlen($s) < 80) {
                    $benefits[] = $s;
                    if (count($benefits) >= 2) break;
                }
            }
        }
        $benefit1 = $benefits[0] ?? "Join a leading {$country} employer";
        $benefit2 = $benefits[1] ?? "Competitive package + growth opportunity";

        [$hookText, $hookSub] = $this->getVideoHook($job);

        return <<<PROMPT
Create a 4-frame visual storyboard for a 10-second TikTok / Instagram Reels job advertisement.

━━━ JOB DETAILS ━━━
Position : {$title}{$companyAt}
{$locationLine}
{$salaryBlock}
{$deadlineBlock}
Apply at : {$url}

━━━ CANVAS SPECS ━━━
Each frame : 1080 × 1920 px  (9:16 portrait — TikTok / Stories format)
Style      : Ultra-realistic, vibrant, professional — Fortune 500 African recruitment ad
People     : Black African professionals dressed appropriately for the role
Branding   : Wakanda Jobs logo (attached) in top-left corner of every frame

━━━ STORYBOARD FRAMES ━━━

FRAME 1 — THE HOOK (0 – 2 s)
  Scene: A young Black African professional stares at their phone with a bored/frustrated expression, then suddenly looks up with wide excited eyes.
  Background: Blurred urban street or office lobby — authentic African city energy.
  Text overlay (large, bold white with drop shadow): "{$hookText}"
  Sub-text: "{$hookSub}"
  Camera feel: Slightly handheld, street-photography realism.

FRAME 2 — THE OPPORTUNITY (2 – 5 s)
  Scene: Same professional standing confidently in a modern workplace relevant to the {$title} role. Wide smile, power pose.
  Text overlay (huge, centred, bold): "{$title}"{$salaryOverlay}
  Sub-text: "{$company}{$locationOf}"
  {$flagAccent}
  Camera feel: Slow push-in towards the subject, creating a sense of momentum.

FRAME 3 — THE DETAILS (5 – 8 s)
  Scene: Close-up on the professional's face — focused, determined. Colleagues visible and collaborating behind them.
  Text overlays (stacked list, bold):
    ✅ {$benefit1}
    ✅ {$benefit2}{$deadlineOverlay}
  Bottom tagline: "This is YOUR moment."
  Camera feel: Rack focus from blurred background to sharp face.

FRAME 4 — CALL TO ACTION (8 – 10 s)
  Scene: The professional holds up their phone showing wakandajobs.com, points directly at the camera with a huge confident smile.
  Text overlay (bold, urgent, centred): "APPLY NOW! 🚀"
  Sub-text: "wakandajobs.com"
  Bottom: Wakanda Jobs logo + "Find your next opportunity in {$country}"
  Camera feel: Wide shot → quick zoom to face on final beat.

━━━ OUTPUT INSTRUCTIONS ━━━
• Generate all 4 frames as SEPARATE images, clearly labelled Frame 1 through Frame 4.
• Every image must be exactly 1080 × 1920 px (9:16 portrait). No landscape. No square.
• Keep the same talent, outfit, and colour palette across all 4 frames for visual continuity.
• After generating, I will paste all 4 images into Gemini to animate into a 10-second video.
PROMPT;
    }

    public function buildGeminiVideoPrompt(Job $job): string
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: ''));
        $country  = trim((string) ($job->country?->name ?? ''));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $deadlineStr = $deadline ? $deadline->format('M j, Y') : '';
        $url      = route('public.job', $job->slugable?->key ?? $job->id);

        $companyAt   = $company  ? " at {$company}" : '';
        $locationOf  = $location ? " | {$location}" : '';
        $jobTitleCTA = "{$title}{$companyAt}";

        [$hookText, $hookSub] = $this->getVideoHook($job);

        return <<<PROMPT
━━━ HOW TO USE THIS PROMPT ━━━
Model    : Gemini 2.0 Flash (Experimental) — video generation mode
           OR Google Veo 2 (via Gemini Advanced → "Generate video")
Attach   : Frame 1, Frame 2, Frame 3, Frame 4 (from ChatGPT), then Wakanda Jobs logo (PNG)
Then paste this entire prompt below the attachments and hit Generate.

━━━ MISSION ━━━
Turn the 4 attached storyboard frames into a single 10-second scroll-stopping video ad
for TikTok, Instagram Reels, and WhatsApp Status — targeting African job seekers aged 18–40.

━━━ JOB CONTEXT ━━━
Position : {$title}{$companyAt}
Location : {$location}
Country  : {$country}
Apply at : {$url}

━━━ WAKANDA JOBS LOGO USAGE ━━━
The Wakanda Jobs logo PNG is attached. Use it as:
  • Persistent watermark — top-left corner of EVERY frame, ~15% screen width, 80% opacity.
  • CTA feature — in Frame 4 only: animate logo from watermark size → 30% width centred,
    0.4 s ease-in, then pulse gently in sync with the "APPLY NOW!" heartbeat.
  • Add a frosted-dark pill behind the logo so it reads clearly on any background.
  • Never distort, recolour, or crop the logo.

━━━ VIDEO SPECIFICATIONS ━━━
Duration   : 10 seconds exactly — do NOT exceed
Dimensions : 1080 × 1920 px (9:16 portrait — TikTok / Reels / Stories)
Frame rate : 30 fps  |  Codec : H.264 MP4
Colour     : Warm, vibrant, punchy — think golden-hour African light, not washed-out studio

━━━ FRAME TIMING & ANIMATION ━━━

FRAME 1  (0.0 – 2.0 s)  ▸ THE HOOK — Stop the Scroll
  Motion : Sharp 1.15× zoom burst on subject's face (0.1 s), then slow pull-back
  Text   : "{$hookText}" — slams in from left, bold white, drop shadow, micro-overshoot bounce
  Sub    : "{$hookSub}" — fades up softly, 0.4 s after hook text
  Feel   : Handheld camera energy (±3 px shake), real street or office authenticity

FRAME 2  (2.0 – 5.0 s)  ▸ THE OPPORTUNITY — Land the Job
  Cut    : Smash cut (0 frames, instant) — no fade, no dissolve
  Text   : "{$jobTitleCTA}" — drops from top with impact flash, bounces (1.3× → 1.0×, 0.25 s)
  Detail : Company & location lines fade in staggered 0.25 s apart
  Motion : Slow push-in — camera drifts 5% closer over the full 3 s
  Bonus  : Salary (if any) pops in from right with a 💰 emoji flash

FRAME 3  (5.0 – 8.0 s)  ▸ THE PROOF — Build Desire
  Transition : Horizontal swipe wipe from Frame 2 (left → right, 0.18 s)
  Bullets    : ✅ benefit lines pop in one-by-one (0 → 100% scale, 0.2 s each, 0.3 s stagger)
  Sound cue  : Satisfying soft 'tick' on each ✅ entry
  Tagline    : "This is YOUR moment." — italic, warm amber/gold, fades in at 7.5 s
  Focus      : Rack-focus rack: background progressively blurs while face sharpens throughout

FRAME 4  (8.0 – 10.0 s)  ▸ CALL TO ACTION — Seal It
  Cut     : 1-frame white flash (hard cut with flash)
  Logo    : Wakanda Jobs logo scales from top-left watermark → 30% centred (0.4 s ease-in-out)
  CTA     : "APPLY NOW! 🚀" slams in below logo — heartbeat pulse every 0.5 s (1.0 → 1.08 → 1.0)
  URL     : "wakandajobs.com" types out character-by-character (typewriter, 0.06 s/char)
  Finale  : At 9.5 s — freeze frame + bright vignette flash + gold confetti burst
  Hold    : Last frame held 0.5 s

━━━ TRANSITIONS ━━━
F1 → F2 : Smash cut (instant, 0 frames)
F2 → F3 : Horizontal swipe wipe (0.18 s)
F3 → F4 : 1-frame white flash cut

━━━ AUDIO DIRECTION ━━━
Music BG : Amapiano log-drum groove or Afrobeats guitar loop — 122–126 BPM, no vocals, no lyrics.
           Energy builds gently from Frame 1 through Frame 3, peaks at Frame 4's CTA.

Sound FX  :
  Frame 1 → notification 'ping' the instant hook text appears
  Frame 2 → deep cinematic bass thud on the job title impact
  Frame 3 → crisp 'tick' on each ✅ bullet pop
  Frame 4 → rising riser build → punchy drum hit on "APPLY NOW" → bright 'ding' on confetti

Outro     : Music and SFX fade out in final 0.4 s — clean, professional end.

━━━ OUTPUT ━━━
• Single MP4, 1080 × 1920, 30 fps, H.264
• Total duration: 10.0 s exactly
• All text must be legible on a mobile screen at arm's length
• Wakanda Jobs watermark visible in every single frame
PROMPT;
    }

    private function titleContains(string $title, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($title, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    private function getVideoHook(Job $job): array
    {
        $title = strtolower(trim((string) $job->name));

        $hooks = match (true) {
            $this->titleContains($title, ['developer', 'engineer', 'software', 'programmer', 'devops', 'frontend', 'backend', 'fullstack', 'data scientist', 'machine learning', 'cybersecurity', 'sysadmin', 'cloud']) => [
                ["Still job-hunting in tech? 💻", "We found something worth stopping for..."],
                ["Your next dev role just dropped 🔥", "This one was built for you..."],
                ["PSA: This tech role won't last long ⏰", "Scroll back up. Trust us."],
                ["Not all tech jobs are the same 👀", "This one's different. Here's why..."],
                ["The role your LinkedIn profile has been waiting for 🚀", "Apply before someone else does..."],
            ],
            $this->titleContains($title, ['accountant', 'accounting', 'finance', 'financial', 'auditor', 'bookkeeper', 'tax', 'cfo', 'treasurer', 'actuary']) => [
                ["Still underselling your finance skills? 💰", "This role was made for someone like you..."],
                ["Your accounting career just leveled up 📊", "See what's waiting for you..."],
                ["The finance role you've been waiting for ✨", "Your next chapter starts here..."],
                ["Numbers don't lie — and neither does this opportunity 📈", "Your skills deserve this role..."],
            ],
            $this->titleContains($title, ['nurse', 'doctor', 'medical', 'healthcare', 'clinical', 'pharmacist', 'dentist', 'therapist', 'midwife', 'health officer', 'radiograph', 'physiother']) => [
                ["A role that actually makes a difference 🏥", "Healthcare professionals — this one's for you..."],
                ["Calling all healthcare heroes 📞", "Your next impactful role is here..."],
                ["You chose this career to change lives ❤️", "Now find a role that matches your calling..."],
                ["Healthcare professionals — your next chapter is here 🌍", "Real work. Real impact."],
            ],
            $this->titleContains($title, ['teacher', 'lecturer', 'tutor', 'educator', 'school', 'training officer', 'instructor', 'academic', 'curriculum']) => [
                ["Educators — this one's for you 📚", "Shape the future. One role at a time."],
                ["The teaching role that changes everything ✏️", "Your next classroom is waiting..."],
                ["You didn't choose education by accident 🌟", "Here's a role that honours that calling..."],
                ["Great teachers deserve great opportunities 🎓", "Apply before the term starts..."],
            ],
            $this->titleContains($title, ['sales', 'business development', 'account manager', 'account executive', 'relationship manager', 'bdm', 'bde']) => [
                ["This role has your name on it 🎯", "Top performers wanted. Are you one?"],
                ["Calling all closers 💼", "This sales role is waiting for its champion..."],
                ["Ready to hit new targets? 🚀", "Your next sales win starts here..."],
                ["The best salespeople don't wait — they apply 🔥", "Don't let this one slip..."],
            ],
            $this->titleContains($title, ['manager', 'director', 'head of', 'chief', 'ceo', 'coo', 'cto', 'vp ', 'president', 'supervisor', 'team lead']) => [
                ["Leaders — your next chapter just arrived 🪑", "This seat was made for you..."],
                ["Ready to lead something bigger? 🌟", "This leadership role is calling your name..."],
                ["The management role worth stopping for 💼", "Real responsibility. Real impact."],
                ["It's time to lead. Not follow. 🚀", "A role that matches your ambition..."],
            ],
            $this->titleContains($title, ['marketing', 'brand manager', 'digital', 'social media', 'content', 'seo', 'campaign', 'communications', 'pr manager', 'copywriter']) => [
                ["Marketing pros — your next big role just dropped 🎯", "Creative talent wanted. See if you qualify..."],
                ["This campaign started with you 🔥", "A marketing role worth writing home about..."],
                ["Stop scrolling. Start applying. 📲", "Your next brand story begins here..."],
                ["Brands are built by people like you 💡", "Here's your next big brief..."],
            ],
            $this->titleContains($title, ['driver', 'logistics', 'transport', 'delivery', 'fleet', 'warehouse', 'supply chain', 'dispatcher']) => [
                ["On the move? So is this opportunity 🚗", "A logistics role worth the trip..."],
                ["This role keeps Africa moving 🌍", "Your next route starts here..."],
                ["Reliable people deserve reliable jobs 💪", "Here's one worth showing up for..."],
            ],
            $this->titleContains($title, ['intern', 'graduate', 'entry level', 'trainee', 'learnership', 'apprentice', 'attaché', 'attachment']) => [
                ["Fresh grad? Your time is NOW 🎓", "Don't wait for experience — build it here..."],
                ["Every expert was once a beginner 🌱", "Your career story starts with this role..."],
                ["The opportunity fresh graduates dream about 🚀", "Apply before someone else does..."],
                ["Your first big break is right here 🌟", "Don't let it scroll past..."],
            ],
            $this->titleContains($title, ['lawyer', 'legal', 'advocate', 'attorney', 'solicitor', 'paralegal', 'compliance']) => [
                ["The legal role worth arguing for ⚖️", "Your next case starts here..."],
                ["Justice. Impact. Career growth. 🏛️", "A legal opportunity worth fighting for..."],
                ["Your legal career just got an upgrade 📜", "See what's waiting for you..."],
            ],
            $this->titleContains($title, ['chef', 'cook', 'kitchen', 'hospitality', 'hotel', 'catering', 'restaurant', 'barista', 'pastry']) => [
                ["Your next kitchen awaits 👨‍🍳", "A hospitality role worth savouring..."],
                ["Passion for food? We've got a role for that 🍽️", "Your culinary career just leveled up..."],
                ["Great chefs deserve great kitchens 🔥", "Apply before the table fills up..."],
            ],
            default => [
                ["You almost scrolled past your dream job 👀", "Keep reading. You'll be glad you did."],
                ["Stop scrolling — this could change everything 🚀", "Your next career move is right here..."],
                ["Your next chapter starts here ✨", "Don't let this one pass you by..."],
                ["Not all job posts are created equal 🔥", "This one's different. Here's why..."],
                ["This job has been waiting for you 🎯", "The right role at the right time..."],
                ["The opportunity you've been looking for just landed 💥", "Apply before it's gone..."],
                ["Real talk — this role is worth your time ⏱️", "Here's everything you need to know..."],
                ["Your 9-to-5 is about to get exciting 🌟", "A role you'll actually look forward to..."],
            ],
        };

        $index = $job->id % count($hooks);
        return $hooks[$index];
    }

    private function getFlagColors(string $country): ?string
    {
        $map = [
            'zambia'       => 'green, red, black, and copper/orange',
            'zimbabwe'     => 'green, yellow, red, black, and white',
            'south africa' => 'red, white, blue, green, gold, and black',
            'kenya'        => 'black, red, white, and green',
            'nigeria'      => 'green and white',
            'ghana'        => 'red, gold, green with a black star',
            'tanzania'     => 'green, yellow (gold), black, and blue',
            'uganda'       => 'black, yellow, and red',
            'rwanda'       => 'blue, yellow, and green',
            'malawi'       => 'black, red, and green with a rising red sun',
            'mozambique'   => 'green, white, black, yellow, and red',
            'botswana'     => 'light blue, white, and black',
            'namibia'      => 'blue, red, green, white, and gold',
            'ethiopia'     => 'green, yellow, and red with a blue star',
            'cameroon'     => 'green, red, and yellow with a gold star',
            'senegal'      => 'green, yellow, and red with a green star',
            'ivory coast'  => 'orange, white, and green',
            'angola'       => 'red and black with a gold emblem',
            'madagascar'   => 'white, red, and green',
            'mauritius'    => 'red, blue, yellow, and green',
        ];

        $key = strtolower(trim($country));
        return $map[$key] ?? null;
    }

    public function buildPlatformPosts(Job $job): array
    {
        $title    = trim((string) $job->name);
        $company  = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->getLocationAttribute() ?: $job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;
        $url      = route('public.job', $job->slugable?->key ?? $job->id);
        $excerpt  = trim(Str::limit(strip_tags((string) ($job->description ?: $job->content)), 220));

        $salaryLine = '';
        try {
            if (! $job->hide_salary && $job->salary_text) {
                $s = (string) $job->salary_text;
                if (! in_array(strtolower($s), ['attractive', 'negotiable', 'competitive'])) {
                    $salaryLine = $s;
                }
            }
        } catch (Throwable) {}

        $deadlineStr  = $deadline ? $deadline->format('M j, Y') : '';
        $titleSlug    = str_replace(' ', '', $title);
        $companySlug  = str_replace(' ', '', $company);
        $countryName  = trim((string) ($job->country?->name ?? 'Zambia'));
        $countrySlug  = str_replace(' ', '', $countryName);

        // ── TikTok ──────────────────────────────────────────────────────────
        $tiktok  = "🚨 NEW JOB ALERT 🚨\n\n";
        $tiktok .= "🎯 {$title}";
        if ($company) $tiktok .= " @ {$company}";
        $tiktok .= "\n📍 {$location}";
        if ($salaryLine) $tiktok .= "\n💰 {$salaryLine}";
        if ($deadlineStr) $tiktok .= "\n📅 Deadline: {$deadlineStr}";
        $tiktok .= "\n\nDon't miss this opportunity — apply NOW! 👇";
        $tiktok .= "\n🔗 {$url}";
        $tiktok .= "\n\n#JobsIn{$countrySlug} #{$countrySlug}Jobs #JobTok #Hiring #{$countrySlug}Hiring";
        $tiktok .= " #TikTokJobs #JobAlert #NewJob ##{$titleSlug}";
        if ($companySlug) $tiktok .= " #{$companySlug}";
        $tiktok .= " #WakandaJobs #AfricaJobs #GetHired #CareerGoals #JobOpportunity #NowHiring";

        // ── X / Twitter ─────────────────────────────────────────────────────
        // Hard 280-char limit — keep it tight
        $twitterBody  = "🔔 {$title}";
        if ($company) $twitterBody .= " at {$company}";
        $twitterBody .= "\n📍 {$location}";
        if ($salaryLine) $twitterBody .= " | 💰 {$salaryLine}";
        if ($deadlineStr) $twitterBody .= "\n⏰ Deadline: {$deadlineStr}";
        $twitterBody .= "\n\nApply 👉 {$url}";
        $twitterBody .= "\n\n#{$countrySlug}Jobs #Hiring #WakandaJobs";
        // Trim if over 280
        if (mb_strlen($twitterBody) > 280) {
            $shortTitle = Str::limit($title, 40, '…');
            $twitterBody  = "🔔 {$shortTitle}";
            if ($company) $twitterBody .= " · " . Str::limit($company, 30, '…');
            $twitterBody .= "\n📍 {$location}";
            if ($deadlineStr) $twitterBody .= " | ⏰ {$deadlineStr}";
            $twitterBody .= "\n\nApply 👉 {$url}";
            $twitterBody .= "\n#{$countrySlug}Jobs #WakandaJobs";
        }
        $twitter = $twitterBody;

        // ── LinkedIn ────────────────────────────────────────────────────────
        $linkedin  = "🌟 Exciting Career Opportunity: {$title}\n\n";
        if ($company) $linkedin .= "📢 Hiring Company: {$company}\n";
        $linkedin .= "📍 Location: {$location}\n";
        if ($salaryLine) $linkedin .= "💰 Salary: {$salaryLine}\n";
        if ($deadlineStr) $linkedin .= "📅 Application Deadline: {$deadlineStr}\n";
        $linkedin .= "\n";
        if ($excerpt) $linkedin .= "{$excerpt}\n\n";
        $linkedin .= "👉 View full details and apply: {$url}\n\n";
        $linkedin .= "Found on Wakanda Jobs — Africa's growing job platform connecting top talent with leading employers.\n\n";
        $linkedin .= "#JobOpening #Hiring #CareerOpportunity #WakandaJobs #{$countrySlug}Jobs";
        if ($titleSlug) $linkedin .= " #{$titleSlug}";
        if ($companySlug) $linkedin .= " #{$companySlug}";
        $linkedin .= " #ProfessionalDevelopment #AfricaCareers";

        // ── Facebook ────────────────────────────────────────────────────────
        $facebook  = "👋 Hey {$countryName}! We've got an opportunity you don't want to miss! 🎯\n\n";
        $facebook .= "🏷️ Position: {$title}\n";
        if ($company) $facebook .= "🏢 Company: {$company}\n";
        $facebook .= "📍 Location: {$location}\n";
        if ($salaryLine) $facebook .= "💰 Salary: {$salaryLine}\n";
        if ($deadlineStr) $facebook .= "📅 Deadline: {$deadlineStr}\n";
        if ($excerpt) $facebook .= "\n{$excerpt}\n";
        $facebook .= "\n🔗 Apply here: {$url}\n\n";
        $facebook .= "💬 Tag someone who needs a job!\n";
        $facebook .= "🔁 Share to help someone find their next opportunity!\n\n";
        $facebook .= "#WakandaJobs #{$countrySlug}Jobs #Jobs #Hiring #JobOpportunity #NowHiring";

        // ── WhatsApp Channel ────────────────────────────────────────────────
        $whatsapp  = "🔔 *JOB ALERT*\n\n";
        $whatsapp .= "*Position:* {$title}\n";
        if ($company) $whatsapp .= "*Company:* {$company}\n";
        $whatsapp .= "*Location:* {$location}\n";
        if ($salaryLine) $whatsapp .= "*Salary:* {$salaryLine}\n";
        if ($deadlineStr) $whatsapp .= "*Deadline:* {$deadlineStr}\n";
        if ($excerpt) $whatsapp .= "\n{$excerpt}\n";
        $whatsapp .= "\n*Apply Now 👉* {$url}\n\n";
        $whatsapp .= "_Wakanda Jobs — wakandajobs.com_";

        return compact('tiktok', 'twitter', 'linkedin', 'facebook', 'whatsapp');
    }

    public function buildManualSocialPost(Job $job): string
    {
        $url = route('public.job', $job->slugable?->key ?? $job->id);
        $company = trim((string) ($job->company?->name ?? ''));
        $location = trim((string) ($job->address ?: 'Zambia'));
        $deadline = $job->application_closing_date ?: $job->expire_date;

        $lines = [
            'Job Opportunity: ' . $job->name,
        ];

        if ($company !== '') {
            $lines[] = 'Company: ' . $company;
        }

        $lines[] = 'Location: ' . $location;

        if ($deadline) {
            $lines[] = 'Deadline: ' . $deadline->format('M j, Y');
        }

        $lines[] = '';
        $lines[] = Str::limit(strip_tags((string) ($job->description ?: $job->content)), 240);
        $lines[] = '';
        $lines[] = 'Apply here: ' . $url;
        $lines[] = '';
        $lines[] = '#Jobs #ZambiaJobs #Hiring #WakandaJobs';

        return trim(implode("\n", array_filter($lines, fn ($line) => $line !== null)));
    }

    // -------------------------------------------------------------------------
    // Facebook
    // -------------------------------------------------------------------------

    protected function postToFacebook(SocialAutomation $automation, Job $job): bool
    {
        $settings = $automation->settings ?? [];
        $pageId   = trim((string) ($settings['page_id'] ?? ''));
        $token    = trim((string) ($settings['access_token'] ?? ''));

        if ($pageId === '' || $token === '') {
            return false;
        }

        $response = Http::timeout(20)
            ->post("https://graph.facebook.com/v19.0/{$pageId}/feed", [
                'message'      => $this->buildJobMessage($job),
                'access_token' => $token,
            ]);

        return $response->successful() && isset($response->json()['id']);
    }

    // -------------------------------------------------------------------------
    // LinkedIn
    // -------------------------------------------------------------------------

    protected function postToLinkedIn(SocialAutomation $automation, Job $job): bool
    {
        $settings = $automation->settings ?? [];
        $orgId    = trim((string) ($settings['org_id'] ?? ''));
        $token    = trim((string) ($settings['access_token'] ?? ''));

        if ($orgId === '' || $token === '') {
            return false;
        }

        $response = Http::timeout(20)
            ->withToken($token)
            ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
            ->post('https://api.linkedin.com/v2/ugcPosts', [
                'author'          => "urn:li:organization:{$orgId}",
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary'   => ['text' => $this->buildJobMessage($job)],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ]);

        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // WhatsApp (Meta Business Cloud API)
    // -------------------------------------------------------------------------

    protected function postToWhatsApp(SocialAutomation $automation, Job $job): bool
    {
        $settings   = $automation->settings ?? [];
        $phoneId    = trim((string) ($settings['phone_number_id'] ?? ''));
        $token      = trim((string) ($settings['access_token'] ?? ''));
        $recipient  = trim((string) ($settings['recipient'] ?? '')); // phone or group

        if ($phoneId === '' || $token === '' || $recipient === '') {
            return false;
        }

        $response = Http::timeout(20)
            ->withToken($token)
            ->post("https://graph.facebook.com/v19.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'   => $recipient,
                'type' => 'text',
                'text' => ['body' => $this->buildJobMessage($job)],
            ]);

        return $response->successful();
    }

    // -------------------------------------------------------------------------
    // Telegram copy queue
    // -------------------------------------------------------------------------

    protected function postToTelegram(SocialAutomation $automation, Job $job): bool
    {
        $settings  = $automation->settings ?? [];
        $token     = trim((string) ($settings['bot_token'] ?? setting('telegram_bot_token')));
        $chatId    = trim((string) ($settings['chat_id'] ?? ''));
        $countryId = isset($settings['country_id']) && $settings['country_id'] !== ''
            ? (int) $settings['country_id']
            : null;
        $generateImage    = ! empty($settings['generate_image']);
        $noInlineButtons  = ! empty($settings['no_inline_buttons']);

        if ($token === '' || $chatId === '') {
            return false;
        }

        if ($countryId !== null && (int) $job->country_id !== $countryId) {
            return false;
        }

        return $this->sendTelegramCopyPost($token, $chatId, $job, $automation->getKey(), $generateImage, $noInlineButtons);
    }

    public function sendTelegramCopyPost(string $token, string $chatId, Job $job, ?int $automationId = null, bool $generateImage = false, bool $noInlineButtons = false): bool
    {
        $postText  = $this->buildManualSocialPost($job);
        $imagePath = null;

        if ($generateImage) {
            try {
                $imagePath = app(JobImageGeneratorService::class)->generate($job);
            } catch (Throwable) {
                $imagePath = null;
            }
        }

        if ($imagePath && file_exists($imagePath)) {
            // sendPhoto: caption is capped at 1024 chars by Telegram.
            $caption  = Str::limit($postText, 1020, '…');
            $response = Http::timeout(30)
                ->attach('photo', file_get_contents($imagePath), 'job_banner.jpg')
                ->post("https://api.telegram.org/bot{$token}/sendPhoto", [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                ]);
            @unlink($imagePath);
        } else {
            $response = Http::timeout(20)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chatId,
                'text'                     => $postText,
                'disable_web_page_preview' => true,
            ]);
        }

        if (! $response->successful() || ! data_get($response->json(), 'ok')) {
            return false;
        }

        $messageId = data_get($response->json(), 'result.message_id');

        if (! $messageId) {
            return true;
        }

        // When no inline buttons are needed (e.g. public channel posts) we're done.
        if ($noInlineButtons) {
            return true;
        }

        // Log message ID so /clear can delete it later.
        DB::table('telegram_message_log')->insert([
            'automation_id' => $automationId,
            'chat_id'       => $chatId,
            'message_id'    => (string) $messageId,
            'job_id'        => $job->getKey(),
            'created_at'    => now(),
        ]);

        $cacheKey = 'tg_copy_' . Str::uuid();

        $step2Params = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'cache_key'  => $cacheKey,
            'job_id'     => $job->getKey(),
        ];
        if ($automationId !== null) {
            $step2Params['automation_id'] = $automationId;
        }

        $step2Url = URL::temporarySignedRoute(
            'public.telegram-social-delete',
            now()->addDays(7),
            $step2Params,
        );

        $step1Params = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'cache_key'  => $cacheKey,
            'job_id'     => $job->getKey(),
        ];
        if ($automationId !== null) {
            $step1Params['automation_id'] = $automationId;
        }

        $step1Url = URL::temporarySignedRoute(
            'public.telegram-social-prompt',
            now()->addDays(7),
            $step1Params,
        );

        // Build AI prompt safely — never let it prevent the button from appearing
        try {
            $aiPrompt = $this->buildAiImagePrompt($job);
        } catch (Throwable) {
            $logoHint = '';
            if (! empty($job->company?->logo)) {
                try {
                    $logoHint = ' Use colours extracted from the company logo at: ' . RvMedia::getImageUrl($job->company->logo) . ' for the background, environment, and clothing.';
                } catch (Throwable) {}
            }
            $aiPrompt = "Generate an ultra-realistic professional African job ad image for: {$job->name} at Wakanda Jobs (wakandajobs.com). Include the job title prominently.{$logoHint} Black African professionals dressed for the role. Clean, trustworthy, corporate feel.";
        }

        try {
            $platformPosts = $this->buildPlatformPosts($job);
        } catch (Throwable) {
            $platformPosts = [];
        }

        $storyboardPrompt = '';
        try {
            $storyboardPrompt = $this->buildStoryboardPrompt($job);
        } catch (Throwable) {}

        $geminiPrompt = '';
        try {
            $geminiPrompt = $this->buildGeminiVideoPrompt($job);
        } catch (Throwable) {}

        // Resolve company logo URL for the UI attachment tip
        $companyLogoUrl = null;
        $companyName    = trim((string) ($job->company?->name ?? ''));
        if ($job->company && ! empty($job->company->logo)) {
            try {
                $companyLogoUrl = RvMedia::getImageUrl($job->company->logo);
            } catch (Throwable) {}
        }

        Cache::put($cacheKey, [
            'text'              => $postText,
            'ai_prompt'         => $aiPrompt,
            'storyboard_prompt' => $storyboardPrompt,
            'gemini_prompt'     => $geminiPrompt,
            'step2_url'         => $step2Url,
            'platform_posts'    => $platformPosts,
            'company_logo_url'  => $companyLogoUrl,
            'company_name'      => $companyName ?: null,
            'job_name'          => trim((string) $job->name),
        ], now()->addDays(7));

        try {
            Http::timeout(20)->post("https://api.telegram.org/bot{$token}/editMessageReplyMarkup", [
                'chat_id'      => $chatId,
                'message_id'   => $messageId,
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎨 Step 1: AI Image Prompt', 'url' => $step1Url],
                        ],
                    ],
                ],
            ]);
        } catch (Throwable) {
            // Non-fatal: message sent, button failed — log but continue
        }

        return true;
    }

}
