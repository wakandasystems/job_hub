<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Uusi työhakemus (ylläpitäjille)',
            'description' => 'Sähköpostipohja ilmoituksen lähettämiseen ylläpitäjille, kun järjestelmä saa uuden työhakemuksen',
            'subject' => 'Uusi työhakemus',
        ],
        'employer-new-job-application' => [
            'title' => 'Uusi työhakemus (työnantajalle ja kollegoille)',
            'description' => 'Sähköpostipohja ilmoituksen lähettämiseen työnantajalle ja kollegoille, kun järjestelmä saa uuden työhakemuksen',
            'subject' => 'Uusi työhakemus',
        ],
        'new-job-posted' => [
            'title' => 'Uusi työpaikka julkaistu',
            'description' => 'Lähetä sähköposti ylläpitäjälle, kun uusi työpaikka julkaistaan',
            'subject' => 'Uusi työpaikka julkaistu sivustolla {{ site_title }} käyttäjän {{ job_author }} toimesta',
        ],
        'new-company-profile-created' => [
            'title' => 'Uusi yritysprofiili luotu',
            'description' => 'Lähetä sähköposti ylläpitäjälle, kun työnantaja luo uuden yrityksen profiilin',
            'subject' => 'Uusi yritysprofiili luotu sivustolla {{ site_title }} käyttäjän {{ employer_name }} toimesta',
        ],
        'job-expired-soon' => [
            'title' => 'Työpaikka vanhenee pian',
            'description' => 'Lähetä sähköposti tekijälle, jos heidän työpaikkansa vanhenee seuraavan 3 päivän aikana',
            'subject' => 'Työpaikkasi "{{ job_name }}" vanhenee {{ job_expired_after }} päivän kuluttua',
        ],
        'job-renewed' => [
            'title' => 'Työpaikka uusittu',
            'description' => 'Lähetä sähköposti tekijälle, kun heidän työpaikkansa uusitaan',
            'subject' => 'Työpaikkasi "{{ job_name }}" on uusittu automaattisesti',
        ],
        'payment-receipt' => [
            'title' => 'Maksukuitti',
            'description' => 'Lähetä ilmoitus käyttäjälle, kun he ostavat krediittejä',
            'subject' => 'Maksukuitti paketista {{ package_name }} sivustolla {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Tili rekisteröity',
            'description' => 'Lähetä ilmoitus ylläpitäjälle, kun uusi työnantaja/työnhakija rekisteröityy',
            'subject' => 'Uusi {{ account_type }} rekisteröitynyt sivustolla {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Vahvista sähköposti',
            'description' => 'Lähetä sähköposti käyttäjälle, kun he rekisteröivät tilin vahvistaakseen sähköpostinsa',
            'subject' => 'Vahvista sähköposti-ilmoitus',
        ],
        'password-reminder' => [
            'title' => 'Nollaa salasana',
            'description' => 'Lähetä sähköposti käyttäjälle pyydettäessä salasanan nollausta',
            'subject' => 'Nollaa salasana',
        ],
        'free-credit-claimed' => [
            'title' => 'Ilmainen krediitti lunastettu',
            'description' => 'Lähetä ilmoitus ylläpitäjälle, kun ilmainen krediitti lunastetaan',
            'subject' => '{{ account_name }} on lunastanut ilmaisen krediitin sivustolla {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Maksu vastaanotettu',
            'description' => 'Lähetä ilmoitus ylläpitäjälle, kun joku ostaa krediittejä',
            'subject' => 'Maksu vastaanotettu käyttäjältä {{ account_name }} sivustolla {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Laskun maksutiedot',
            'description' => 'Lähetä ilmoitus asiakkaalle, joka suorittaa työpaikan julkaisumaksun',
            'subject' => 'Maksu vastaanotettu käyttäjältä {{ account_name }} sivustolla {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Uusi työpaikka julkaistu',
            'description' => 'Lähetä sähköposti työnhakijalle, kun uusi työpaikka julkaistaan',
            'subject' => 'Palkataan {{ job_name }} yrityksessä {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Työpaikka hyväksytty',
            'description' => 'Lähetä sähköposti tekijälle, kun heidän työpaikkansa hyväksytään',
            'subject' => 'Työpaikkasi "{{ job_name }}" on hyväksytty',
        ],
        'company-approved' => [
            'title' => 'Yritys hyväksytty',
            'description' => 'Lähetä sähköposti tekijälle, kun heidän yrityksensä hyväksytään',
            'subject' => 'Yrityksesi "{{ company_name }}" on hyväksytty',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Työhakemuksen vahvistus',
            'description' => 'Lähetä sähköposti työnhakijalle, kun he hakevat työpaikkaa',
            'subject' => 'Hakemuksen vahvistus työpaikkaan {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Nimi',
        'position' => 'Asema',
        'email' => 'Sähköposti',
        'phone' => 'Puhelin',
        'summary' => 'Yhteenveto',
        'resume' => 'Ansioluettelo',
        'cover_letter' => 'Saatekirje',
        'job_application' => 'Työhakemus',
        'job_name' => 'Työpaikan nimi',
        'job_url' => 'Työpaikan URL',
        'job_author' => 'Työpaikan tekijä',
        'company_name' => 'Yrityksen nimi',
        'company_url' => 'Yrityksen URL',
        'employer_name' => 'Työnantajan nimi',
        'job_list' => 'Työpaikkalistauksen URL',
        'job_expired_after' => 'Työpaikka vanhenee x päivän kuluttua',
        'account_name' => 'Tilin nimi',
        'account_email' => 'Tilin sähköposti',
        'package_name' => 'Paketin nimi',
        'package_price' => 'Hinta',
        'package_percent_discount' => 'Alennusprosentti',
        'package_number_of_listings' => 'Listausten määrä',
        'package_price_per_credit' => 'Hinta per krediitti',
        'account_type' => 'Tilin tyyppi (työnantaja/työnhakija)',
        'verify_link' => 'Vahvistuslinkki',
        'reset_link' => 'Nollauslinkki',
        'invoice_code' => 'Laskun koodi',
        'invoice_link' => 'Laskun linkki',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hei ylläpitäjä!',
        'account_registered_new_account' => 'Uusi :account_type rekisteröitynyt:',
        'account_registered_name' => 'Nimi: <strong>:account_name</strong>',
        'account_registered_email' => 'Sähköposti: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hei, Vastaanotimme uuden työhakemuksen sivustolta :site_title!',
        'admin_job_application_name' => 'Nimi: :job_application_name',
        'admin_job_application_position' => 'Asema: :job_application_position',
        'admin_job_application_email' => 'Sähköposti: :job_application_email',
        'admin_job_application_phone' => 'Puhelin: :job_application_phone',
        'admin_job_application_summary' => 'Yhteenveto: :job_application_summary',
        'admin_job_application_resume' => 'Ansioluettelo: :job_application_resume',
        'admin_job_application_cover_letter' => 'Saatekirje: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hei, Vastaanotimme uuden työhakemuksen sivustolta :site_title!',
        'employer_job_application_name' => 'Nimi: :job_application_name',
        'employer_job_application_position' => 'Asema: :job_application_position',
        'employer_job_application_email' => 'Sähköposti: :job_application_email',
        'employer_job_application_phone' => 'Puhelin: :job_application_phone',
        'employer_job_application_summary' => 'Yhteenveto: :job_application_summary',
        'employer_job_application_resume' => 'Ansioluettelo: :job_application_resume',
        'employer_job_application_cover_letter' => 'Saatekirje: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Yritys hyväksytty',
        'company_approved_greeting' => 'Hei,',
        'company_approved_message' => 'Ilmoitamme ilolla, että yrityksesi on hyväksytty ja nyt aktiivinen alustalla.',
        'company_approved_info' => 'Yrityksen tiedot',
        'company_approved_name' => 'Nimi: <strong>:company_name</strong>',
        'company_approved_view' => 'Katso',
        'company_approved_here' => 'täältä',

        // Confirm email template
        'confirm_email_greeting' => 'Hei!',
        'confirm_email_message' => 'Vahvista sähköpostiosoitteesi päästäksesi tälle verkkosivustolle. Napsauta alla olevaa painiketta vahvistaaksesi sähköpostisi.',
        'confirm_email_button' => 'Vahvista nyt',
        'confirm_email_regards' => 'Terveisin,',
        'confirm_email_trouble' => 'Jos sinulla on ongelmia "Vahvista nyt" -painikkeen napsauttamisessa, kopioi ja liitä alla oleva URL-osoite verkkoselaimeen: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Työpaikka hyväksytty',
        'job_approved_greeting' => 'Hei :job_author,',
        'job_approved_message' => 'Ilmoitamme ilolla, että työpaikkailmoituksesi on hyväksytty ja nyt aktiivinen alustalla.',
        'job_approved_info' => 'Työpaikan tiedot',
        'job_approved_job_title' => 'Työpaikan otsikko: <strong>:job_name</strong>',
        'job_approved_view' => 'Katso',
        'job_approved_here' => 'täältä',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hei :job_author!',
        'job_expired_soon_message' => 'Työpaikkasi <a href=":job_url">:job_name</a> vanhenee :job_expired_after päivän kuluttua.',
        'job_expired_soon_renew' => 'Ole hyvä ja <a href=":job_list">siirry tänne</a> uusiaksesi työpaikkasi.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hei :job_author!',
        'job_renewed_message' => 'Työpaikkasi <a href=":job_url">:job_name</a> on uusittu automaattisesti.',

        // New job posted email template
        'new_job_posted_title' => 'Uusi työpaikka julkaistu',
        'new_job_posted_admin_greeting' => 'Hei ylläpitäjä,',
        'new_job_posted_message' => 'Ilmoitamme ilolla, että työnantaja on julkaissut uuden työpaikkailmoituksen alustalla.',
        'new_job_posted_info' => 'Työpaikkailmoitus',
        'new_job_posted_employer' => 'Työnantaja: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Työpaikan otsikko: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Ylläpitopaneelin linkki',
        'new_job_posted_here' => 'täältä',

        // New company profile created email template
        'new_company_profile_title' => 'Uusi yritysprofiili luotu',
        'new_company_profile_admin_greeting' => 'Hei ylläpitäjä!',
        'new_company_profile_message' => 'Uusi yritysprofiili on luotu käyttäjän :employer_name toimesta ":company_name"',
        'new_company_profile_info' => 'Yrityksen tiedot',
        'new_company_profile_employer' => 'Työnantaja: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Yrityksen nimi: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Ylläpitopaneelin linkki',
        'new_company_profile_here' => 'täältä',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hei :account_name!',
        'payment_receipt_message' => 'Maksukuitti ostoksestasi:',
        'payment_receipt_package' => 'Paketti: :package_name',
        'payment_receipt_price' => 'Hinta: :package_price_per_credit/krediitti',
        'payment_receipt_total' => 'Yhteensä: :package_price :package_number_of_listings krediitistä',
        'payment_receipt_save' => '(Säästä :package_percent_discount%)',
        'payment_receipt_thanks' => 'Kiitos maksustasi!',
        'payment_receipt_info' => 'Maksutiedot',
        'payment_receipt_amount' => 'Summa: :package_price',
        'payment_receipt_invoice' => 'Laskun koodi: :invoice_code',
        'payment_receipt_view_invoice' => 'Katso lasku',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hei ylläpitäjä!',
        'payment_received_message' => 'Maksu vastaanotettu käyttäjältä :account_name:',
        'payment_received_account' => 'Tili: :account_name (:account_email)',
        'payment_received_package' => 'Paketti: :package_name',
        'payment_received_price' => 'Hinta: :package_price_per_credit/krediitti',
        'payment_received_total' => 'Yhteensä: :package_price :package_number_of_listings krediitistä',
        'payment_received_save' => '(Säästä :package_percent_discount%)',
        'payment_received_info' => 'Maksutiedot',
        'payment_received_customer' => 'Asiakas: :account_name',
        'payment_received_amount' => 'Summa: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hei :account_name,',
        'invoice_payment_from' => 'Saat sähköpostin sivustolta :site_title',
        'invoice_payment_attached' => 'Lasku #:invoice_code on liitetty tähän sähköpostiin.',
        'invoice_payment_view_online' => 'Katso verkossa',
        'invoice_payment_thanks' => 'Kiitos maksustasi!',
        'invoice_payment_info' => 'Laskun tiedot',
        'invoice_payment_code' => 'Laskun koodi: :invoice_code',
        'invoice_payment_view' => 'Katso lasku',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hei ylläpitäjä,',
        'free_credit_claimed_message' => ':account_name on lunastanut ilmaisen krediitin sivustolla :site_title',
        'free_credit_claimed_info' => 'Tilin tiedot',
        'free_credit_claimed_name' => 'Nimi: :account_name',
        'free_credit_claimed_email' => 'Sähköposti: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hei!',
        'password_reminder_message' => 'Saat tämän sähköpostin, koska olemme vastaanottaneet salasanan nollauspyynnön tiliisi.',
        'password_reminder_button' => 'Nollaa salasana',
        'password_reminder_no_action' => 'Jos et pyytänyt salasanan nollausta, toimenpiteitä ei tarvita.',
        'password_reminder_regards' => 'Terveisin,',
        'password_reminder_trouble' => 'Jos sinulla on ongelmia "Nollaa salasana" -painikkeen napsauttamisessa, kopioi ja liitä alla oleva URL-osoite verkkoselaimeen: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hei :account_name!',
        'job_alert_hiring' => 'Palkataan :job_name yrityksessä :company_name',
        'job_alert_apply_forward' => '👇 Hae tai välitä ystävälle: :job_url',
        'job_alert_message' => 'Uusia työmahdollisuuksia, jotka vastaavat mieltymyksiäsi, on julkaistu!',
        'job_alert_job_info' => 'Työpaikka: :job_name',
        'job_alert_company_info' => 'Yritys: :company_name',
        'job_alert_view_job' => 'Katso työpaikka',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Työhakemuksen vahvistus',
        'job_application_confirmation_greeting' => 'Hyvä :job_application_name,',
        'job_application_confirmation_thanks' => 'Kiitos kiinnostuksestasi :job_name -työpaikkaan yrityksessä :company_name. Vahvistamme ilolla, että hakemuksesi on lähetetty järjestelmämme kautta onnistuneesti.',
        'job_application_confirmation_reviewing' => 'Rekrytointitiimimme tarkistaa pätevyytesi, ja otamme sinuun yhteyttä, jos taitosi ja kokemuksesi vastaavat tämän roolin vaatimuksia. Huomioithan, että suuren hakemusmäärän vuoksi tämä prosessi voi kestää jonkin aikaa.',
        'job_application_confirmation_thanks_again' => 'Kiitos vielä kerran hakemisesta!',
        'job_application_confirmation_regards' => 'Ystävällisin terveisin,',
        'job_application_confirmation_team' => ':company_name -tiimi',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hei,',
        'new_job_application_received' => 'Olet vastaanottanut uuden työhakemuksen.',
        'new_job_application_details' => 'Hakemuksen tiedot:',
        'new_job_application_name' => 'Nimi: :job_application_name',
        'new_job_application_position' => 'Asema: :job_application_position',
        'new_job_application_email' => 'Sähköposti: :job_application_email',
        'new_job_application_phone' => 'Puhelin: :job_application_phone',
    ],
];
