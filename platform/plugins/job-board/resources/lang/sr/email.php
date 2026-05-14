<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nova prijava za posao (za administratore)',
            'description' => 'Email šablon za slanje obaveštenja administratorima kada sistem dobije novu prijavu za posao',
            'subject' => 'Nova prijava za posao',
        ],
        'employer-new-job-application' => [
            'title' => 'Nova prijava za posao (za poslodavca i kolege)',
            'description' => 'Email šablon za slanje obaveštenja poslodavcu i kolegama kada sistem dobije novu prijavu za posao',
            'subject' => 'Nova prijava za posao',
        ],
        'new-job-posted' => [
            'title' => 'Novi posao objavljen',
            'description' => 'Pošalji email administratoru kada je novi posao objavljen',
            'subject' => 'Novi posao je objavljen na {{ site_title }} od strane {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Novi profil kompanije kreiran',
            'description' => 'Pošalji email administratoru kada poslodavac kreira novi profil kompanije',
            'subject' => 'Novi profil kompanije je kreiran na {{ site_title }} od strane {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Posao uskoro ističe',
            'description' => 'Pošalji email autoru ako će njihov posao isteći u naredna 3 dana',
            'subject' => 'Vaš posao "{{ job_name }}" će isteći za {{ job_expired_after }} dana',
        ],
        'job-renewed' => [
            'title' => 'Posao obnovljen',
            'description' => 'Pošalji email autoru kada je njihov posao obnovljen',
            'subject' => 'Vaš posao "{{ job_name }}" je automatski obnovljen',
        ],
        'payment-receipt' => [
            'title' => 'Potvrda o plaćanju',
            'description' => 'Pošalji obaveštenje korisniku kada kupuje kredite',
            'subject' => 'Potvrda o plaćanju za paket {{ package_name }} na {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Nalog registrovan',
            'description' => 'Pošalji obaveštenje administratoru kada se novi poslodavac/tražilac posla registruje',
            'subject' => 'Novi {{ account_type }} registrovan na {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Potvrda email adrese',
            'description' => 'Pošalji email korisniku kada registruje nalog da bi potvrdio svoju email adresu',
            'subject' => 'Obaveštenje o potvrdi email adrese',
        ],
        'password-reminder' => [
            'title' => 'Resetovanje lozinke',
            'description' => 'Pošalji email korisniku kada zahteva resetovanje lozinke',
            'subject' => 'Resetovanje lozinke',
        ],
        'free-credit-claimed' => [
            'title' => 'Besplatni kredit preuzet',
            'description' => 'Pošalji obaveštenje administratoru kada je besplatni kredit preuzet',
            'subject' => '{{ account_name }} je preuzeo besplatni kredit na {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Plaćanje primljeno',
            'description' => 'Pošalji obaveštenje administratoru kada neko kupi kredite',
            'subject' => 'Plaćanje primljeno od {{ account_name }} na {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Detalji plaćanja fakture',
            'description' => 'Pošalji obaveštenje kupcu koji vrši plaćanje za objavu posla',
            'subject' => 'Plaćanje primljeno od {{ account_name }} na {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Novi posao objavljen',
            'description' => 'Pošalji email tražiocu posla kada je novi posao objavljen',
            'subject' => 'Zapošljavanje {{ job_name }} u {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Posao odobren',
            'description' => 'Pošalji email autoru kada je njihov posao odobren',
            'subject' => 'Vaš posao "{{ job_name }}" je odobren',
        ],
        'company-approved' => [
            'title' => 'Kompanija odobrena',
            'description' => 'Pošalji email autoru kada je njihova kompanija odobrena',
            'subject' => 'Vaša kompanija "{{ company_name }}" je odobrena',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Potvrda prijave za posao',
            'description' => 'Pošalji email tražiocu posla kada se prijavi za posao',
            'subject' => 'Potvrda prijave za {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Ime',
        'position' => 'Pozicija',
        'email' => 'Email',
        'phone' => 'Telefon',
        'summary' => 'Rezime',
        'resume' => 'Biografija',
        'cover_letter' => 'Propratno pismo',
        'job_application' => 'Prijava za posao',
        'job_name' => 'Naziv posla',
        'job_url' => 'URL posla',
        'job_author' => 'Autor posla',
        'company_name' => 'Naziv kompanije',
        'company_url' => 'URL kompanije',
        'employer_name' => 'Ime poslodavca',
        'job_list' => 'URL liste poslova',
        'job_expired_after' => 'Posao ističe nakon x dana',
        'account_name' => 'Ime naloga',
        'account_email' => 'Email naloga',
        'package_name' => 'Naziv paketa',
        'package_price' => 'Cena',
        'package_percent_discount' => 'Procenat popusta',
        'package_number_of_listings' => 'Broj oglasa',
        'package_price_per_credit' => 'Cena po kreditu',
        'account_type' => 'Tip naloga (poslodavac/tražilac posla)',
        'verify_link' => 'Link za potvrdu',
        'reset_link' => 'Link za resetovanje',
        'invoice_code' => 'Kod fakture',
        'invoice_link' => 'Link fakture',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Zdravo Admin!',
        'account_registered_new_account' => 'Novi :account_type registrovan:',
        'account_registered_name' => 'Ime: <strong>:account_name</strong>',
        'account_registered_email' => 'Email: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Zdravo, primili smo novu prijavu za posao sa :site_title!',
        'admin_job_application_name' => 'Ime: :job_application_name',
        'admin_job_application_position' => 'Pozicija: :job_application_position',
        'admin_job_application_email' => 'Email: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Rezime: :job_application_summary',
        'admin_job_application_resume' => 'Biografija: :job_application_resume',
        'admin_job_application_cover_letter' => 'Propratno pismo: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Zdravo, primili smo novu prijavu za posao sa :site_title!',
        'employer_job_application_name' => 'Ime: :job_application_name',
        'employer_job_application_position' => 'Pozicija: :job_application_position',
        'employer_job_application_email' => 'Email: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Rezime: :job_application_summary',
        'employer_job_application_resume' => 'Biografija: :job_application_resume',
        'employer_job_application_cover_letter' => 'Propratno pismo: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Kompanija odobrena',
        'company_approved_greeting' => 'Zdravo,',
        'company_approved_message' => 'Zadovoljstvo nam je da vas obavestimo da je vaša kompanija odobrena i sada je dostupna na našoj platformi.',
        'company_approved_info' => 'Informacije o kompaniji',
        'company_approved_name' => 'Naziv: <strong>:company_name</strong>',
        'company_approved_view' => 'Pogledaj',
        'company_approved_here' => 'ovde',

        // Confirm email template
        'confirm_email_greeting' => 'Zdravo!',
        'confirm_email_message' => 'Molimo vas da potvrdite svoju email adresu kako biste pristupili ovoj veb stranici. Kliknite na dugme ispod da potvrdite svoj email.',
        'confirm_email_button' => 'Potvrdi sada',
        'confirm_email_regards' => 'Srdačan pozdrav,',
        'confirm_email_trouble' => 'Ako imate problema sa klikom na dugme "Potvrdi sada", kopirajte i nalepite URL ispod u vaš veb pretraživač: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Posao odobren',
        'job_approved_greeting' => 'Zdravo :job_author,',
        'job_approved_message' => 'Zadovoljstvo nam je da vas obavestimo da je vaš oglas za posao odobren i sada je dostupan na našoj platformi.',
        'job_approved_info' => 'Informacije o poslu',
        'job_approved_job_title' => 'Naziv posla: <strong>:job_name</strong>',
        'job_approved_view' => 'Pogledaj',
        'job_approved_here' => 'ovde',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Zdravo :job_author!',
        'job_expired_soon_message' => 'Vaš posao <a href=":job_url">:job_name</a> će isteći za :job_expired_after dana.',
        'job_expired_soon_renew' => 'Molimo <a href=":job_list">idite ovde</a> da obnovite svoj posao.',

        // Job renewed email template
        'job_renewed_greeting' => 'Zdravo :job_author!',
        'job_renewed_message' => 'Vaš posao <a href=":job_url">:job_name</a> je automatski obnovljen.',

        // New job posted email template
        'new_job_posted_title' => 'Novi posao objavljen',
        'new_job_posted_admin_greeting' => 'Zdravo Admin,',
        'new_job_posted_message' => 'Zadovoljstvo nam je da vas obavestimo da je novi oglas za posao objavljen od strane poslodavca na našoj platformi.',
        'new_job_posted_info' => 'Objava posla',
        'new_job_posted_employer' => 'Poslodavac: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Naziv posla: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Link administratorskog panela',
        'new_job_posted_here' => 'ovde',

        // New company profile created email template
        'new_company_profile_title' => 'Novi profil kompanije kreiran',
        'new_company_profile_admin_greeting' => 'Zdravo Admin!',
        'new_company_profile_message' => 'Novi profil kompanije je kreiran od strane :employer_name ":company_name"',
        'new_company_profile_info' => 'Informacije o kompaniji',
        'new_company_profile_employer' => 'Poslodavac: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Naziv kompanije: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Link administratorskog panela',
        'new_company_profile_here' => 'ovde',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Zdravo :account_name!',
        'payment_receipt_message' => 'Potvrda o plaćanju za vašu kupovinu:',
        'payment_receipt_package' => 'Paket: :package_name',
        'payment_receipt_price' => 'Cena: :package_price_per_credit/kredit',
        'payment_receipt_total' => 'Ukupno: :package_price za :package_number_of_listings kredita',
        'payment_receipt_save' => '(Ušteda :package_percent_discount%)',
        'payment_receipt_thanks' => 'Hvala vam na plaćanju!',
        'payment_receipt_info' => 'Informacije o plaćanju',
        'payment_receipt_amount' => 'Iznos: :package_price',
        'payment_receipt_invoice' => 'Kod fakture: :invoice_code',
        'payment_receipt_view_invoice' => 'Pogledaj fakturu',

        // Payment received email template
        'payment_received_admin_greeting' => 'Zdravo Admin!',
        'payment_received_message' => 'Plaćanje primljeno od :account_name:',
        'payment_received_account' => 'Nalog: :account_name (:account_email)',
        'payment_received_package' => 'Paket: :package_name',
        'payment_received_price' => 'Cena: :package_price_per_credit/kredit',
        'payment_received_total' => 'Ukupno: :package_price za :package_number_of_listings kredita',
        'payment_received_save' => '(Ušteda :package_percent_discount%)',
        'payment_received_info' => 'Informacije o plaćanju',
        'payment_received_customer' => 'Kupac: :account_name',
        'payment_received_amount' => 'Iznos: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Zdravo :account_name,',
        'invoice_payment_from' => 'Primate email od :site_title',
        'invoice_payment_attached' => 'Faktura #:invoice_code je priložena uz ovaj email.',
        'invoice_payment_view_online' => 'Pogledaj online',
        'invoice_payment_thanks' => 'Hvala vam na plaćanju!',
        'invoice_payment_info' => 'Informacije o fakturi',
        'invoice_payment_code' => 'Kod fakture: :invoice_code',
        'invoice_payment_view' => 'Pogledaj fakturu',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Zdravo Admin,',
        'free_credit_claimed_message' => ':account_name je preuzeo besplatni kredit na :site_title',
        'free_credit_claimed_info' => 'Informacije o nalogu',
        'free_credit_claimed_name' => 'Ime: :account_name',
        'free_credit_claimed_email' => 'Email: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Zdravo!',
        'password_reminder_message' => 'Primate ovaj email jer smo primili zahtev za resetovanje lozinke za vaš nalog.',
        'password_reminder_button' => 'Resetuj lozinku',
        'password_reminder_no_action' => 'Ako niste zatražili resetovanje lozinke, nije potrebna nikakva dalja akcija.',
        'password_reminder_regards' => 'Srdačan pozdrav,',
        'password_reminder_trouble' => 'Ako imate problema sa klikom na dugme "Resetuj lozinku", kopirajte i nalepite URL ispod u vaš veb pretraživač: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Zdravo :account_name!',
        'job_alert_hiring' => 'Zapošljavanje :job_name u :company_name',
        'job_alert_apply_forward' => '👇 Prijavite se ili prosledite prijatelju: :job_url',
        'job_alert_message' => 'Nove prilike za posao koje odgovaraju vašim preferencijama su objavljene!',
        'job_alert_job_info' => 'Posao: :job_name',
        'job_alert_company_info' => 'Kompanija: :company_name',
        'job_alert_view_job' => 'Pogledaj posao',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Potvrda prijave za posao',
        'job_application_confirmation_greeting' => 'Poštovani :job_application_name,',
        'job_application_confirmation_thanks' => 'Hvala vam na interesovanju za poziciju :job_name u :company_name. Zadovoljstvo nam je da potvrdimo da je vaša prijava uspešno prosleđena kroz naš sistem.',
        'job_application_confirmation_reviewing' => 'Naš tim za zapošljavanje pregleda vaše kvalifikacije i kontaktiraćemo vas ako vaše veštine i iskustvo odgovaraju zahtevima za ovu ulogu. Molimo vas da imate na umu da, zbog velikog broja prijava, ovaj proces može potrajati.',
        'job_application_confirmation_thanks_again' => 'Još jednom vam hvala što ste se prijavili!',
        'job_application_confirmation_regards' => 'Srdačan pozdrav,',
        'job_application_confirmation_team' => ':company_name Tim',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Zdravo,',
        'new_job_application_received' => 'Primili ste novu prijavu za posao.',
        'new_job_application_details' => 'Detalji prijave:',
        'new_job_application_name' => 'Ime: :job_application_name',
        'new_job_application_position' => 'Pozicija: :job_application_position',
        'new_job_application_email' => 'Email: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
