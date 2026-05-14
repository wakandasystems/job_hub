<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nowe zgłoszenie o pracę (dla administratorów)',
            'description' => 'Szablon wiadomości e-mail do wysłania powiadomienia administratorom, gdy system otrzyma nowe zgłoszenie o pracę',
            'subject' => 'Nowe zgłoszenie o pracę',
        ],
        'employer-new-job-application' => [
            'title' => 'Nowe zgłoszenie o pracę (dla pracodawcy i współpracowników)',
            'description' => 'Szablon wiadomości e-mail do wysłania powiadomienia pracodawcy i współpracownikom, gdy system otrzyma nowe zgłoszenie o pracę',
            'subject' => 'Nowe zgłoszenie o pracę',
        ],
        'new-job-posted' => [
            'title' => 'Nowe ogłoszenie o pracę',
            'description' => 'Wyślij e-mail do administratora, gdy zostanie dodane nowe ogłoszenie o pracę',
            'subject' => 'Nowe ogłoszenie o pracę zostało opublikowane na {{ site_title }} przez {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Utworzono nowy profil firmy',
            'description' => 'Wyślij e-mail do administratora, gdy pracodawca utworzy nowy profil firmy',
            'subject' => 'Nowy profil firmy został utworzony na {{ site_title }} przez {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Ogłoszenie wkrótce wygaśnie',
            'description' => 'Wyślij e-mail do autora, jeśli jego ogłoszenie wygaśnie w ciągu następnych 3 dni',
            'subject' => 'Twoje ogłoszenie "{{ job_name }}" wygaśnie za {{ job_expired_after }} dni',
        ],
        'job-renewed' => [
            'title' => 'Ogłoszenie odnowione',
            'description' => 'Wyślij e-mail do autora, gdy jego ogłoszenie zostanie odnowione',
            'subject' => 'Twoje ogłoszenie "{{ job_name }}" zostało automatycznie odnowione',
        ],
        'payment-receipt' => [
            'title' => 'Potwierdzenie płatności',
            'description' => 'Wyślij powiadomienie do użytkownika, gdy kupi kredyty',
            'subject' => 'Potwierdzenie płatności za pakiet {{ package_name }} na {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Konto zarejestrowane',
            'description' => 'Wyślij powiadomienie do administratora, gdy nowy pracodawca/osoba poszukująca pracy się zarejestruje',
            'subject' => 'Nowe konto {{ account_type }} zarejestrowane na {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Potwierdź adres e-mail',
            'description' => 'Wyślij e-mail do użytkownika podczas rejestracji konta w celu weryfikacji adresu e-mail',
            'subject' => 'Powiadomienie o potwierdzeniu adresu e-mail',
        ],
        'password-reminder' => [
            'title' => 'Resetowanie hasła',
            'description' => 'Wyślij e-mail do użytkownika żądającego zresetowania hasła',
            'subject' => 'Resetowanie hasła',
        ],
        'free-credit-claimed' => [
            'title' => 'Odebrany darmowy kredyt',
            'description' => 'Wyślij powiadomienie do administratora, gdy darmowy kredyt zostanie odebrany',
            'subject' => '{{ account_name }} odebrał darmowy kredyt na {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Otrzymano płatność',
            'description' => 'Wyślij powiadomienie do administratora, gdy ktoś kupi kredyty',
            'subject' => 'Otrzymano płatność od {{ account_name }} na {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Szczegóły płatności faktury',
            'description' => 'Wyślij powiadomienie do klienta, który dokonuje płatności za ogłoszenie o pracę',
            'subject' => 'Otrzymano płatność od {{ account_name }} na {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Nowe ogłoszenie o pracę',
            'description' => 'Wyślij e-mail do osoby poszukującej pracy, gdy zostanie dodane nowe ogłoszenie',
            'subject' => 'Zatrudniamy {{ job_name }} w {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Ogłoszenie zatwierdzone',
            'description' => 'Wyślij e-mail do autora, gdy jego ogłoszenie zostanie zatwierdzone',
            'subject' => 'Twoje ogłoszenie "{{ job_name }}" zostało zatwierdzone',
        ],
        'company-approved' => [
            'title' => 'Firma zatwierdzona',
            'description' => 'Wyślij e-mail do autora, gdy jego firma zostanie zatwierdzona',
            'subject' => 'Twoja firma "{{ company_name }}" została zatwierdzona',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Potwierdzenie zgłoszenia o pracę',
            'description' => 'Wyślij e-mail do osoby poszukującej pracy, gdy złoży aplikację o pracę',
            'subject' => 'Potwierdzenie aplikacji na stanowisko {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Imię i nazwisko',
        'position' => 'Stanowisko',
        'email' => 'Adres e-mail',
        'phone' => 'Telefon',
        'summary' => 'Podsumowanie',
        'resume' => 'CV',
        'cover_letter' => 'List motywacyjny',
        'job_application' => 'Zgłoszenie o pracę',
        'job_name' => 'Nazwa stanowiska',
        'job_url' => 'URL ogłoszenia',
        'job_author' => 'Autor ogłoszenia',
        'company_name' => 'Nazwa firmy',
        'company_url' => 'URL firmy',
        'employer_name' => 'Imię pracodawcy',
        'job_list' => 'URL listy ofert pracy',
        'job_expired_after' => 'Ogłoszenie wygaśnie po x dniach',
        'account_name' => 'Nazwa konta',
        'account_email' => 'E-mail konta',
        'package_name' => 'Nazwa pakietu',
        'package_price' => 'Cena',
        'package_percent_discount' => 'Procent rabatu',
        'package_number_of_listings' => 'Liczba ogłoszeń',
        'package_price_per_credit' => 'Cena za kredyt',
        'account_type' => 'Typ konta (pracodawca/osoba poszukująca pracy)',
        'verify_link' => 'Link weryfikacyjny',
        'reset_link' => 'Link resetujący',
        'invoice_code' => 'Kod faktury',
        'invoice_link' => 'Link do faktury',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Witaj Administratorze!',
        'account_registered_new_account' => 'Zarejestrowano nowe konto :account_type:',
        'account_registered_name' => 'Imię: <strong>:account_name</strong>',
        'account_registered_email' => 'E-mail: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Witaj, otrzymaliśmy nowe zgłoszenie o pracę z :site_title!',
        'admin_job_application_name' => 'Imię: :job_application_name',
        'admin_job_application_position' => 'Stanowisko: :job_application_position',
        'admin_job_application_email' => 'E-mail: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Podsumowanie: :job_application_summary',
        'admin_job_application_resume' => 'CV: :job_application_resume',
        'admin_job_application_cover_letter' => 'List motywacyjny: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Witaj, otrzymaliśmy nowe zgłoszenie o pracę z :site_title!',
        'employer_job_application_name' => 'Imię: :job_application_name',
        'employer_job_application_position' => 'Stanowisko: :job_application_position',
        'employer_job_application_email' => 'E-mail: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Podsumowanie: :job_application_summary',
        'employer_job_application_resume' => 'CV: :job_application_resume',
        'employer_job_application_cover_letter' => 'List motywacyjny: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Firma zatwierdzona',
        'company_approved_greeting' => 'Witaj,',
        'company_approved_message' => 'Z przyjemnością informujemy, że Twoja firma została zatwierdzona i jest teraz widoczna na naszej platformie.',
        'company_approved_info' => 'Informacje o firmie',
        'company_approved_name' => 'Nazwa: <strong>:company_name</strong>',
        'company_approved_view' => 'Zobacz',
        'company_approved_here' => 'tutaj',

        // Confirm email template
        'confirm_email_greeting' => 'Witaj!',
        'confirm_email_message' => 'Potwierdź swój adres e-mail, aby uzyskać dostęp do tej witryny. Kliknij poniższy przycisk, aby zweryfikować swój adres e-mail.',
        'confirm_email_button' => 'Zweryfikuj teraz',
        'confirm_email_regards' => 'Pozdrawiam,',
        'confirm_email_trouble' => 'Jeśli masz problem z kliknięciem przycisku "Zweryfikuj teraz", skopiuj i wklej poniższy adres URL do przeglądarki internetowej: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Ogłoszenie zatwierdzone',
        'job_approved_greeting' => 'Witaj :job_author,',
        'job_approved_message' => 'Z przyjemnością informujemy, że Twoje ogłoszenie o pracę zostało zatwierdzone i jest teraz widoczne na naszej platformie.',
        'job_approved_info' => 'Informacje o ogłoszeniu',
        'job_approved_job_title' => 'Tytuł stanowiska: <strong>:job_name</strong>',
        'job_approved_view' => 'Zobacz',
        'job_approved_here' => 'tutaj',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Witaj :job_author!',
        'job_expired_soon_message' => 'Twoje ogłoszenie <a href=":job_url">:job_name</a> wygaśnie za :job_expired_after dni.',
        'job_expired_soon_renew' => 'Przejdź <a href=":job_list">tutaj</a>, aby odnowić swoje ogłoszenie.',

        // Job renewed email template
        'job_renewed_greeting' => 'Witaj :job_author!',
        'job_renewed_message' => 'Twoje ogłoszenie <a href=":job_url">:job_name</a> zostało automatycznie odnowione.',

        // New job posted email template
        'new_job_posted_title' => 'Nowe ogłoszenie o pracę',
        'new_job_posted_admin_greeting' => 'Witaj Administratorze,',
        'new_job_posted_message' => 'Z przyjemnością informujemy, że nowe ogłoszenie o pracę zostało dodane przez pracodawcę na naszej platformie.',
        'new_job_posted_info' => 'Ogłoszenie o pracę',
        'new_job_posted_employer' => 'Pracodawca: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Tytuł stanowiska: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Link do panelu administratora',
        'new_job_posted_here' => 'tutaj',

        // New company profile created email template
        'new_company_profile_title' => 'Utworzono nowy profil firmy',
        'new_company_profile_admin_greeting' => 'Witaj Administratorze!',
        'new_company_profile_message' => 'Nowy profil firmy został utworzony przez :employer_name ":company_name"',
        'new_company_profile_info' => 'Informacje o firmie',
        'new_company_profile_employer' => 'Pracodawca: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Nazwa firmy: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Link do panelu administratora',
        'new_company_profile_here' => 'tutaj',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Witaj :account_name!',
        'payment_receipt_message' => 'Potwierdzenie płatności za Twój zakup:',
        'payment_receipt_package' => 'Pakiet: :package_name',
        'payment_receipt_price' => 'Cena: :package_price_per_credit/kredyt',
        'payment_receipt_total' => 'Razem: :package_price za :package_number_of_listings kredytów',
        'payment_receipt_save' => '(Oszczędność :package_percent_discount%)',
        'payment_receipt_thanks' => 'Dziękujemy za płatność!',
        'payment_receipt_info' => 'Informacje o płatności',
        'payment_receipt_amount' => 'Kwota: :package_price',
        'payment_receipt_invoice' => 'Kod faktury: :invoice_code',
        'payment_receipt_view_invoice' => 'Zobacz fakturę',

        // Payment received email template
        'payment_received_admin_greeting' => 'Witaj Administratorze!',
        'payment_received_message' => 'Otrzymano płatność od :account_name:',
        'payment_received_account' => 'Konto: :account_name (:account_email)',
        'payment_received_package' => 'Pakiet: :package_name',
        'payment_received_price' => 'Cena: :package_price_per_credit/kredyt',
        'payment_received_total' => 'Razem: :package_price za :package_number_of_listings kredytów',
        'payment_received_save' => '(Oszczędność :package_percent_discount%)',
        'payment_received_info' => 'Informacje o płatności',
        'payment_received_customer' => 'Klient: :account_name',
        'payment_received_amount' => 'Kwota: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Witaj :account_name,',
        'invoice_payment_from' => 'Otrzymujesz e-mail z :site_title',
        'invoice_payment_attached' => 'Faktura #:invoice_code jest załączona do tego e-maila.',
        'invoice_payment_view_online' => 'Zobacz online',
        'invoice_payment_thanks' => 'Dziękujemy za płatność!',
        'invoice_payment_info' => 'Informacje o fakturze',
        'invoice_payment_code' => 'Kod faktury: :invoice_code',
        'invoice_payment_view' => 'Zobacz fakturę',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Witaj Administratorze,',
        'free_credit_claimed_message' => ':account_name odebrał darmowy kredyt na :site_title',
        'free_credit_claimed_info' => 'Informacje o koncie',
        'free_credit_claimed_name' => 'Imię: :account_name',
        'free_credit_claimed_email' => 'E-mail: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Witaj!',
        'password_reminder_message' => 'Otrzymujesz ten e-mail, ponieważ otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta.',
        'password_reminder_button' => 'Zresetuj hasło',
        'password_reminder_no_action' => 'Jeśli nie prosiłeś o zresetowanie hasła, nie musisz podejmować żadnych działań.',
        'password_reminder_regards' => 'Pozdrawiam,',
        'password_reminder_trouble' => 'Jeśli masz problem z kliknięciem przycisku "Zresetuj hasło", skopiuj i wklej poniższy adres URL do przeglądarki internetowej: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Witaj :account_name!',
        'job_alert_hiring' => 'Zatrudniamy :job_name w :company_name',
        'job_alert_apply_forward' => '👇 Aplikuj lub przekaż znajomemu: :job_url',
        'job_alert_message' => 'Opublikowano nowe oferty pracy zgodne z Twoimi preferencjami!',
        'job_alert_job_info' => 'Praca: :job_name',
        'job_alert_company_info' => 'Firma: :company_name',
        'job_alert_view_job' => 'Zobacz ofertę',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Potwierdzenie zgłoszenia o pracę',
        'job_application_confirmation_greeting' => 'Szanowny/a :job_application_name,',
        'job_application_confirmation_thanks' => 'Dziękujemy za zainteresowanie stanowiskiem :job_name w :company_name. Z przyjemnością potwierdzamy, że Twoje zgłoszenie zostało pomyślnie przesłane przez nasz system.',
        'job_application_confirmation_reviewing' => 'Nasz zespół rekrutacyjny przegląda Twoje kwalifikacje i skontaktujemy się z Tobą, jeśli Twoje umiejętności i doświadczenie będą zgodne z wymaganiami na to stanowisko. Należy pamiętać, że ze względu na dużą liczbę zgłoszeń proces ten może zająć trochę czasu.',
        'job_application_confirmation_thanks_again' => 'Dziękujemy jeszcze raz za aplikację!',
        'job_application_confirmation_regards' => 'Z poważaniem,',
        'job_application_confirmation_team' => 'Zespół :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Witaj,',
        'new_job_application_received' => 'Otrzymano nowe zgłoszenie o pracę.',
        'new_job_application_details' => 'Szczegóły zgłoszenia:',
        'new_job_application_name' => 'Imię: :job_application_name',
        'new_job_application_position' => 'Stanowisko: :job_application_position',
        'new_job_application_email' => 'E-mail: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
