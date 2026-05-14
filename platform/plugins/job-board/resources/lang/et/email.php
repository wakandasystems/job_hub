<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nová žádost o zaměstnání (pro administrátory)',
            'description' => 'E-mailová šablona pro odeslání upozornění administrátorům při příjmu nové žádosti o zaměstnání',
            'subject' => 'Nová žádost o zaměstnání',
        ],
        'employer-new-job-application' => [
            'title' => 'Nová žádost o zaměstnání (pro zaměstnavatele a kolegy)',
            'description' => 'E-mailová šablona pro odeslání upozornění zaměstnavateli a kolegům při příjmu nové žádosti o zaměstnání',
            'subject' => 'Nová žádost o zaměstnání',
        ],
        'new-job-posted' => [
            'title' => 'Nová pracovní nabídka',
            'description' => 'Odeslat e-mail administrátorovi při vložení nové pracovní nabídky',
            'subject' => 'Nová pracovní nabídka byla zveřejněna na {{ site_title }} uživatelem {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Vytvořen nový profil společnosti',
            'description' => 'Odeslat e-mail administrátorovi, když zaměstnavatel vytvoří nový profil společnosti',
            'subject' => 'Nový profil společnosti byl vytvořen na {{ site_title }} uživatelem {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Pracovní nabídka brzy vyprší',
            'description' => 'Odeslat e-mail autorovi, pokud jeho pracovní nabídka vyprší za 3 dny',
            'subject' => 'Vaše pracovní nabídka "{{ job_name }}" vyprší za {{ job_expired_after }} dní',
        ],
        'job-renewed' => [
            'title' => 'Pracovní nabídka obnovena',
            'description' => 'Odeslat e-mail autorovi, když je jeho pracovní nabídka obnovena',
            'subject' => 'Vaše pracovní nabídka "{{ job_name }}" byla automaticky obnovena',
        ],
        'payment-receipt' => [
            'title' => 'Potvrzení o platbě',
            'description' => 'Odeslat upozornění uživateli při zakoupení kreditů',
            'subject' => 'Potvrzení o platbě za balíček {{ package_name }} na {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Účet zaregistrován',
            'description' => 'Odeslat upozornění administrátorovi při registraci nového zaměstnavatele/uchazeče o zaměstnání',
            'subject' => 'Nový {{ account_type }} zaregistrován na {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Potvrzení e-mailu',
            'description' => 'Odeslat e-mail uživateli při registraci účtu k ověření jejich e-mailu',
            'subject' => 'Upozornění na potvrzení e-mailu',
        ],
        'password-reminder' => [
            'title' => 'Obnovení hesla',
            'description' => 'Odeslat e-mail uživateli při žádosti o obnovení hesla',
            'subject' => 'Obnovení hesla',
        ],
        'free-credit-claimed' => [
            'title' => 'Získán bezplatný kredit',
            'description' => 'Odeslat upozornění administrátorovi při získání bezplatného kreditu',
            'subject' => '{{ account_name }} získal bezplatný kredit na {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Platba přijata',
            'description' => 'Odeslat upozornění administrátorovi, když někdo koupí kredity',
            'subject' => 'Platba přijata od {{ account_name }} na {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Detail platby faktury',
            'description' => 'Odeslat upozornění zákazníkovi, který provádí platbu za zveřejnění pracovní nabídky',
            'subject' => 'Platba přijata od {{ account_name }} na {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Nová pracovní nabídka',
            'description' => 'Odeslat e-mail uchazeči o zaměstnání při zveřejnění nové pracovní nabídky',
            'subject' => 'Hledáme {{ job_name }} ve společnosti {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Pracovní nabídka schválena',
            'description' => 'Odeslat e-mail autorovi, když je jeho pracovní nabídka schválena',
            'subject' => 'Vaše pracovní nabídka "{{ job_name }}" byla schválena',
        ],
        'company-approved' => [
            'title' => 'Společnost schválena',
            'description' => 'Odeslat e-mail autorovi, když je jeho společnost schválena',
            'subject' => 'Vaše společnost "{{ company_name }}" byla schválena',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Potvrzení žádosti o zaměstnání',
            'description' => 'Odeslat e-mail uchazeči o zaměstnání, když se přihlásí na pracovní pozici',
            'subject' => 'Potvrzení žádosti o {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Jméno',
        'position' => 'Pozice',
        'email' => 'E-mail',
        'phone' => 'Telefon',
        'summary' => 'Shrnutí',
        'resume' => 'Životopis',
        'cover_letter' => 'Průvodní dopis',
        'job_application' => 'Žádost o zaměstnání',
        'job_name' => 'Název pracovní pozice',
        'job_url' => 'URL pracovní pozice',
        'job_author' => 'Autor pracovní pozice',
        'company_name' => 'Název společnosti',
        'company_url' => 'URL společnosti',
        'employer_name' => 'Jméno zaměstnavatele',
        'job_list' => 'URL seznamu pracovních pozic',
        'job_expired_after' => 'Pracovní pozice vyprší za x dnů',
        'account_name' => 'Název účtu',
        'account_email' => 'E-mail účtu',
        'package_name' => 'Název balíčku',
        'package_price' => 'Cena',
        'package_percent_discount' => 'Procentní sleva',
        'package_number_of_listings' => 'Počet zveřejnění',
        'package_price_per_credit' => 'Cena za kredit',
        'account_type' => 'Typ účtu (zaměstnavatel/uchazeč o zaměstnání)',
        'verify_link' => 'Ověřovací odkaz',
        'reset_link' => 'Odkaz pro obnovení',
        'invoice_code' => 'Kód faktury',
        'invoice_link' => 'Odkaz na fakturu',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Dobrý den, administrátore!',
        'account_registered_new_account' => 'Zaregistroval se nový :account_type:',
        'account_registered_name' => 'Jméno: <strong>:account_name</strong>',
        'account_registered_email' => 'E-mail: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Dobrý den, obdrželi jsme novou žádost o zaměstnání z :site_title!',
        'admin_job_application_name' => 'Jméno: :job_application_name',
        'admin_job_application_position' => 'Pozice: :job_application_position',
        'admin_job_application_email' => 'E-mail: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Shrnutí: :job_application_summary',
        'admin_job_application_resume' => 'Životopis: :job_application_resume',
        'admin_job_application_cover_letter' => 'Průvodní dopis: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Dobrý den, obdrželi jsme novou žádost o zaměstnání z :site_title!',
        'employer_job_application_name' => 'Jméno: :job_application_name',
        'employer_job_application_position' => 'Pozice: :job_application_position',
        'employer_job_application_email' => 'E-mail: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Shrnutí: :job_application_summary',
        'employer_job_application_resume' => 'Životopis: :job_application_resume',
        'employer_job_application_cover_letter' => 'Průvodní dopis: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Společnost schválena',
        'company_approved_greeting' => 'Dobrý den,',
        'company_approved_message' => 'S potěšením vám oznamujeme, že vaše společnost byla schválena a je nyní aktivní na naší platformě.',
        'company_approved_info' => 'Informace o společnosti',
        'company_approved_name' => 'Název: <strong>:company_name</strong>',
        'company_approved_view' => 'Zobrazit',
        'company_approved_here' => 'zde',

        // Confirm email template
        'confirm_email_greeting' => 'Dobrý den!',
        'confirm_email_message' => 'Ověřte prosím svou e-mailovou adresu, abyste měli přístup k této webové stránce. Klikněte na tlačítko níže pro ověření vašeho e-mailu.',
        'confirm_email_button' => 'Ověřit nyní',
        'confirm_email_regards' => 'S pozdravem,',
        'confirm_email_trouble' => 'Pokud máte potíže s kliknutím na tlačítko "Ověřit nyní", zkopírujte a vložte níže uvedenou URL adresu do svého webového prohlížeče: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Pracovní nabídka schválena',
        'job_approved_greeting' => 'Dobrý den :job_author,',
        'job_approved_message' => 'S potěšením vám oznamujeme, že vaše pracovní nabídka byla schválena a je nyní aktivní na naší platformě.',
        'job_approved_info' => 'Informace o pracovní nabídce',
        'job_approved_job_title' => 'Název pozice: <strong>:job_name</strong>',
        'job_approved_view' => 'Zobrazit',
        'job_approved_here' => 'zde',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Dobrý den :job_author!',
        'job_expired_soon_message' => 'Vaše pracovní nabídka <a href=":job_url">:job_name</a> vyprší za :job_expired_after dní.',
        'job_expired_soon_renew' => 'Prosím <a href=":job_list">přejděte sem</a> pro obnovení vaší pracovní nabídky.',

        // Job renewed email template
        'job_renewed_greeting' => 'Dobrý den :job_author!',
        'job_renewed_message' => 'Vaše pracovní nabídka <a href=":job_url">:job_name</a> byla automaticky obnovena.',

        // New job posted email template
        'new_job_posted_title' => 'Nová pracovní nabídka zveřejněna',
        'new_job_posted_admin_greeting' => 'Dobrý den, administrátore,',
        'new_job_posted_message' => 'S potěšením vám oznamujeme, že zaměstnavatel zveřejnil novou pracovní nabídku na naší platformě.',
        'new_job_posted_info' => 'Pracovní nabídka',
        'new_job_posted_employer' => 'Zaměstnavatel: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Název pozice: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Odkaz do administrace',
        'new_job_posted_here' => 'zde',

        // New company profile created email template
        'new_company_profile_title' => 'Vytvořen nový profil společnosti',
        'new_company_profile_admin_greeting' => 'Dobrý den, administrátore!',
        'new_company_profile_message' => 'Nový profil společnosti byl vytvořen uživatelem :employer_name ":company_name"',
        'new_company_profile_info' => 'Informace o společnosti',
        'new_company_profile_employer' => 'Zaměstnavatel: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Název společnosti: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Odkaz do administrace',
        'new_company_profile_here' => 'zde',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Dobrý den :account_name!',
        'payment_receipt_message' => 'Potvrzení o platbě za váš nákup:',
        'payment_receipt_package' => 'Balíček: :package_name',
        'payment_receipt_price' => 'Cena: :package_price_per_credit/kredit',
        'payment_receipt_total' => 'Celkem: :package_price za :package_number_of_listings kreditů',
        'payment_receipt_save' => '(Ušetříte :package_percent_discount%)',
        'payment_receipt_thanks' => 'Děkujeme za vaši platbu!',
        'payment_receipt_info' => 'Informace o platbě',
        'payment_receipt_amount' => 'Částka: :package_price',
        'payment_receipt_invoice' => 'Kód faktury: :invoice_code',
        'payment_receipt_view_invoice' => 'Zobrazit fakturu',

        // Payment received email template
        'payment_received_admin_greeting' => 'Dobrý den, administrátore!',
        'payment_received_message' => 'Platba přijata od :account_name:',
        'payment_received_account' => 'Účet: :account_name (:account_email)',
        'payment_received_package' => 'Balíček: :package_name',
        'payment_received_price' => 'Cena: :package_price_per_credit/kredit',
        'payment_received_total' => 'Celkem: :package_price za :package_number_of_listings kreditů',
        'payment_received_save' => '(Ušetříte :package_percent_discount%)',
        'payment_received_info' => 'Informace o platbě',
        'payment_received_customer' => 'Zákazník: :account_name',
        'payment_received_amount' => 'Částka: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Dobrý den :account_name,',
        'invoice_payment_from' => 'Tento e-mail dostáváte od :site_title',
        'invoice_payment_attached' => 'Faktura #:invoice_code je přiložena k tomuto e-mailu.',
        'invoice_payment_view_online' => 'Zobrazit online',
        'invoice_payment_thanks' => 'Děkujeme za vaši platbu!',
        'invoice_payment_info' => 'Informace o faktuře',
        'invoice_payment_code' => 'Kód faktury: :invoice_code',
        'invoice_payment_view' => 'Zobrazit fakturu',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Dobrý den, administrátore,',
        'free_credit_claimed_message' => ':account_name získal bezplatný kredit na :site_title',
        'free_credit_claimed_info' => 'Informace o účtu',
        'free_credit_claimed_name' => 'Jméno: :account_name',
        'free_credit_claimed_email' => 'E-mail: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Dobrý den!',
        'password_reminder_message' => 'Tento e-mail dostáváte, protože jsme obdrželi žádost o obnovení hesla pro váš účet.',
        'password_reminder_button' => 'Obnovit heslo',
        'password_reminder_no_action' => 'Pokud jste o obnovení hesla nežádali, není třeba žádná další akce.',
        'password_reminder_regards' => 'S pozdravem,',
        'password_reminder_trouble' => 'Pokud máte potíže s kliknutím na tlačítko "Obnovit heslo", zkopírujte a vložte níže uvedenou URL adresu do svého webového prohlížeče: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Dobrý den :account_name!',
        'job_alert_hiring' => 'Hledáme :job_name ve společnosti :company_name',
        'job_alert_apply_forward' => '👇 Přihlaste se nebo pošlete přítel: :job_url',
        'job_alert_message' => 'Byly zveřejněny nové pracovní příležitosti odpovídající vašim preferencím!',
        'job_alert_job_info' => 'Pracovní pozice: :job_name',
        'job_alert_company_info' => 'Společnost: :company_name',
        'job_alert_view_job' => 'Zobrazit pracovní pozici',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Potvrzení žádosti o zaměstnání',
        'job_application_confirmation_greeting' => 'Vážený/á :job_application_name,',
        'job_application_confirmation_thanks' => 'Děkujeme za váš zájem o pozici :job_name ve společnosti :company_name. S potěšením potvrzujeme, že vaše žádost byla úspěšně odeslána přes náš systém.',
        'job_application_confirmation_reviewing' => 'Náš náborový tým prozkoumává vaši kvalifikaci a budeme vás kontaktovat, pokud vaše dovednosti a zkušenosti odpovídají požadavkům pro tuto roli. Upozorňujeme, že vzhledem k velkému počtu žádostí může tento proces nějakou dobu trvat.',
        'job_application_confirmation_thanks_again' => 'Ještě jednou děkujeme za vaši žádost!',
        'job_application_confirmation_regards' => 'S pozdravem,',
        'job_application_confirmation_team' => 'Tým :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Dobrý den,',
        'new_job_application_received' => 'Obdrželi jste novou žádost o zaměstnání.',
        'new_job_application_details' => 'Detaily žádosti:',
        'new_job_application_name' => 'Jméno: :job_application_name',
        'new_job_application_position' => 'Pozice: :job_application_position',
        'new_job_application_email' => 'E-mail: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
