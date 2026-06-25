<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerServiceOrder extends BaseModel
{
    protected $table = 'jb_career_service_orders';

    protected $fillable = [
        'service_type',
        'service_name',
        'amount',
        'currency',
        'sales_agent_id',
        'sales_agent_original_amount',
        'sales_agent_discount_amount',
        'sales_agent_code',
        'customer_name',
        'customer_email',
        'customer_phone',
        'candidate_id',
        'assigned_coach_name',
        'assigned_coach_email',
        'charge_id',
        'payment_method',
        'delivery_status',
        'delivered_at',
        'ai_cv_score',
        'ai_cv_feedback',
        'status',
        'notes',
        'candidate_cv_path',
        'reviewed_cv_path',
    ];

    protected $casts = [
        'amount' => 'float',
        'sales_agent_original_amount' => 'float',
        'sales_agent_discount_amount' => 'float',
        'delivered_at' => 'datetime',
        'ai_cv_feedback' => 'array',
    ];

    public static function deliveryStatuses(): array
    {
        return [
            'unassigned' => 'Unassigned',
            'assigned' => 'Assigned',
            'in_progress' => 'In progress',
            'delivered' => 'Delivered',
            'revision_requested' => 'Revision requested',
            'cancelled' => 'Cancelled',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'candidate_id');
    }

    public function salesAgent(): BelongsTo
    {
        return $this->belongsTo(SalesAgent::class, 'sales_agent_id');
    }

    public static function services(): array
    {
        return [
            'cv_review' => [
                'name'        => 'Basic CV Review',
                'price'       => (float) setting('career_service_price_cv_review', 100),
                'delivery'    => setting('career_service_delivery_cv_review', '24 hrs'),
                'description' => 'Get expert eyes on your existing CV. We check every section and give you a clear written report on what to fix — so your next application stands out.',
                'what_it_is'  => 'A professional review of your current CV with actionable feedback on structure, content, grammar, and ATS-friendliness.',
                'icon'        => 'fi-rr-document',
                'time'        => '15–30 minutes',
                'steps' => [
                    'You submit your current CV',
                    'We check formatting and visual design',
                    'We review grammar and spelling',
                    'We identify weak or vague job descriptions',
                    'We flag missing sections (summary, skills, achievements)',
                    'We assess ATS (Applicant Tracking System) friendliness',
                    'You receive a written report with clear recommendations',
                ],
                'deliverables' => [
                    'Your CV with inline annotations',
                    'Written review report',
                    'Priority improvement checklist',
                ],
                'benefits' => [
                    'Know exactly what recruiters see when they open your CV',
                    'Fix issues before submitting to competitive roles',
                    'Understand if your CV passes ATS software',
                ],
            ],
            'cv_rewrite' => [
                'name'        => 'Professional CV Rewrite',
                'price'       => (float) setting('career_service_price_cv_rewrite', 350),
                'delivery'    => setting('career_service_delivery_cv_rewrite', '48 hrs'),
                'description' => 'A complete, from-scratch rewrite of your CV into a modern, professional format tailored to your target role — delivered as PDF and Word.',
                'what_it_is'  => 'Our career coach rewrites your entire CV: stronger summary, impactful bullet points, keyword optimisation, and a clean professional layout.',
                'icon'        => 'fi-rr-pencil',
                'time'        => '1–3 hours',
                'steps' => [
                    'You submit your current CV and target job role',
                    'Coach reviews your employment history and skills',
                    'Coach rewrites your professional profile/summary',
                    'Job responsibilities are rewritten with strong action verbs',
                    'Achievements are highlighted with numbers and results',
                    'Keywords are optimised for ATS and recruiter searches',
                    'A clean, professional layout is applied',
                    'You receive PDF and editable Word versions',
                ],
                'deliverables' => [
                    'New CV in PDF format',
                    'Editable Word document',
                    'ATS keyword summary',
                ],
                'benefits' => [
                    'Make a strong first impression in 6 seconds',
                    'Beat ATS filters with role-matched keywords',
                    'Present your experience in the most compelling way',
                    'Reusable template you can update yourself',
                ],
            ],
            'linkedin' => [
                'name'        => 'LinkedIn Optimisation',
                'price'       => (float) setting('career_service_price_linkedin', 300),
                'delivery'    => setting('career_service_delivery_linkedin', '48 hrs'),
                'description' => 'Transform your LinkedIn profile into a recruiter magnet. We rewrite your headline, About section, and experience — then add the keywords that get you found.',
                'what_it_is'  => 'Professional enhancement of your LinkedIn profile so recruiters can discover you and immediately see your value.',
                'icon'        => 'fi-rr-network',
                'time'        => '1–2 hours',
                'steps' => [
                    'You share your LinkedIn profile URL',
                    'We review your current headline, About, and experience sections',
                    'We rewrite your headline with role + value keywords',
                    'We rewrite your About section to tell your career story',
                    'Experience entries are strengthened with achievements',
                    'Skills section is updated with high-demand keywords',
                    'Profile photo and banner advice provided',
                    'Networking and connection strategy tips included',
                ],
                'deliverables' => [
                    'Rewritten headline, About, and experience text (ready to paste)',
                    'LinkedIn improvement report',
                    'Skills keyword list',
                    'Profile photo tips',
                ],
                'benefits' => [
                    'Appear in recruiter searches for your target role',
                    'Get InMail messages from hiring managers',
                    'Build a professional personal brand',
                    'Stand out from thousands of profiles',
                ],
            ],
            'cover_letter' => [
                'name'        => 'Cover Letter Writing',
                'price'       => (float) setting('career_service_price_cover_letter', 150),
                'delivery'    => setting('career_service_delivery_cover_letter', '24 hrs'),
                'description' => 'A tailored, compelling cover letter written specifically for your target job — matched to the advert and your experience.',
                'what_it_is'  => 'A professionally written cover letter that connects your skills to the role requirements and gives the hiring manager a reason to call you.',
                'icon'        => 'fi-rr-envelope',
                'time'        => '30–45 minutes',
                'steps' => [
                    'You provide the job advert and your CV',
                    'We analyse the role requirements',
                    'We match your experience to what they are looking for',
                    'We write a 3-paragraph cover letter (opening, body, close)',
                    'The letter is proofread for grammar and tone',
                    'You receive Word and PDF versions',
                ],
                'deliverables' => [
                    'Tailored cover letter in Word format',
                    'PDF version',
                ],
                'benefits' => [
                    'Show the employer you read and understood the role',
                    'Differentiate yourself from applicants who send generic letters',
                    'Increase your chances of being shortlisted',
                ],
            ],
            'interview_coaching' => [
                'name'        => 'Interview Coaching (1 hr)',
                'price'       => (float) setting('career_service_price_interview_coaching', 250),
                'delivery'    => setting('career_service_delivery_interview_coaching', '72 hrs'),
                'description' => 'A live 1-on-1 mock interview session with a career coach via video call. Walk in prepared, confident, and ready to impress.',
                'what_it_is'  => 'Your coach runs a realistic mock interview, assesses your answers, and teaches you the techniques top candidates use.',
                'icon'        => 'fi-rr-user-headset',
                'time'        => '45–60 minutes',
                'steps' => [
                    'You share your target role and company',
                    'Coach researches common questions for that role',
                    'Live mock interview is conducted via video call',
                    'Coach assesses your confidence, communication, and answers',
                    'STAR method (Situation, Task, Action, Result) is taught',
                    'Weak answers are identified and improved together',
                    'You receive a written feedback report after the session',
                ],
                'deliverables' => [
                    'Live 1-on-1 coaching session (1 hr)',
                    'Interview feedback report',
                    'Sample strong answers for key questions',
                    'Personal improvement plan',
                ],
                'benefits' => [
                    'Eliminate interview nerves with realistic practice',
                    'Learn how to answer "Tell me about yourself" and tough questions',
                    'Know what interviewers are really looking for',
                    'Walk in with a proven structure for any question',
                ],
            ],
            'bundle' => [
                'name'        => 'Complete Career Bundle',
                'price'       => (float) setting('career_service_price_bundle', 750),
                'delivery'    => setting('career_service_delivery_bundle', '72 hrs'),
                'description' => 'Everything you need to land your next job — CV Rewrite, Cover Letter, LinkedIn Optimisation, and Interview Coaching, all in one package at the best value.',
                'what_it_is'  => 'Our flagship service. Get the full career transformation: a new CV, cover letter, optimised LinkedIn profile, and a live interview coaching session.',
                'icon'        => 'fi-rr-stars',
                'time'        => '3–5 days',
                'badge'       => 'Best Value',
                'steps' => [
                    'You submit your CV and target role details',
                    'Coach completes the Professional CV Rewrite (48 hrs)',
                    'Coach writes your tailored Cover Letter (24 hrs)',
                    'Coach optimises your LinkedIn profile (48 hrs)',
                    'Live Interview Coaching session is scheduled',
                    'All documents delivered together with a Job Search Strategy guide',
                ],
                'deliverables' => [
                    'New CV (PDF + Word)',
                    'Tailored cover letter (PDF + Word)',
                    'LinkedIn profile rewrite (ready to paste)',
                    'Live 1-hr interview coaching session',
                    'Job Search Strategy guide',
                ],
                'benefits' => [
                    'Save K250 vs buying services individually',
                    'One coach handles everything for a consistent personal brand',
                    'Apply to roles with full confidence within 72 hrs',
                    'Best investment for serious job seekers',
                ],
                'includes' => [
                    'Professional CV Rewrite',
                    'Cover Letter Writing',
                    'LinkedIn Optimisation',
                    'Interview Coaching (1 hr)',
                    'Job Search Strategy Session',
                ],
            ],
            'graduate_starter' => [
                'name'        => 'Graduate Career Starter',
                'price'       => (float) setting('career_service_price_graduate_starter', 500),
                'delivery'    => setting('career_service_delivery_graduate_starter', '48 hrs'),
                'description' => 'Just graduated? Launch your career with a professional CV, cover letter, LinkedIn setup, and interview tips — all tailored for entry-level roles.',
                'what_it_is'  => 'A complete starter pack for new graduates entering the Zambian job market. Designed specifically for first-time job seekers.',
                'icon'        => 'fi-rr-graduation-cap',
                'time'        => '2–3 days',
                'badge'       => 'Graduates',
                'steps' => [
                    'You share your degree, internships, and target industry',
                    'Coach writes a graduate-focused professional CV',
                    'A flexible cover letter template is created',
                    'LinkedIn profile is set up and optimised',
                    'Interview tips guide for common graduate interview questions',
                ],
                'deliverables' => [
                    'Graduate CV (PDF + Word)',
                    'Cover letter template',
                    'LinkedIn profile write-up',
                    'Graduate interview tips guide',
                ],
                'benefits' => [
                    'Start your career with a CV that looks experienced',
                    'Know exactly how to write about internships and projects',
                    'Get found by graduate recruiters on LinkedIn',
                    'Walk into your first interviews prepared',
                ],
                'includes' => [
                    'Graduate CV Writing',
                    'Cover Letter Template',
                    'LinkedIn Profile Setup',
                    'Interview Tips Guide',
                ],
            ],
            'ats_optimization' => [
                'name'        => 'ATS CV Optimisation',
                'price'       => (float) setting('career_service_price_ats_optimization', 200),
                'delivery'    => setting('career_service_delivery_ats_optimization', '24 hrs'),
                'description' => 'Most large employers filter CVs through software before a human ever sees them. We optimise your CV to pass ATS filters and land in the interview pile.',
                'what_it_is'  => 'ATS (Applicant Tracking System) optimisation ensures your CV uses the right keywords, formatting, and structure to get past automated screening.',
                'icon'        => 'fi-rr-settings',
                'time'        => '1–2 hours',
                'steps' => [
                    'You provide your CV and target job adverts',
                    'We run ATS compatibility checks on your current CV',
                    'Keywords from job adverts are mapped to your experience',
                    'Formatting is updated (no tables, graphics, or columns that break ATS)',
                    'Headings and section labels are standardised',
                    'File is saved in ATS-safe format',
                    'You receive an ATS score before and after',
                ],
                'deliverables' => [
                    'ATS-optimised CV (Word + PDF)',
                    'Before/after ATS compatibility report',
                    'Keyword match list for your target roles',
                ],
                'benefits' => [
                    'Get past automated screening at banks, NGOs, and multinationals',
                    'Match job description keywords without keyword stuffing',
                    'Increase your call-back rate significantly',
                ],
            ],
            'personal_statement' => [
                'name'        => 'Personal Statement Writing',
                'price'       => (float) setting('career_service_price_personal_statement', 250),
                'delivery'    => setting('career_service_delivery_personal_statement', '48 hrs'),
                'description' => 'A compelling personal statement for scholarship, university, or professional applications — written by an expert to maximise your chances.',
                'what_it_is'  => 'A tailored 500–800 word personal statement that tells your story, highlights your motivation, and makes selection panels choose you.',
                'icon'        => 'fi-rr-edit',
                'time'        => '1–2 hours',
                'steps' => [
                    'You share the application requirements and word limit',
                    'You tell us your background, goals, and why this opportunity',
                    'Writer crafts a structured personal statement (intro, body, close)',
                    'Statement is tailored to the specific institution or scholarship',
                    'Proofreading and final polish',
                    'Delivered in Word and PDF',
                ],
                'deliverables' => [
                    'Personal statement (Word + PDF)',
                    'One round of revisions included',
                ],
                'benefits' => [
                    'Stand out from hundreds of applicants',
                    'Tell your story in a structured, persuasive way',
                    'Ideal for UNZA, CBU, Copperbelt University, and international scholarships',
                ],
            ],
            'career_consultation' => [
                'name'        => 'Career Consultation',
                'price'       => (float) setting('career_service_price_career_consultation', 200),
                'delivery'    => setting('career_service_delivery_career_consultation', '48 hrs'),
                'description' => 'Feeling stuck in your career? Book a 45-minute 1-on-1 session with a career coach for personalised guidance on your next move.',
                'what_it_is'  => 'A focused career guidance session where you get expert advice on job searching, career switching, salary negotiation, or career progression.',
                'icon'        => 'fi-rr-comments',
                'time'        => '45 minutes',
                'steps' => [
                    'You book and share your career situation in advance',
                    'Coach reviews your background before the session',
                    'Live 45-minute video or phone consultation',
                    'Coach covers your specific questions and goals',
                    'Practical next steps are agreed',
                    'You receive a written summary of recommendations after',
                ],
                'deliverables' => [
                    '45-minute 1-on-1 consultation session',
                    'Written action plan and recommendations',
                ],
                'benefits' => [
                    'Get clarity on your career path',
                    'Learn how to approach salary negotiations',
                    'Understand how to switch industries or roles',
                    'Leave with a concrete 30-day action plan',
                ],
            ],
            'executive_cv' => [
                'name'        => 'Executive CV Service',
                'price'       => (float) setting('career_service_price_executive_cv', 1500),
                'delivery'    => setting('career_service_delivery_executive_cv', '72 hrs'),
                'description' => 'For managers, directors, and executives. A premium, boardroom-ready CV that positions your leadership, strategy, and results at the highest level.',
                'what_it_is'  => 'A senior-level CV rewrite crafted for professionals targeting director, C-suite, or executive roles. Includes executive profile, board experience, and P&L summary.',
                'icon'        => 'fi-rr-crown',
                'time'        => '3–5 hours',
                'badge'       => 'Premium',
                'steps' => [
                    'You submit your current CV and LinkedIn profile',
                    'In-depth consultation call with senior coach (30 mins)',
                    'Executive profile/summary written for C-suite impact',
                    'Leadership achievements quantified (P&L, team size, revenue)',
                    'Board experience and governance roles highlighted',
                    'Executive-level formatting and design applied',
                    'LinkedIn Executive Summary included',
                    'Delivered as PDF, Word, and InDesign-exported design version',
                ],
                'deliverables' => [
                    'Executive CV (PDF + Word)',
                    'Executive LinkedIn summary',
                    'Cover letter for executive applications',
                    '30-minute consultation call',
                ],
                'benefits' => [
                    'Position yourself for director and C-suite roles',
                    'Demonstrate ROI and strategic leadership clearly',
                    'Beat executive recruiters\' expectations',
                    'Appropriate for NGOs, multinationals, government, and private sector',
                ],
            ],
        ];
    }
}
