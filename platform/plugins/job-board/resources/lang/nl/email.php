<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nieuwe sollicitatie (naar beheerders)',
            'description' => 'E-mailsjabloon om beheerders te informeren wanneer het systeem een nieuwe sollicitatie ontvangt',
            'subject' => 'Nieuwe sollicitatie',
        ],
        'employer-new-job-application' => [
            'title' => 'Nieuwe sollicitatie (naar werkgever en collega\'s)',
            'description' => 'E-mailsjabloon om werkgever en collega\'s te informeren wanneer het systeem een nieuwe sollicitatie ontvangt',
            'subject' => 'Nieuwe sollicitatie',
        ],
        'new-job-posted' => [
            'title' => 'Nieuwe vacature geplaatst',
            'description' => 'E-mail naar beheerder sturen wanneer een nieuwe vacature is geplaatst',
            'subject' => 'Nieuwe vacature is geplaatst op {{ site_title }} door {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Nieuw bedrijfsprofiel aangemaakt',
            'description' => 'E-mail naar beheerder sturen wanneer een werkgever een nieuw bedrijfsprofiel aanmaakt',
            'subject' => 'Nieuw bedrijfsprofiel is aangemaakt op {{ site_title }} door {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Vacature verloopt binnenkort',
            'description' => 'E-mail naar auteur sturen als hun vacature binnen 3 dagen verloopt',
            'subject' => 'Uw vacature "{{ job_name }}" verloopt over {{ job_expired_after }} dagen',
        ],
        'job-renewed' => [
            'title' => 'Vacature verlengd',
            'description' => 'E-mail naar auteur sturen wanneer hun vacature is verlengd',
            'subject' => 'Uw vacature "{{ job_name }}" is automatisch verlengd',
        ],
        'payment-receipt' => [
            'title' => 'Betalingsbewijs',
            'description' => 'Melding naar gebruiker sturen wanneer deze credits koopt',
            'subject' => 'Betalingsbewijs voor pakket {{ package_name }} op {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Account geregistreerd',
            'description' => 'Melding naar beheerder sturen wanneer een nieuwe werkgever/werkzoekende zich registreert',
            'subject' => 'Nieuwe {{ account_type }} geregistreerd op {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'E-mail bevestigen',
            'description' => 'E-mail naar gebruiker sturen bij registratie om hun e-mailadres te verifiëren',
            'subject' => 'E-mail bevestigingsmelding',
        ],
        'password-reminder' => [
            'title' => 'Wachtwoord resetten',
            'description' => 'E-mail naar gebruiker sturen bij aanvraag wachtwoord reset',
            'subject' => 'Wachtwoord resetten',
        ],
        'free-credit-claimed' => [
            'title' => 'Gratis credit geclaimd',
            'description' => 'Melding naar beheerder sturen wanneer gratis credit is geclaimd',
            'subject' => '{{ account_name }} heeft gratis credit geclaimd op {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Betaling ontvangen',
            'description' => 'Melding naar beheerder sturen wanneer iemand credits koopt',
            'subject' => 'Betaling ontvangen van {{ account_name }} op {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Factuur betalingsdetails',
            'description' => 'Melding sturen naar klant die betaling voor vacatureplaatsing heeft gedaan',
            'subject' => 'Betaling ontvangen van {{ account_name }} op {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Nieuwe vacature geplaatst',
            'description' => 'E-mail naar werkzoekende sturen wanneer een nieuwe vacature is geplaatst',
            'subject' => 'Werven {{ job_name }} bij {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Vacature goedgekeurd',
            'description' => 'E-mail naar auteur sturen wanneer hun vacature is goedgekeurd',
            'subject' => 'Uw vacature "{{ job_name }}" is goedgekeurd',
        ],
        'company-approved' => [
            'title' => 'Bedrijf goedgekeurd',
            'description' => 'E-mail naar auteur sturen wanneer hun bedrijf is goedgekeurd',
            'subject' => 'Uw bedrijf "{{ company_name }}" is goedgekeurd',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Sollicitatiebevestiging',
            'description' => 'E-mail naar werkzoekende sturen wanneer deze op een vacature heeft gesolliciteerd',
            'subject' => 'Sollicitatiebevestiging voor {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Naam',
        'position' => 'Functie',
        'email' => 'E-mailadres',
        'phone' => 'Telefoonnummer',
        'summary' => 'Samenvatting',
        'resume' => 'CV',
        'cover_letter' => 'Motivatiebrief',
        'job_application' => 'Sollicitatie',
        'job_name' => 'Vacaturenaam',
        'job_url' => 'Vacature URL',
        'job_author' => 'Vacatureauteur',
        'company_name' => 'Bedrijfsnaam',
        'company_url' => 'Bedrijf URL',
        'employer_name' => 'Werkgeversnaam',
        'job_list' => 'Vacaturelijst URL',
        'job_expired_after' => 'Vacature verloopt na x dagen',
        'account_name' => 'Accountnaam',
        'account_email' => 'Account e-mailadres',
        'package_name' => 'Naam van pakket',
        'package_price' => 'Prijs',
        'package_percent_discount' => 'Percentage korting',
        'package_number_of_listings' => 'Aantal advertenties',
        'package_price_per_credit' => 'Prijs per credit',
        'account_type' => 'Accounttype (werkgever/werkzoekende)',
        'verify_link' => 'Verificatielink',
        'reset_link' => 'Resetlink',
        'invoice_code' => 'Factuurcode',
        'invoice_link' => 'Factuurlink',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hallo beheerder!',
        'account_registered_new_account' => 'Een nieuwe :account_type heeft zich geregistreerd:',
        'account_registered_name' => 'Naam: <strong>:account_name</strong>',
        'account_registered_email' => 'E-mailadres: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hallo, we hebben een nieuwe sollicitatie ontvangen van :site_title!',
        'admin_job_application_name' => 'Naam: :job_application_name',
        'admin_job_application_position' => 'Functie: :job_application_position',
        'admin_job_application_email' => 'E-mailadres: :job_application_email',
        'admin_job_application_phone' => 'Telefoonnummer: :job_application_phone',
        'admin_job_application_summary' => 'Samenvatting: :job_application_summary',
        'admin_job_application_resume' => 'CV: :job_application_resume',
        'admin_job_application_cover_letter' => 'Motivatiebrief: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hallo, we hebben een nieuwe sollicitatie ontvangen van :site_title!',
        'employer_job_application_name' => 'Naam: :job_application_name',
        'employer_job_application_position' => 'Functie: :job_application_position',
        'employer_job_application_email' => 'E-mailadres: :job_application_email',
        'employer_job_application_phone' => 'Telefoonnummer: :job_application_phone',
        'employer_job_application_summary' => 'Samenvatting: :job_application_summary',
        'employer_job_application_resume' => 'CV: :job_application_resume',
        'employer_job_application_cover_letter' => 'Motivatiebrief: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Bedrijf goedgekeurd',
        'company_approved_greeting' => 'Hallo,',
        'company_approved_message' => 'We zijn verheugd u te kunnen meedelen dat uw bedrijf is goedgekeurd en nu live staat op ons platform.',
        'company_approved_info' => 'Bedrijfsinformatie',
        'company_approved_name' => 'Naam: <strong>:company_name</strong>',
        'company_approved_view' => 'Bekijk',
        'company_approved_here' => 'hier',

        // Confirm email template
        'confirm_email_greeting' => 'Hallo!',
        'confirm_email_message' => 'Verifieer uw e-mailadres om toegang te krijgen tot deze website. Klik op de onderstaande knop om uw e-mailadres te verifiëren.',
        'confirm_email_button' => 'Nu verifiëren',
        'confirm_email_regards' => 'Met vriendelijke groet,',
        'confirm_email_trouble' => 'Als u problemen heeft met het klikken op de knop "Nu verifiëren", kopieer en plak dan de onderstaande URL in uw webbrowser: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Vacature goedgekeurd',
        'job_approved_greeting' => 'Hallo :job_author,',
        'job_approved_message' => 'We zijn verheugd u te kunnen meedelen dat uw vacature is goedgekeurd en nu live staat op ons platform.',
        'job_approved_info' => 'Vacatureinformatie',
        'job_approved_job_title' => 'Vacaturetitel: <strong>:job_name</strong>',
        'job_approved_view' => 'Bekijk',
        'job_approved_here' => 'hier',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hallo :job_author!',
        'job_expired_soon_message' => 'Uw vacature <a href=":job_url">:job_name</a> verloopt over :job_expired_after dagen.',
        'job_expired_soon_renew' => '<a href=":job_list">Ga hier naartoe</a> om uw vacature te verlengen.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hallo :job_author!',
        'job_renewed_message' => 'Uw vacature <a href=":job_url">:job_name</a> is automatisch verlengd.',

        // New job posted email template
        'new_job_posted_title' => 'Nieuwe vacature geplaatst',
        'new_job_posted_admin_greeting' => 'Hallo beheerder,',
        'new_job_posted_message' => 'We zijn verheugd u te kunnen meedelen dat een nieuwe vacature is geplaatst door een werkgever op ons platform.',
        'new_job_posted_info' => 'Vacatureplaatsing',
        'new_job_posted_employer' => 'Werkgever: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Vacaturetitel: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Beheerpaneel link',
        'new_job_posted_here' => 'hier',

        // New company profile created email template
        'new_company_profile_title' => 'Nieuw bedrijfsprofiel aangemaakt',
        'new_company_profile_admin_greeting' => 'Hallo beheerder!',
        'new_company_profile_message' => 'Een nieuw bedrijfsprofiel is aangemaakt door :employer_name ":company_name"',
        'new_company_profile_info' => 'Bedrijfsinformatie',
        'new_company_profile_employer' => 'Werkgever: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Bedrijfsnaam: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Beheerpaneel link',
        'new_company_profile_here' => 'hier',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hallo :account_name!',
        'payment_receipt_message' => 'Betalingsbewijs voor uw aankoop:',
        'payment_receipt_package' => 'Pakket: :package_name',
        'payment_receipt_price' => 'Prijs: :package_price_per_credit/credit',
        'payment_receipt_total' => 'Totaal: :package_price voor :package_number_of_listings credits',
        'payment_receipt_save' => '(Bespaar :package_percent_discount%)',
        'payment_receipt_thanks' => 'Bedankt voor uw betaling!',
        'payment_receipt_info' => 'Betalingsinformatie',
        'payment_receipt_amount' => 'Bedrag: :package_price',
        'payment_receipt_invoice' => 'Factuurcode: :invoice_code',
        'payment_receipt_view_invoice' => 'Factuur bekijken',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hallo beheerder!',
        'payment_received_message' => 'Betaling ontvangen van :account_name:',
        'payment_received_account' => 'Account: :account_name (:account_email)',
        'payment_received_package' => 'Pakket: :package_name',
        'payment_received_price' => 'Prijs: :package_price_per_credit/credit',
        'payment_received_total' => 'Totaal: :package_price voor :package_number_of_listings credits',
        'payment_received_save' => '(Bespaar :package_percent_discount%)',
        'payment_received_info' => 'Betalingsinformatie',
        'payment_received_customer' => 'Klant: :account_name',
        'payment_received_amount' => 'Bedrag: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hallo :account_name,',
        'invoice_payment_from' => 'U ontvangt deze e-mail van :site_title',
        'invoice_payment_attached' => 'Factuur #:invoice_code is bijgevoegd bij deze e-mail.',
        'invoice_payment_view_online' => 'Online bekijken',
        'invoice_payment_thanks' => 'Bedankt voor uw betaling!',
        'invoice_payment_info' => 'Factuurinformatie',
        'invoice_payment_code' => 'Factuurcode: :invoice_code',
        'invoice_payment_view' => 'Factuur bekijken',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hallo beheerder,',
        'free_credit_claimed_message' => ':account_name heeft gratis credit geclaimd op :site_title',
        'free_credit_claimed_info' => 'Accountinformatie',
        'free_credit_claimed_name' => 'Naam: :account_name',
        'free_credit_claimed_email' => 'E-mailadres: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hallo!',
        'password_reminder_message' => 'U ontvangt deze e-mail omdat we een verzoek hebben ontvangen om uw wachtwoord te resetten.',
        'password_reminder_button' => 'Wachtwoord resetten',
        'password_reminder_no_action' => 'Als u geen wachtwoordreset heeft aangevraagd, hoeft u verder niets te doen.',
        'password_reminder_regards' => 'Met vriendelijke groet,',
        'password_reminder_trouble' => 'Als u problemen heeft met het klikken op de knop "Wachtwoord resetten", kopieer en plak dan de onderstaande URL in uw webbrowser: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hallo :account_name!',
        'job_alert_hiring' => 'Werven :job_name bij :company_name',
        'job_alert_apply_forward' => '👇 Solliciteer of stuur door naar een vriend: :job_url',
        'job_alert_message' => 'Nieuwe vacatures die aan uw voorkeuren voldoen zijn geplaatst!',
        'job_alert_job_info' => 'Vacature: :job_name',
        'job_alert_company_info' => 'Bedrijf: :company_name',
        'job_alert_view_job' => 'Vacature bekijken',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Sollicitatiebevestiging',
        'job_application_confirmation_greeting' => 'Beste :job_application_name,',
        'job_application_confirmation_thanks' => 'Bedankt voor uw interesse in de functie :job_name bij :company_name. We bevestigen graag dat uw sollicitatie succesvol is ingediend via ons systeem.',
        'job_application_confirmation_reviewing' => 'Ons wervingsteam beoordeelt uw kwalificaties en we nemen contact met u op als uw vaardigheden en ervaring overeenkomen met de vereisten voor deze functie. Houd er rekening mee dat dit proces enige tijd kan duren vanwege het grote aantal sollicitaties.',
        'job_application_confirmation_thanks_again' => 'Nogmaals bedankt voor uw sollicitatie!',
        'job_application_confirmation_regards' => 'Met vriendelijke groet,',
        'job_application_confirmation_team' => ':company_name Team',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hallo,',
        'new_job_application_received' => 'U heeft een nieuwe sollicitatie ontvangen.',
        'new_job_application_details' => 'Sollicitatiedetails:',
        'new_job_application_name' => 'Naam: :job_application_name',
        'new_job_application_position' => 'Functie: :job_application_position',
        'new_job_application_email' => 'E-mailadres: :job_application_email',
        'new_job_application_phone' => 'Telefoonnummer: :job_application_phone',
    ],
];
