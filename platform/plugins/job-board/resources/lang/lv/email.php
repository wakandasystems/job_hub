<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Jauns darba pieteikums (administratoriem)',
            'description' => 'E-pasta veidne, lai nosūtītu paziņojumu administratoriem, kad sistēma saņem jaunu darba pieteikumu',
            'subject' => 'Jauns darba pieteikums',
        ],
        'employer-new-job-application' => [
            'title' => 'Jauns darba pieteikums (darba devējam un kolēģiem)',
            'description' => 'E-pasta veidne, lai nosūtītu paziņojumu darba devējam un kolēģiem, kad sistēma saņem jaunu darba pieteikumu',
            'subject' => 'Jauns darba pieteikums',
        ],
        'new-job-posted' => [
            'title' => 'Jauns darba sludinājums publicēts',
            'description' => 'Nosūtīt e-pastu administratoram, kad tiek publicēts jauns darba sludinājums',
            'subject' => 'Jauns darba sludinājums publicēts vietnē {{ site_title }} no {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Izveidots jauns uzņēmuma profils',
            'description' => 'Nosūtīt e-pastu administratoram, kad darba devējs izveido jaunu uzņēmuma profilu',
            'subject' => 'Jauns uzņēmuma profils izveidots vietnē {{ site_title }} no {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Darba sludinājums drīz beigsies',
            'description' => 'Nosūtīt e-pastu autoram, ja viņa darba sludinājums beigsies nākamajos 3 dienās',
            'subject' => 'Jūsu darba sludinājums "{{ job_name }}" beigsies pēc {{ job_expired_after }} dienām',
        ],
        'job-renewed' => [
            'title' => 'Darba sludinājums atjaunināts',
            'description' => 'Nosūtīt e-pastu autoram, kad viņa darba sludinājums ir atjaunināts',
            'subject' => 'Jūsu darba sludinājums "{{ job_name }}" ir automātiski atjaunināts',
        ],
        'payment-receipt' => [
            'title' => 'Maksājuma kvīts',
            'description' => 'Nosūtīt paziņojumu lietotājam, kad viņš iegādājas kredītus',
            'subject' => 'Maksājuma kvīts par paketi {{ package_name }} vietnē {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Konts reģistrēts',
            'description' => 'Nosūtīt paziņojumu administratoram, kad reģistrējas jauns darba devējs/darba meklētājs',
            'subject' => 'Jauns {{ account_type }} reģistrēts vietnē {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Apstiprināt e-pastu',
            'description' => 'Nosūtīt e-pastu lietotājam, kad viņš reģistrē kontu, lai verificētu e-pastu',
            'subject' => 'E-pasta apstiprinājuma paziņojums',
        ],
        'password-reminder' => [
            'title' => 'Atiestatīt paroli',
            'description' => 'Nosūtīt e-pastu lietotājam, kad pieprasa paroles atiestatīšanu',
            'subject' => 'Paroles atiestatīšana',
        ],
        'free-credit-claimed' => [
            'title' => 'Bezmaksas kredīts saņemts',
            'description' => 'Nosūtīt paziņojumu administratoram, kad tiek saņemts bezmaksas kredīts',
            'subject' => '{{ account_name }} ir saņēmis bezmaksas kredītu vietnē {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Maksājums saņemts',
            'description' => 'Nosūtīt paziņojumu administratoram, kad kāds iegādājas kredītus',
            'subject' => 'Maksājums saņemts no {{ account_name }} vietnē {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Rēķina maksājuma detaļas',
            'description' => 'Nosūtīt paziņojumu klientam, kurš veic darba sludinājuma publicēšanas maksājumu',
            'subject' => 'Maksājums saņemts no {{ account_name }} vietnē {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Jauns darba sludinājums publicēts',
            'description' => 'Nosūtīt e-pastu darba meklētājam, kad tiek publicēts jauns darba sludinājums',
            'subject' => 'Pieņem darbā {{ job_name }} uzņēmumā {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Darba sludinājums apstiprināts',
            'description' => 'Nosūtīt e-pastu autoram, kad viņa darba sludinājums ir apstiprināts',
            'subject' => 'Jūsu darba sludinājums "{{ job_name }}" ir apstiprināts',
        ],
        'company-approved' => [
            'title' => 'Uzņēmums apstiprināts',
            'description' => 'Nosūtīt e-pastu autoram, kad viņa uzņēmums ir apstiprināts',
            'subject' => 'Jūsu uzņēmums "{{ company_name }}" ir apstiprināts',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Darba pieteikuma apstiprinājums',
            'description' => 'Nosūtīt e-pastu darba meklētājam, kad viņš piesakās darbam',
            'subject' => 'Pieteikuma apstiprinājums par {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Vārds',
        'position' => 'Amats',
        'email' => 'E-pasts',
        'phone' => 'Tālrunis',
        'summary' => 'Kopsavilkums',
        'resume' => 'CV',
        'cover_letter' => 'Motivācijas vēstule',
        'job_application' => 'Darba pieteikums',
        'job_name' => 'Darba nosaukums',
        'job_url' => 'Darba URL',
        'job_author' => 'Darba autors',
        'company_name' => 'Uzņēmuma nosaukums',
        'company_url' => 'Uzņēmuma URL',
        'employer_name' => 'Darba devēja vārds',
        'job_list' => 'Darba sludinājumu saraksta URL',
        'job_expired_after' => 'Darbs beigsies pēc x dienām',
        'account_name' => 'Konta nosaukums',
        'account_email' => 'Konta e-pasts',
        'package_name' => 'Paketes nosaukums',
        'package_price' => 'Cena',
        'package_percent_discount' => 'Procentuālā atlaide',
        'package_number_of_listings' => 'Sludinājumu skaits',
        'package_price_per_credit' => 'Cena par kredītu',
        'account_type' => 'Konta tips (darba devējs/darba meklētājs)',
        'verify_link' => 'Verifikācijas saite',
        'reset_link' => 'Atiestatīšanas saite',
        'invoice_code' => 'Rēķina kods',
        'invoice_link' => 'Rēķina saite',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Sveiki, Administrator!',
        'account_registered_new_account' => 'Jauns :account_type reģistrējies:',
        'account_registered_name' => 'Vārds: <strong>:account_name</strong>',
        'account_registered_email' => 'E-pasts: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Sveiki! Mēs saņēmām jaunu darba pieteikumu no :site_title!',
        'admin_job_application_name' => 'Vārds: :job_application_name',
        'admin_job_application_position' => 'Amats: :job_application_position',
        'admin_job_application_email' => 'E-pasts: :job_application_email',
        'admin_job_application_phone' => 'Tālrunis: :job_application_phone',
        'admin_job_application_summary' => 'Kopsavilkums: :job_application_summary',
        'admin_job_application_resume' => 'CV: :job_application_resume',
        'admin_job_application_cover_letter' => 'Motivācijas vēstule: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Sveiki! Mēs saņēmām jaunu darba pieteikumu no :site_title!',
        'employer_job_application_name' => 'Vārds: :job_application_name',
        'employer_job_application_position' => 'Amats: :job_application_position',
        'employer_job_application_email' => 'E-pasts: :job_application_email',
        'employer_job_application_phone' => 'Tālrunis: :job_application_phone',
        'employer_job_application_summary' => 'Kopsavilkums: :job_application_summary',
        'employer_job_application_resume' => 'CV: :job_application_resume',
        'employer_job_application_cover_letter' => 'Motivācijas vēstule: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Uzņēmums apstiprināts',
        'company_approved_greeting' => 'Sveiki,',
        'company_approved_message' => 'Mums ir prieks paziņot, ka jūsu uzņēmums ir apstiprināts un tagad ir pieejams mūsu platformā.',
        'company_approved_info' => 'Uzņēmuma informācija',
        'company_approved_name' => 'Nosaukums: <strong>:company_name</strong>',
        'company_approved_view' => 'Skatīt',
        'company_approved_here' => 'šeit',

        // Confirm email template
        'confirm_email_greeting' => 'Sveiki!',
        'confirm_email_message' => 'Lūdzu, verificējiet savu e-pasta adresi, lai piekļūtu šai vietnei. Noklikšķiniet uz zemāk esošās pogas, lai verificētu savu e-pastu.',
        'confirm_email_button' => 'Verificēt tagad',
        'confirm_email_regards' => 'Ar cieņu,',
        'confirm_email_trouble' => 'Ja jums ir problēmas noklikšķināt uz pogas "Verificēt tagad", nokopējiet un ielīmējiet zemāk esošo URL savā tīmekļa pārlūkā: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Darba sludinājums apstiprināts',
        'job_approved_greeting' => 'Sveiki, :job_author!',
        'job_approved_message' => 'Mums ir prieks paziņot, ka jūsu darba sludinājums ir apstiprināts un tagad ir pieejams mūsu platformā.',
        'job_approved_info' => 'Darba informācija',
        'job_approved_job_title' => 'Darba nosaukums: <strong>:job_name</strong>',
        'job_approved_view' => 'Skatīt',
        'job_approved_here' => 'šeit',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Sveiki, :job_author!',
        'job_expired_soon_message' => 'Jūsu darba sludinājums <a href=":job_url">:job_name</a> beigsies pēc :job_expired_after dienām.',
        'job_expired_soon_renew' => 'Lūdzu, <a href=":job_list">dodieties šeit</a>, lai atjauninātu savu darba sludinājumu.',

        // Job renewed email template
        'job_renewed_greeting' => 'Sveiki, :job_author!',
        'job_renewed_message' => 'Jūsu darba sludinājums <a href=":job_url">:job_name</a> ir automātiski atjaunināts.',

        // New job posted email template
        'new_job_posted_title' => 'Jauns darba sludinājums publicēts',
        'new_job_posted_admin_greeting' => 'Sveiki, Administrator!',
        'new_job_posted_message' => 'Mums ir prieks paziņot, ka jauns darba sludinājums ir publicēts darba devēja mūsu platformā.',
        'new_job_posted_info' => 'Darba sludinājums',
        'new_job_posted_employer' => 'Darba devējs: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Darba nosaukums: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Administratora paneļa saite',
        'new_job_posted_here' => 'šeit',

        // New company profile created email template
        'new_company_profile_title' => 'Izveidots jauns uzņēmuma profils',
        'new_company_profile_admin_greeting' => 'Sveiki, Administrator!',
        'new_company_profile_message' => 'Jauns uzņēmuma profils ir izveidots no :employer_name ":company_name"',
        'new_company_profile_info' => 'Uzņēmuma informācija',
        'new_company_profile_employer' => 'Darba devējs: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Uzņēmuma nosaukums: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Administratora paneļa saite',
        'new_company_profile_here' => 'šeit',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Sveiki, :account_name!',
        'payment_receipt_message' => 'Maksājuma kvīts par jūsu pirkumu:',
        'payment_receipt_package' => 'Pakete: :package_name',
        'payment_receipt_price' => 'Cena: :package_price_per_credit/kredīts',
        'payment_receipt_total' => 'Kopā: :package_price par :package_number_of_listings kredītiem',
        'payment_receipt_save' => '(Ietaupījums :package_percent_discount%)',
        'payment_receipt_thanks' => 'Paldies par jūsu maksājumu!',
        'payment_receipt_info' => 'Maksājuma informācija',
        'payment_receipt_amount' => 'Summa: :package_price',
        'payment_receipt_invoice' => 'Rēķina kods: :invoice_code',
        'payment_receipt_view_invoice' => 'Skatīt rēķinu',

        // Payment received email template
        'payment_received_admin_greeting' => 'Sveiki, Administrator!',
        'payment_received_message' => 'Maksājums saņemts no :account_name:',
        'payment_received_account' => 'Konts: :account_name (:account_email)',
        'payment_received_package' => 'Pakete: :package_name',
        'payment_received_price' => 'Cena: :package_price_per_credit/kredīts',
        'payment_received_total' => 'Kopā: :package_price par :package_number_of_listings kredītiem',
        'payment_received_save' => '(Ietaupījums :package_percent_discount%)',
        'payment_received_info' => 'Maksājuma informācija',
        'payment_received_customer' => 'Klients: :account_name',
        'payment_received_amount' => 'Summa: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Sveiki, :account_name!',
        'invoice_payment_from' => 'Jūs saņemat e-pastu no :site_title',
        'invoice_payment_attached' => 'Rēķins #:invoice_code ir pievienots šim e-pastam.',
        'invoice_payment_view_online' => 'Skatīt tiešsaistē',
        'invoice_payment_thanks' => 'Paldies par jūsu maksājumu!',
        'invoice_payment_info' => 'Rēķina informācija',
        'invoice_payment_code' => 'Rēķina kods: :invoice_code',
        'invoice_payment_view' => 'Skatīt rēķinu',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Sveiki, Administrator!',
        'free_credit_claimed_message' => ':account_name ir saņēmis bezmaksas kredītu vietnē :site_title',
        'free_credit_claimed_info' => 'Konta informācija',
        'free_credit_claimed_name' => 'Vārds: :account_name',
        'free_credit_claimed_email' => 'E-pasts: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Sveiki!',
        'password_reminder_message' => 'Jūs saņemat šo e-pastu, jo mēs saņēmām paroles atiestatīšanas pieprasījumu jūsu kontam.',
        'password_reminder_button' => 'Atiestatīt paroli',
        'password_reminder_no_action' => 'Ja jūs nepieprasījāt paroles atiestatīšanu, nav nepieciešama turpmāka rīcība.',
        'password_reminder_regards' => 'Ar cieņu,',
        'password_reminder_trouble' => 'Ja jums ir problēmas noklikšķināt uz pogas "Atiestatīt paroli", nokopējiet un ielīmējiet zemāk esošo URL savā tīmekļa pārlūkā: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Sveiki, :account_name!',
        'job_alert_hiring' => 'Pieņem darbā :job_name uzņēmumā :company_name',
        'job_alert_apply_forward' => '👇 Pieteikties vai pārsūtīt draugam: :job_url',
        'job_alert_message' => 'Jauni darba piedāvājumi, kas atbilst jūsu vēlmēm, ir publicēti!',
        'job_alert_job_info' => 'Darbs: :job_name',
        'job_alert_company_info' => 'Uzņēmums: :company_name',
        'job_alert_view_job' => 'Skatīt darbu',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Darba pieteikuma apstiprinājums',
        'job_application_confirmation_greeting' => 'Cienījamais :job_application_name!',
        'job_application_confirmation_thanks' => 'Paldies par jūsu interesi par :job_name amatu uzņēmumā :company_name. Mums ir prieks apstiprināt, ka jūsu pieteikums ir veiksmīgi iesniegts mūsu sistēmā.',
        'job_application_confirmation_reviewing' => 'Mūsu personāla atlases komanda pārskata jūsu kvalifikāciju, un mēs ar jums sazināsimies, ja jūsu prasmes un pieredze atbilst šīs lomas prasībām. Lūdzu, ņemiet vērā, ka ņemot vērā lielo pieteikumu skaitu, šis process var aizņemt kādu laiku.',
        'job_application_confirmation_thanks_again' => 'Vēlreiz paldies par pieteikšanos!',
        'job_application_confirmation_regards' => 'Ar cieņu,',
        'job_application_confirmation_team' => ':company_name komanda',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Sveiki,',
        'new_job_application_received' => 'Jūs esat saņēmis jaunu darba pieteikumu.',
        'new_job_application_details' => 'Pieteikuma detaļas:',
        'new_job_application_name' => 'Vārds: :job_application_name',
        'new_job_application_position' => 'Amats: :job_application_position',
        'new_job_application_email' => 'E-pasts: :job_application_email',
        'new_job_application_phone' => 'Tālrunis: :job_application_phone',
    ],
];
