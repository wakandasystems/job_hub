<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Ny jobbansökan (till administratörer)',
            'description' => 'E-postmall för att skicka meddelande till administratörer när systemet får en ny jobbansökan',
            'subject' => 'Ny jobbansökan',
        ],
        'employer-new-job-application' => [
            'title' => 'Ny jobbansökan (till arbetsgivare och kollegor)',
            'description' => 'E-postmall för att skicka meddelande till arbetsgivare och kollegor när systemet får en ny jobbansökan',
            'subject' => 'Ny jobbansökan',
        ],
        'new-job-posted' => [
            'title' => 'Nytt jobb publicerat',
            'description' => 'Skicka e-post till administratör när ett nytt jobb publiceras',
            'subject' => 'Nytt jobb publicerat på {{ site_title }} av {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Ny företagsprofil skapad',
            'description' => 'Skicka e-post till administratör när en arbetsgivare skapar en ny företagsprofil',
            'subject' => 'Ny företagsprofil skapad på {{ site_title }} av {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Jobbet går snart ut',
            'description' => 'Skicka e-post till författaren om deras jobb går ut inom 3 dagar',
            'subject' => 'Ditt jobb "{{ job_name }}" går ut om {{ job_expired_after }} dagar',
        ],
        'job-renewed' => [
            'title' => 'Jobbet förnyat',
            'description' => 'Skicka e-post till författaren när deras jobb förnyas',
            'subject' => 'Ditt jobb "{{ job_name }}" har förnyats automatiskt',
        ],
        'payment-receipt' => [
            'title' => 'Betalningskvitto',
            'description' => 'Skicka ett meddelande till användaren när de köper krediter',
            'subject' => 'Betalningskvitto för paket {{ package_name }} på {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Konto registrerat',
            'description' => 'Skicka ett meddelande till administratör när en ny arbetsgivare/jobbsökande registrerar sig',
            'subject' => 'Ny {{ account_type }} registrerad på {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Bekräfta e-post',
            'description' => 'Skicka e-post till användare när de registrerar ett konto för att verifiera deras e-post',
            'subject' => 'Bekräfta e-postmeddelande',
        ],
        'password-reminder' => [
            'title' => 'Återställ lösenord',
            'description' => 'Skicka e-post till användare när de begär att återställa lösenord',
            'subject' => 'Återställ lösenord',
        ],
        'free-credit-claimed' => [
            'title' => 'Gratis kredit hämtad',
            'description' => 'Skicka ett meddelande till administratör när gratis kredit hämtas',
            'subject' => '{{ account_name }} har hämtat gratis kredit på {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Betalning mottagen',
            'description' => 'Skicka ett meddelande till administratör när någon köper krediter',
            'subject' => 'Betalning mottagen från {{ account_name }} på {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Faktura betalningsdetaljer',
            'description' => 'Skicka ett meddelande till kunden som gör jobbpubliceringsbetalningen',
            'subject' => 'Betalning mottagen från {{ account_name }} på {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Nytt jobb publicerat',
            'description' => 'Skicka e-post till jobbsökande när ett nytt jobb publiceras',
            'subject' => 'Rekryterar {{ job_name }} på {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Jobb godkänt',
            'description' => 'Skicka e-post till författaren när deras jobb godkänns',
            'subject' => 'Ditt jobb "{{ job_name }}" har godkänts',
        ],
        'company-approved' => [
            'title' => 'Företag godkänt',
            'description' => 'Skicka e-post till författaren när deras företag godkänns',
            'subject' => 'Ditt företag "{{ company_name }}" har godkänts',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Bekräftelse på jobbansökan',
            'description' => 'Skicka e-post till jobbsökande när de ansökt om ett jobb',
            'subject' => 'Ansökningsbekräftelse för {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Namn',
        'position' => 'Befattning',
        'email' => 'E-post',
        'phone' => 'Telefon',
        'summary' => 'Sammanfattning',
        'resume' => 'CV',
        'cover_letter' => 'Personligt brev',
        'job_application' => 'Jobbansökan',
        'job_name' => 'Jobbnamn',
        'job_url' => 'Jobb URL',
        'job_author' => 'Jobbförfattare',
        'company_name' => 'Företagsnamn',
        'company_url' => 'Företags URL',
        'employer_name' => 'Arbetsgivarnamn',
        'job_list' => 'Jobblista URL',
        'job_expired_after' => 'Jobb går ut efter x dagar',
        'account_name' => 'Kontonamn',
        'account_email' => 'Konto e-post',
        'package_name' => 'Paketets namn',
        'package_price' => 'Pris',
        'package_percent_discount' => 'Procentuell rabatt',
        'package_number_of_listings' => 'Antal annonser',
        'package_price_per_credit' => 'Pris per kredit',
        'account_type' => 'Kontotyp (arbetsgivare/jobbsökande)',
        'verify_link' => 'Verifieringslänk',
        'reset_link' => 'Återställningslänk',
        'invoice_code' => 'Fakturakod',
        'invoice_link' => 'Fakturalänk',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hej Admin!',
        'account_registered_new_account' => 'En ny :account_type registrerad:',
        'account_registered_name' => 'Namn: <strong>:account_name</strong>',
        'account_registered_email' => 'E-post: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hej, vi har fått en ny jobbansökan från :site_title!',
        'admin_job_application_name' => 'Namn: :job_application_name',
        'admin_job_application_position' => 'Befattning: :job_application_position',
        'admin_job_application_email' => 'E-post: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Sammanfattning: :job_application_summary',
        'admin_job_application_resume' => 'CV: :job_application_resume',
        'admin_job_application_cover_letter' => 'Personligt brev: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hej, vi har fått en ny jobbansökan från :site_title!',
        'employer_job_application_name' => 'Namn: :job_application_name',
        'employer_job_application_position' => 'Befattning: :job_application_position',
        'employer_job_application_email' => 'E-post: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Sammanfattning: :job_application_summary',
        'employer_job_application_resume' => 'CV: :job_application_resume',
        'employer_job_application_cover_letter' => 'Personligt brev: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Företag godkänt',
        'company_approved_greeting' => 'Hej,',
        'company_approved_message' => 'Vi är glada att informera dig om att ditt företag har godkänts och nu är aktivt på vår plattform.',
        'company_approved_info' => 'Företagsinformation',
        'company_approved_name' => 'Namn: <strong>:company_name</strong>',
        'company_approved_view' => 'Visa',
        'company_approved_here' => 'här',

        // Confirm email template
        'confirm_email_greeting' => 'Hej!',
        'confirm_email_message' => 'Vänligen verifiera din e-postadress för att få tillgång till denna webbplats. Klicka på knappen nedan för att verifiera din e-post.',
        'confirm_email_button' => 'Verifiera nu',
        'confirm_email_regards' => 'Hälsningar,',
        'confirm_email_trouble' => 'Om du har problem med att klicka på "Verifiera nu"-knappen, kopiera och klistra in URL:en nedan i din webbläsare: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Jobb godkänt',
        'job_approved_greeting' => 'Hej :job_author,',
        'job_approved_message' => 'Vi är glada att informera dig om att din jobbannons har godkänts och nu är aktiv på vår plattform.',
        'job_approved_info' => 'Jobbinformation',
        'job_approved_job_title' => 'Jobbtitel: <strong>:job_name</strong>',
        'job_approved_view' => 'Visa',
        'job_approved_here' => 'här',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hej :job_author!',
        'job_expired_soon_message' => 'Ditt jobb <a href=":job_url">:job_name</a> går ut om :job_expired_after dagar.',
        'job_expired_soon_renew' => 'Vänligen <a href=":job_list">gå hit</a> för att förnya ditt jobb.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hej :job_author!',
        'job_renewed_message' => 'Ditt jobb <a href=":job_url">:job_name</a> har förnyats automatiskt.',

        // New job posted email template
        'new_job_posted_title' => 'Nytt jobb publicerat',
        'new_job_posted_admin_greeting' => 'Hej Admin,',
        'new_job_posted_message' => 'Vi är glada att informera dig om att en ny jobbannons har publicerats av en arbetsgivare på vår plattform.',
        'new_job_posted_info' => 'Jobbinlägg',
        'new_job_posted_employer' => 'Arbetsgivare: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Jobbtitel: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Adminpanel länk',
        'new_job_posted_here' => 'här',

        // New company profile created email template
        'new_company_profile_title' => 'Ny företagsprofil skapad',
        'new_company_profile_admin_greeting' => 'Hej Admin!',
        'new_company_profile_message' => 'En ny företagsprofil har skapats av :employer_name ":company_name"',
        'new_company_profile_info' => 'Företagsinformation',
        'new_company_profile_employer' => 'Arbetsgivare: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Företagsnamn: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Adminpanel länk',
        'new_company_profile_here' => 'här',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hej :account_name!',
        'payment_receipt_message' => 'Betalningskvitto för ditt köp:',
        'payment_receipt_package' => 'Paket: :package_name',
        'payment_receipt_price' => 'Pris: :package_price_per_credit/kredit',
        'payment_receipt_total' => 'Totalt: :package_price för :package_number_of_listings krediter',
        'payment_receipt_save' => '(Spara :package_percent_discount%)',
        'payment_receipt_thanks' => 'Tack för din betalning!',
        'payment_receipt_info' => 'Betalningsinformation',
        'payment_receipt_amount' => 'Belopp: :package_price',
        'payment_receipt_invoice' => 'Fakturakod: :invoice_code',
        'payment_receipt_view_invoice' => 'Visa faktura',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hej Admin!',
        'payment_received_message' => 'Betalning mottagen från :account_name:',
        'payment_received_account' => 'Konto: :account_name (:account_email)',
        'payment_received_package' => 'Paket: :package_name',
        'payment_received_price' => 'Pris: :package_price_per_credit/kredit',
        'payment_received_total' => 'Totalt: :package_price för :package_number_of_listings krediter',
        'payment_received_save' => '(Spara :package_percent_discount%)',
        'payment_received_info' => 'Betalningsinformation',
        'payment_received_customer' => 'Kund: :account_name',
        'payment_received_amount' => 'Belopp: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hej :account_name,',
        'invoice_payment_from' => 'Du får detta e-postmeddelande från :site_title',
        'invoice_payment_attached' => 'Fakturan #:invoice_code är bifogad i detta e-postmeddelande.',
        'invoice_payment_view_online' => 'Visa online',
        'invoice_payment_thanks' => 'Tack för din betalning!',
        'invoice_payment_info' => 'Fakturainformation',
        'invoice_payment_code' => 'Fakturakod: :invoice_code',
        'invoice_payment_view' => 'Visa faktura',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hej Admin,',
        'free_credit_claimed_message' => ':account_name har hämtat gratis kredit på :site_title',
        'free_credit_claimed_info' => 'Kontoinformation',
        'free_credit_claimed_name' => 'Namn: :account_name',
        'free_credit_claimed_email' => 'E-post: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hej!',
        'password_reminder_message' => 'Du får detta e-postmeddelande eftersom vi har fått en begäran om återställning av lösenord för ditt konto.',
        'password_reminder_button' => 'Återställ lösenord',
        'password_reminder_no_action' => 'Om du inte begärde en återställning av lösenord behöver du inte göra något mer.',
        'password_reminder_regards' => 'Hälsningar,',
        'password_reminder_trouble' => 'Om du har problem med att klicka på "Återställ lösenord"-knappen, kopiera och klistra in URL:en nedan i din webbläsare: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hej :account_name!',
        'job_alert_hiring' => 'Rekryterar :job_name på :company_name',
        'job_alert_apply_forward' => '👇 Ansök eller Vidarebefordra till en vän: :job_url',
        'job_alert_message' => 'Nya jobbmöjligheter som matchar dina preferenser har publicerats!',
        'job_alert_job_info' => 'Jobb: :job_name',
        'job_alert_company_info' => 'Företag: :company_name',
        'job_alert_view_job' => 'Visa jobb',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Bekräftelse på jobbansökan',
        'job_application_confirmation_greeting' => 'Bästa :job_application_name,',
        'job_application_confirmation_thanks' => 'Tack för ditt intresse för tjänsten som :job_name på :company_name. Vi är glada att bekräfta att din ansökan har skickats in via vårt system.',
        'job_application_confirmation_reviewing' => 'Vårt rekryteringsteam granskar dina kvalifikationer, och vi kommer att kontakta dig om dina färdigheter och erfarenheter matchar kraven för denna roll. Observera att på grund av det stora antalet ansökningar kan denna process ta lite tid.',
        'job_application_confirmation_thanks_again' => 'Tack igen för din ansökan!',
        'job_application_confirmation_regards' => 'Vänliga hälsningar,',
        'job_application_confirmation_team' => ':company_name Team',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hej,',
        'new_job_application_received' => 'Du har fått en ny jobbansökan.',
        'new_job_application_details' => 'Ansökningsdetaljer:',
        'new_job_application_name' => 'Namn: :job_application_name',
        'new_job_application_position' => 'Befattning: :job_application_position',
        'new_job_application_email' => 'E-post: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
