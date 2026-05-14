<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nauja darbo paraiška (administratoriams)',
            'description' => 'El. pašto šablonas, skirtas išsiųsti pranešimą administratoriams, kai sistema gauna naują darbo paraišką',
            'subject' => 'Nauja darbo paraiška',
        ],
        'employer-new-job-application' => [
            'title' => 'Nauja darbo paraiška (darbdaviui ir kolegoms)',
            'description' => 'El. pašto šablonas, skirtas išsiųsti pranešimą darbdaviui ir kolegoms, kai sistema gauna naują darbo paraišką',
            'subject' => 'Nauja darbo paraiška',
        ],
        'new-job-posted' => [
            'title' => 'Paskelbtas naujas darbas',
            'description' => 'Siųsti el. laišką administratoriui, kai paskelbiamas naujas darbas',
            'subject' => 'Naują darbą {{ site_title }} paskelbė {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Sukurtas naujas įmonės profilis',
            'description' => 'Siųsti el. laišką administratoriui, kai darbdavys sukuria naują įmonės profilį',
            'subject' => 'Naują įmonės profilį {{ site_title }} sukūrė {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Darbo skelbimas greitai baigs galioti',
            'description' => 'Siųsti el. laišką autoriui, jei jo darbo skelbimas baigs galioti po 3 dienų',
            'subject' => 'Jūsų darbo skelbimas "{{ job_name }}" baigs galioti po {{ job_expired_after }} dienų',
        ],
        'job-renewed' => [
            'title' => 'Darbo skelbimas atnaujintas',
            'description' => 'Siųsti el. laišką autoriui, kai jo darbo skelbimas atnaujinamas',
            'subject' => 'Jūsų darbo skelbimas "{{ job_name }}" buvo automatiškai atnaujintas',
        ],
        'payment-receipt' => [
            'title' => 'Mokėjimo kvitas',
            'description' => 'Siųsti pranešimą vartotojui, kai jis perka kreditų',
            'subject' => 'Mokėjimo kvitas už {{ package_name }} paketą {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Paskyra užregistruota',
            'description' => 'Siųsti pranešimą administratoriui, kai užsiregistruoja naujas darbdavys/darbo ieškotojas',
            'subject' => 'Naujas {{ account_type }} užsiregistravo {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Patvirtinti el. paštą',
            'description' => 'Siųsti el. laišką vartotojui, kai jis užsiregistruoja paskyrą, kad patvirtintų savo el. paštą',
            'subject' => 'El. pašto patvirtinimo pranešimas',
        ],
        'password-reminder' => [
            'title' => 'Slaptažodžio atstatymas',
            'description' => 'Siųsti el. laišką vartotojui, kai jis prašo atstatyti slaptažodį',
            'subject' => 'Slaptažodžio atstatymas',
        ],
        'free-credit-claimed' => [
            'title' => 'Nemokamas kreditas pareikalautas',
            'description' => 'Siųsti pranešimą administratoriui, kai pareikalautas nemokamas kreditas',
            'subject' => '{{ account_name }} pareikalavo nemokamo kredito {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Mokėjimas gautas',
            'description' => 'Siųsti pranešimą administratoriui, kai kas nors perka kreditų',
            'subject' => 'Mokėjimas gautas iš {{ account_name }} {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Sąskaitos faktūros mokėjimo informacija',
            'description' => 'Siųsti pranešimą klientui, kuris atlieka darbo skelbimo mokėjimą',
            'subject' => 'Mokėjimas gautas iš {{ account_name }} {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Paskelbtas naujas darbas',
            'description' => 'Siųsti el. laišką darbo ieškotojui, kai paskelbiamas naujas darbas',
            'subject' => 'Ieškoma {{ job_name }} {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Darbas patvirtintas',
            'description' => 'Siųsti el. laišką autoriui, kai jo darbas patvirtinamas',
            'subject' => 'Jūsų darbo skelbimas "{{ job_name }}" buvo patvirtintas',
        ],
        'company-approved' => [
            'title' => 'Įmonė patvirtinta',
            'description' => 'Siųsti el. laišką autoriui, kai jo įmonė patvirtinama',
            'subject' => 'Jūsų įmonė "{{ company_name }}" buvo patvirtinta',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Darbo paraiškos patvirtinimas',
            'description' => 'Siųsti el. laišką darbo ieškotojui, kai jis pateikė paraišką darbui',
            'subject' => 'Paraiškos patvirtinimas {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Vardas',
        'position' => 'Pareigos',
        'email' => 'El. paštas',
        'phone' => 'Telefonas',
        'summary' => 'Santrauka',
        'resume' => 'Gyvenimo aprašymas',
        'cover_letter' => 'Motyvacinis laiškas',
        'job_application' => 'Darbo paraiška',
        'job_name' => 'Darbo pavadinimas',
        'job_url' => 'Darbo URL',
        'job_author' => 'Darbo autorius',
        'company_name' => 'Įmonės pavadinimas',
        'company_url' => 'Įmonės URL',
        'employer_name' => 'Darbdavio vardas',
        'job_list' => 'Darbų sąrašo URL',
        'job_expired_after' => 'Darbas baigs galioti po x dienų',
        'account_name' => 'Paskyros pavadinimas',
        'account_email' => 'Paskyros el. paštas',
        'package_name' => 'Paketo pavadinimas',
        'package_price' => 'Kaina',
        'package_percent_discount' => 'Nuolaidos procentas',
        'package_number_of_listings' => 'Skelbimų skaičius',
        'package_price_per_credit' => 'Kaina už kreditą',
        'account_type' => 'Paskyros tipas (darbdavys/darbo ieškotojas)',
        'verify_link' => 'Patvirtinimo nuoroda',
        'reset_link' => 'Atstatymo nuoroda',
        'invoice_code' => 'Sąskaitos faktūros kodas',
        'invoice_link' => 'Sąskaitos faktūros nuoroda',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Sveiki Administratoriau!',
        'account_registered_new_account' => 'Užsiregistravo naujas :account_type:',
        'account_registered_name' => 'Vardas: <strong>:account_name</strong>',
        'account_registered_email' => 'El. paštas: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Sveiki, gavome naują darbo paraišką iš :site_title!',
        'admin_job_application_name' => 'Vardas: :job_application_name',
        'admin_job_application_position' => 'Pareigos: :job_application_position',
        'admin_job_application_email' => 'El. paštas: :job_application_email',
        'admin_job_application_phone' => 'Telefonas: :job_application_phone',
        'admin_job_application_summary' => 'Santrauka: :job_application_summary',
        'admin_job_application_resume' => 'Gyvenimo aprašymas: :job_application_resume',
        'admin_job_application_cover_letter' => 'Motyvacinis laiškas: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Sveiki, gavome naują darbo paraišką iš :site_title!',
        'employer_job_application_name' => 'Vardas: :job_application_name',
        'employer_job_application_position' => 'Pareigos: :job_application_position',
        'employer_job_application_email' => 'El. paštas: :job_application_email',
        'employer_job_application_phone' => 'Telefonas: :job_application_phone',
        'employer_job_application_summary' => 'Santrauka: :job_application_summary',
        'employer_job_application_resume' => 'Gyvenimo aprašymas: :job_application_resume',
        'employer_job_application_cover_letter' => 'Motyvacinis laiškas: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Įmonė patvirtinta',
        'company_approved_greeting' => 'Sveiki,',
        'company_approved_message' => 'Džiaugiamės pranešdami, kad jūsų įmonė buvo patvirtinta ir dabar veikia mūsų platformoje.',
        'company_approved_info' => 'Įmonės informacija',
        'company_approved_name' => 'Pavadinimas: <strong>:company_name</strong>',
        'company_approved_view' => 'Peržiūrėti',
        'company_approved_here' => 'čia',

        // Confirm email template
        'confirm_email_greeting' => 'Sveiki!',
        'confirm_email_message' => 'Patvirtinkite savo el. pašto adresą, kad galėtumėte pasiekti šią svetainę. Spustelėkite žemiau esantį mygtuką, kad patvirtintumėte savo el. paštą.',
        'confirm_email_button' => 'Patvirtinti dabar',
        'confirm_email_regards' => 'Pagarbiai,',
        'confirm_email_trouble' => 'Jei kyla problemų spustelėjus mygtuką "Patvirtinti dabar", nukopijuokite ir įklijuokite žemiau esantį URL į savo žiniatinklio naršyklę: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Darbas patvirtintas',
        'job_approved_greeting' => 'Sveiki :job_author,',
        'job_approved_message' => 'Džiaugiamės pranešdami, kad jūsų darbo skelbimas buvo patvirtintas ir dabar veikia mūsų platformoje.',
        'job_approved_info' => 'Darbo informacija',
        'job_approved_job_title' => 'Darbo pavadinimas: <strong>:job_name</strong>',
        'job_approved_view' => 'Peržiūrėti',
        'job_approved_here' => 'čia',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Sveiki :job_author!',
        'job_expired_soon_message' => 'Jūsų darbas <a href=":job_url">:job_name</a> baigs galioti po :job_expired_after dienų.',
        'job_expired_soon_renew' => '<a href=":job_list">Eikite čia</a>, kad atnaujintumėte savo darbą.',

        // Job renewed email template
        'job_renewed_greeting' => 'Sveiki :job_author!',
        'job_renewed_message' => 'Jūsų darbas <a href=":job_url">:job_name</a> buvo automatiškai atnaujintas.',

        // New job posted email template
        'new_job_posted_title' => 'Paskelbtas naujas darbas',
        'new_job_posted_admin_greeting' => 'Sveiki Administratoriau,',
        'new_job_posted_message' => 'Džiaugiamės pranešdami, kad darbdavys paskelbė naują darbo skelbimą mūsų platformoje.',
        'new_job_posted_info' => 'Darbo skelbimas',
        'new_job_posted_employer' => 'Darbdavys: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Darbo pavadinimas: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Administravimo skydelio nuoroda',
        'new_job_posted_here' => 'čia',

        // New company profile created email template
        'new_company_profile_title' => 'Sukurtas naujas įmonės profilis',
        'new_company_profile_admin_greeting' => 'Sveiki Administratoriau!',
        'new_company_profile_message' => ':employer_name sukūrė naują įmonės profilį ":company_name"',
        'new_company_profile_info' => 'Įmonės informacija',
        'new_company_profile_employer' => 'Darbdavys: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Įmonės pavadinimas: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Administravimo skydelio nuoroda',
        'new_company_profile_here' => 'čia',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Sveiki :account_name!',
        'payment_receipt_message' => 'Mokėjimo kvitas už jūsų pirkimą:',
        'payment_receipt_package' => 'Paketas: :package_name',
        'payment_receipt_price' => 'Kaina: :package_price_per_credit/kreditas',
        'payment_receipt_total' => 'Iš viso: :package_price už :package_number_of_listings kreditų',
        'payment_receipt_save' => '(Sutaupykite :package_percent_discount%)',
        'payment_receipt_thanks' => 'Dėkojame už mokėjimą!',
        'payment_receipt_info' => 'Mokėjimo informacija',
        'payment_receipt_amount' => 'Suma: :package_price',
        'payment_receipt_invoice' => 'Sąskaitos faktūros kodas: :invoice_code',
        'payment_receipt_view_invoice' => 'Peržiūrėti sąskaitą faktūrą',

        // Payment received email template
        'payment_received_admin_greeting' => 'Sveiki Administratoriau!',
        'payment_received_message' => 'Mokėjimas gautas iš :account_name:',
        'payment_received_account' => 'Paskyra: :account_name (:account_email)',
        'payment_received_package' => 'Paketas: :package_name',
        'payment_received_price' => 'Kaina: :package_price_per_credit/kreditas',
        'payment_received_total' => 'Iš viso: :package_price už :package_number_of_listings kreditų',
        'payment_received_save' => '(Sutaupykite :package_percent_discount%)',
        'payment_received_info' => 'Mokėjimo informacija',
        'payment_received_customer' => 'Klientas: :account_name',
        'payment_received_amount' => 'Suma: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Sveiki :account_name,',
        'invoice_payment_from' => 'Gaunat el. laišką iš :site_title',
        'invoice_payment_attached' => 'Sąskaita faktūra #:invoice_code pridėta prie šio el. laiško.',
        'invoice_payment_view_online' => 'Peržiūrėti internete',
        'invoice_payment_thanks' => 'Dėkojame už mokėjimą!',
        'invoice_payment_info' => 'Sąskaitos faktūros informacija',
        'invoice_payment_code' => 'Sąskaitos faktūros kodas: :invoice_code',
        'invoice_payment_view' => 'Peržiūrėti sąskaitą faktūrą',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Sveiki Administratoriau,',
        'free_credit_claimed_message' => ':account_name pareikalavo nemokamo kredito :site_title',
        'free_credit_claimed_info' => 'Paskyros informacija',
        'free_credit_claimed_name' => 'Vardas: :account_name',
        'free_credit_claimed_email' => 'El. paštas: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Sveiki!',
        'password_reminder_message' => 'Gaunat šį el. laišką, nes gavome slaptažodžio atstatymo užklausą jūsų paskyrai.',
        'password_reminder_button' => 'Atstatyti slaptažodį',
        'password_reminder_no_action' => 'Jei neprašėte atstatyti slaptažodžio, jokių veiksmų atlikti nereikia.',
        'password_reminder_regards' => 'Pagarbiai,',
        'password_reminder_trouble' => 'Jei kyla problemų spustelėjus mygtuką "Atstatyti slaptažodį", nukopijuokite ir įklijuokite žemiau esantį URL į savo žiniatinklio naršyklę: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Sveiki :account_name!',
        'job_alert_hiring' => 'Ieškoma :job_name :company_name',
        'job_alert_apply_forward' => '👇 Pateikite paraišką arba persiųskite draugui: :job_url',
        'job_alert_message' => 'Paskelbtos naujos darbo galimybės, atitinkančios jūsų pageidavimus!',
        'job_alert_job_info' => 'Darbas: :job_name',
        'job_alert_company_info' => 'Įmonė: :company_name',
        'job_alert_view_job' => 'Peržiūrėti darbą',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Darbo paraiškos patvirtinimas',
        'job_application_confirmation_greeting' => 'Gerb. :job_application_name,',
        'job_application_confirmation_thanks' => 'Dėkojame už susidomėjimą :job_name pareigomis :company_name. Džiaugiamės patvirtindami, kad jūsų paraiška buvo sėkmingai pateikta per mūsų sistemą.',
        'job_application_confirmation_reviewing' => 'Mūsų įdarbinimo komanda peržiūri jūsų kvalifikaciją, ir susisieksime su jumis, jei jūsų įgūdžiai ir patirtis atitiks šios pareigų reikalavimus. Atkreipkite dėmesį, kad dėl didelio paraiškų skaičiaus šis procesas gali užtrukti.',
        'job_application_confirmation_thanks_again' => 'Dar kartą dėkojame už paraišką!',
        'job_application_confirmation_regards' => 'Pagarbiai,',
        'job_application_confirmation_team' => ':company_name komanda',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Sveiki,',
        'new_job_application_received' => 'Gavote naują darbo paraišką.',
        'new_job_application_details' => 'Paraiškos informacija:',
        'new_job_application_name' => 'Vardas: :job_application_name',
        'new_job_application_position' => 'Pareigos: :job_application_position',
        'new_job_application_email' => 'El. paštas: :job_application_email',
        'new_job_application_phone' => 'Telefonas: :job_application_phone',
    ],
];
