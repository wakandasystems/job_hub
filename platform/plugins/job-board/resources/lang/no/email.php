<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Ny jobbsøknad (til administratorer)',
            'description' => 'E-postmal for å sende varsel til administratorer når systemet får ny jobbsøknad',
            'subject' => 'Ny jobbsøknad',
        ],
        'employer-new-job-application' => [
            'title' => 'Ny jobbsøknad (til arbeidsgiver og kolleger)',
            'description' => 'E-postmal for å sende varsel til arbeidsgiver og kolleger når systemet får ny jobbsøknad',
            'subject' => 'Ny jobbsøknad',
        ],
        'new-job-posted' => [
            'title' => 'Ny jobb publisert',
            'description' => 'Send e-post til admin når en ny jobb publiseres',
            'subject' => 'Ny jobb er publisert på {{ site_title }} av {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Ny selskapsprofil opprettet',
            'description' => 'Send e-post til admin når en arbeidsgiver oppretter en ny selskapsprofil',
            'subject' => 'Ny selskapsprofil er opprettet på {{ site_title }} av {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Jobb utløper snart',
            'description' => 'Send e-post til forfatteren hvis jobben deres utløper om 3 dager',
            'subject' => 'Din jobb "{{ job_name }}" utløper om {{ job_expired_after }} dager',
        ],
        'job-renewed' => [
            'title' => 'Jobb fornyet',
            'description' => 'Send e-post til forfatteren når jobben deres fornyes',
            'subject' => 'Din jobb "{{ job_name }}" er fornyet automatisk',
        ],
        'payment-receipt' => [
            'title' => 'Betalingskvittering',
            'description' => 'Send en varsling til bruker når de kjøper kreditter',
            'subject' => 'Betalingskvittering for pakke {{ package_name }} på {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Konto registrert',
            'description' => 'Send en varsling til admin når en ny arbeidsgiver/jobbsøker registreres',
            'subject' => 'Ny {{ account_type }} registrert på {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Bekreft e-post',
            'description' => 'Send e-post til bruker når de registrerer en konto for å bekrefte e-posten',
            'subject' => 'Bekreft e-post',
        ],
        'password-reminder' => [
            'title' => 'Tilbakestill passord',
            'description' => 'Send e-post til bruker ved forespørsel om tilbakestilling av passord',
            'subject' => 'Tilbakestill passord',
        ],
        'free-credit-claimed' => [
            'title' => 'Gratis kreditt hentet',
            'description' => 'Send en varsling til admin når gratis kreditt blir hentet',
            'subject' => '{{ account_name }} har hentet gratis kreditt på {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Betaling mottatt',
            'description' => 'Send en varsling til admin når noen kjøper kreditter',
            'subject' => 'Betaling mottatt fra {{ account_name }} på {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Fakturabetalingsdetaljer',
            'description' => 'Send en varsling til kunden som foretar jobbpubliseringsbetalingen',
            'subject' => 'Betaling mottatt fra {{ account_name }} på {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Ny jobb publisert',
            'description' => 'Send e-post til jobbsøker når en ny jobb publiseres',
            'subject' => 'Ansetter {{ job_name }} hos {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Jobb godkjent',
            'description' => 'Send e-post til forfatteren når jobben deres godkjennes',
            'subject' => 'Din jobb "{{ job_name }}" er godkjent',
        ],
        'company-approved' => [
            'title' => 'Selskap godkjent',
            'description' => 'Send e-post til forfatteren når selskapet deres godkjennes',
            'subject' => 'Ditt selskap "{{ company_name }}" er godkjent',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Jobbsøknadsbekreftelse',
            'description' => 'Send e-post til jobbsøker når de søkte på en jobb',
            'subject' => 'Søknadsbekreftelse for {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Navn',
        'position' => 'Stilling',
        'email' => 'E-post',
        'phone' => 'Telefon',
        'summary' => 'Sammendrag',
        'resume' => 'CV',
        'cover_letter' => 'Søknadsbrev',
        'job_application' => 'Jobbsøknad',
        'job_name' => 'Jobbnavn',
        'job_url' => 'Jobb-URL',
        'job_author' => 'Jobbforfatter',
        'company_name' => 'Selskapsnavn',
        'company_url' => 'Selskaps-URL',
        'employer_name' => 'Arbeidsgivernavn',
        'job_list' => 'Jobbliste-URL',
        'job_expired_after' => 'Jobb utløper etter x dager',
        'account_name' => 'Kontonavn',
        'account_email' => 'Konto-e-post',
        'package_name' => 'Pakkenavn',
        'package_price' => 'Pris',
        'package_percent_discount' => 'Prosentrabatt',
        'package_number_of_listings' => 'Antall oppføringer',
        'package_price_per_credit' => 'Pris per kreditt',
        'account_type' => 'Kontotype (arbeidsgiver/jobbsøker)',
        'verify_link' => 'Verifiseringslenke',
        'reset_link' => 'Tilbakestillingslenke',
        'invoice_code' => 'Fakturakode',
        'invoice_link' => 'Fakturalenke',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hei administrator!',
        'account_registered_new_account' => 'En ny :account_type registrert:',
        'account_registered_name' => 'Navn: <strong>:account_name</strong>',
        'account_registered_email' => 'E-post: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hei, Vi mottok en ny jobbsøknad fra :site_title!',
        'admin_job_application_name' => 'Navn: :job_application_name',
        'admin_job_application_position' => 'Stilling: :job_application_position',
        'admin_job_application_email' => 'E-post: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Sammendrag: :job_application_summary',
        'admin_job_application_resume' => 'CV: :job_application_resume',
        'admin_job_application_cover_letter' => 'Søknadsbrev: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hei, Vi mottok en ny jobbsøknad fra :site_title!',
        'employer_job_application_name' => 'Navn: :job_application_name',
        'employer_job_application_position' => 'Stilling: :job_application_position',
        'employer_job_application_email' => 'E-post: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Sammendrag: :job_application_summary',
        'employer_job_application_resume' => 'CV: :job_application_resume',
        'employer_job_application_cover_letter' => 'Søknadsbrev: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Selskap godkjent',
        'company_approved_greeting' => 'Hei,',
        'company_approved_message' => 'Vi er glade for å informere deg om at selskapet ditt er godkjent og nå tilgjengelig på plattformen vår.',
        'company_approved_info' => 'Selskapsinformasjon',
        'company_approved_name' => 'Navn: <strong>:company_name</strong>',
        'company_approved_view' => 'Vis',
        'company_approved_here' => 'her',

        // Confirm email template
        'confirm_email_greeting' => 'Hei!',
        'confirm_email_message' => 'Vennligst bekreft e-postadressen din for å få tilgang til dette nettstedet. Klikk på knappen nedenfor for å bekrefte e-posten din.',
        'confirm_email_button' => 'Bekreft nå',
        'confirm_email_regards' => 'Vennlig hilsen,',
        'confirm_email_trouble' => 'Hvis du har problemer med å klikke på "Bekreft nå"-knappen, kopier og lim inn URL-en nedenfor i nettleseren din: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Jobb godkjent',
        'job_approved_greeting' => 'Hei :job_author,',
        'job_approved_message' => 'Vi er glade for å informere deg om at jobboppføringen din er godkjent og nå tilgjengelig på plattformen vår.',
        'job_approved_info' => 'Jobbinformasjon',
        'job_approved_job_title' => 'Jobbtittel: <strong>:job_name</strong>',
        'job_approved_view' => 'Vis',
        'job_approved_here' => 'her',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hei :job_author!',
        'job_expired_soon_message' => 'Din jobb <a href=":job_url">:job_name</a> utløper om :job_expired_after dager.',
        'job_expired_soon_renew' => 'Vennligst <a href=":job_list">gå hit</a> for å fornye jobben din.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hei :job_author!',
        'job_renewed_message' => 'Din jobb <a href=":job_url">:job_name</a> er fornyet automatisk.',

        // New job posted email template
        'new_job_posted_title' => 'Ny jobb publisert',
        'new_job_posted_admin_greeting' => 'Hei administrator,',
        'new_job_posted_message' => 'Vi er glade for å informere deg om at en ny jobboppføring er publisert av en arbeidsgiver på plattformen vår.',
        'new_job_posted_info' => 'Jobbinnlegg',
        'new_job_posted_employer' => 'Arbeidsgiver: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Jobbtittel: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Administratorpanellenke',
        'new_job_posted_here' => 'her',

        // New company profile created email template
        'new_company_profile_title' => 'Ny selskapsprofil opprettet',
        'new_company_profile_admin_greeting' => 'Hei administrator!',
        'new_company_profile_message' => 'En ny selskapsprofil er opprettet av :employer_name ":company_name"',
        'new_company_profile_info' => 'Selskapsinformasjon',
        'new_company_profile_employer' => 'Arbeidsgiver: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Selskapsnavn: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Administratorpanellenke',
        'new_company_profile_here' => 'her',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hei :account_name!',
        'payment_receipt_message' => 'Betalingskvittering for ditt kjøp:',
        'payment_receipt_package' => 'Pakke: :package_name',
        'payment_receipt_price' => 'Pris: :package_price_per_credit/kreditt',
        'payment_receipt_total' => 'Totalt: :package_price for :package_number_of_listings kreditter',
        'payment_receipt_save' => '(Spar :package_percent_discount%)',
        'payment_receipt_thanks' => 'Takk for betalingen!',
        'payment_receipt_info' => 'Betalingsinformasjon',
        'payment_receipt_amount' => 'Beløp: :package_price',
        'payment_receipt_invoice' => 'Fakturakode: :invoice_code',
        'payment_receipt_view_invoice' => 'Vis faktura',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hei administrator!',
        'payment_received_message' => 'Betaling mottatt fra :account_name:',
        'payment_received_account' => 'Konto: :account_name (:account_email)',
        'payment_received_package' => 'Pakke: :package_name',
        'payment_received_price' => 'Pris: :package_price_per_credit/kreditt',
        'payment_received_total' => 'Totalt: :package_price for :package_number_of_listings kreditter',
        'payment_received_save' => '(Spar :package_percent_discount%)',
        'payment_received_info' => 'Betalingsinformasjon',
        'payment_received_customer' => 'Kunde: :account_name',
        'payment_received_amount' => 'Beløp: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hei :account_name,',
        'invoice_payment_from' => 'Du mottar e-post fra :site_title',
        'invoice_payment_attached' => 'Fakturaen #:invoice_code er vedlagt denne e-posten.',
        'invoice_payment_view_online' => 'Vis online',
        'invoice_payment_thanks' => 'Takk for betalingen!',
        'invoice_payment_info' => 'Fakturainformasjon',
        'invoice_payment_code' => 'Fakturakode: :invoice_code',
        'invoice_payment_view' => 'Vis faktura',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hei administrator,',
        'free_credit_claimed_message' => ':account_name har hentet gratis kreditt på :site_title',
        'free_credit_claimed_info' => 'Kontoinformasjon',
        'free_credit_claimed_name' => 'Navn: :account_name',
        'free_credit_claimed_email' => 'E-post: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hei!',
        'password_reminder_message' => 'Du mottar denne e-posten fordi vi mottok en forespørsel om tilbakestilling av passord for kontoen din.',
        'password_reminder_button' => 'Tilbakestill passord',
        'password_reminder_no_action' => 'Hvis du ikke ba om tilbakestilling av passord, er ingen ytterligere handling nødvendig.',
        'password_reminder_regards' => 'Vennlig hilsen,',
        'password_reminder_trouble' => 'Hvis du har problemer med å klikke på "Tilbakestill passord"-knappen, kopier og lim inn URL-en nedenfor i nettleseren din: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hei :account_name!',
        'job_alert_hiring' => 'Ansetter :job_name hos :company_name',
        'job_alert_apply_forward' => '👇 Søk eller videreformidle til en venn: :job_url',
        'job_alert_message' => 'Nye jobbmuligheter som matcher dine preferanser er publisert!',
        'job_alert_job_info' => 'Jobb: :job_name',
        'job_alert_company_info' => 'Selskap: :company_name',
        'job_alert_view_job' => 'Vis jobb',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Jobbsøknadsbekreftelse',
        'job_application_confirmation_greeting' => 'Kjære :job_application_name,',
        'job_application_confirmation_thanks' => 'Takk for din interesse i :job_name-stillingen hos :company_name. Vi er glade for å bekrefte at søknaden din er sendt inn gjennom systemet vårt.',
        'job_application_confirmation_reviewing' => 'Rekrutteringsteamet vårt gjennomgår kvalifikasjonene dine, og vi vil kontakte deg hvis ferdighetene og erfaringen din matcher kravene for denne rollen. Vær oppmerksom på at på grunn av høyt antall søknader, kan denne prosessen ta litt tid.',
        'job_application_confirmation_thanks_again' => 'Takk igjen for søknaden!',
        'job_application_confirmation_regards' => 'Vennlig hilsen,',
        'job_application_confirmation_team' => ':company_name-teamet',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hei,',
        'new_job_application_received' => 'Du har mottatt en ny jobbsøknad.',
        'new_job_application_details' => 'Søknadsdetaljer:',
        'new_job_application_name' => 'Navn: :job_application_name',
        'new_job_application_position' => 'Stilling: :job_application_position',
        'new_job_application_email' => 'E-post: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
