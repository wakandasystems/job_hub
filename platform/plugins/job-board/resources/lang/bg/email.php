<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Нова кандидатура за работа (за администратори)',
            'description' => 'Шаблон за имейл за изпращане на известие до администратори, когато системата получи нова кандидатура за работа',
            'subject' => 'Нова кандидатура за работа',
        ],
        'employer-new-job-application' => [
            'title' => 'Нова кандидатура за работа (за работодател и колеги)',
            'description' => 'Шаблон за имейл за изпращане на известие до работодател и колеги, когато системата получи нова кандидатура за работа',
            'subject' => 'Нова кандидатура за работа',
        ],
        'new-job-posted' => [
            'title' => 'Публикувана нова работа',
            'description' => 'Изпращане на имейл до администратор при публикуване на нова работа',
            'subject' => 'Нова работа е публикувана на {{ site_title }} от {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Създаден нов профил на компания',
            'description' => 'Изпращане на имейл до администратор, когато работодател създаде нов профил на компания',
            'subject' => 'Нов профил на компания е създаден на {{ site_title }} от {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Работата скоро изтича',
            'description' => 'Изпращане на имейл до автора, ако работата им ще изтече в следващите 3 дни',
            'subject' => 'Вашата работа "{{ job_name }}" ще изтече след {{ job_expired_after }} дни',
        ],
        'job-renewed' => [
            'title' => 'Работата е подновена',
            'description' => 'Изпращане на имейл до автора, когато работата им бъде подновена',
            'subject' => 'Вашата работа "{{ job_name }}" е подновена автоматично',
        ],
        'payment-receipt' => [
            'title' => 'Разписка за плащане',
            'description' => 'Изпращане на известие до потребител, когато купи кредити',
            'subject' => 'Разписка за плащане за пакет {{ package_name }} на {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Акаунтът е регистриран',
            'description' => 'Изпращане на известие до администратор, когато се регистрира нов работодател/търсещ работа',
            'subject' => 'Нов {{ account_type }} регистриран на {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Потвърждение на имейл',
            'description' => 'Изпращане на имейл до потребител при регистрация на акаунт за потвърждаване на имейла',
            'subject' => 'Известие за потвърждение на имейл',
        ],
        'password-reminder' => [
            'title' => 'Нулиране на парола',
            'description' => 'Изпращане на имейл до потребител при заявка за нулиране на парола',
            'subject' => 'Нулиране на парола',
        ],
        'free-credit-claimed' => [
            'title' => 'Безплатен кредит е получен',
            'description' => 'Изпращане на известие до администратор, когато безплатен кредит бъде получен',
            'subject' => '{{ account_name }} получи безплатен кредит на {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Плащане получено',
            'description' => 'Изпращане на известие до администратор, когато някой купи кредити',
            'subject' => 'Плащане получено от {{ account_name }} на {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Детайли за плащане на фактура',
            'description' => 'Изпращане на известие до клиента, който извършва плащане за публикуване на работа',
            'subject' => 'Плащане получено от {{ account_name }} на {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Публикувана нова работа',
            'description' => 'Изпращане на имейл до търсещ работа, когато бъде публикувана нова работа',
            'subject' => 'Набиране на {{ job_name }} в {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Работата е одобрена',
            'description' => 'Изпращане на имейл до автора, когато работата им бъде одобрена',
            'subject' => 'Вашата работа "{{ job_name }}" е одобрена',
        ],
        'company-approved' => [
            'title' => 'Компанията е одобрена',
            'description' => 'Изпращане на имейл до автора, когато компанията им бъде одобрена',
            'subject' => 'Вашата компания "{{ company_name }}" е одобрена',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Потвърждение на кандидатура за работа',
            'description' => 'Изпращане на имейл до търсещ работа, когато кандидатства за работа',
            'subject' => 'Потвърждение на кандидатура за {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Име',
        'position' => 'Позиция',
        'email' => 'Имейл',
        'phone' => 'Телефон',
        'summary' => 'Резюме',
        'resume' => 'Автобиография',
        'cover_letter' => 'Мотивационно писмо',
        'job_application' => 'Кандидатура за работа',
        'job_name' => 'Име на работата',
        'job_url' => 'URL на работата',
        'job_author' => 'Автор на работата',
        'company_name' => 'Име на компанията',
        'company_url' => 'URL на компанията',
        'employer_name' => 'Име на работодателя',
        'job_list' => 'URL на списъка с работи',
        'job_expired_after' => 'Работата изтича след x дни',
        'account_name' => 'Име на акаунта',
        'account_email' => 'Имейл на акаунта',
        'package_name' => 'Име на пакета',
        'package_price' => 'Цена',
        'package_percent_discount' => 'Процент отстъпка',
        'package_number_of_listings' => 'Брой обяви',
        'package_price_per_credit' => 'Цена на кредит',
        'account_type' => 'Тип акаунт (работодател/търсещ работа)',
        'verify_link' => 'Връзка за потвърждение',
        'reset_link' => 'Връзка за нулиране',
        'invoice_code' => 'Код на фактура',
        'invoice_link' => 'Връзка към фактура',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Здравей, Администратор!',
        'account_registered_new_account' => 'Нов :account_type се регистрира:',
        'account_registered_name' => 'Име: <strong>:account_name</strong>',
        'account_registered_email' => 'Имейл: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Здравейте, получихме нова кандидатура за работа от :site_title!',
        'admin_job_application_name' => 'Име: :job_application_name',
        'admin_job_application_position' => 'Позиция: :job_application_position',
        'admin_job_application_email' => 'Имейл: :job_application_email',
        'admin_job_application_phone' => 'Телефон: :job_application_phone',
        'admin_job_application_summary' => 'Резюме: :job_application_summary',
        'admin_job_application_resume' => 'Автобиография: :job_application_resume',
        'admin_job_application_cover_letter' => 'Мотивационно писмо: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Здравейте, получихме нова кандидатура за работа от :site_title!',
        'employer_job_application_name' => 'Име: :job_application_name',
        'employer_job_application_position' => 'Позиция: :job_application_position',
        'employer_job_application_email' => 'Имейл: :job_application_email',
        'employer_job_application_phone' => 'Телефон: :job_application_phone',
        'employer_job_application_summary' => 'Резюме: :job_application_summary',
        'employer_job_application_resume' => 'Автобиография: :job_application_resume',
        'employer_job_application_cover_letter' => 'Мотивационно писмо: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Компанията е одобрена',
        'company_approved_greeting' => 'Здравейте,',
        'company_approved_message' => 'Имаме удоволствието да ви информираме, че вашата компания е одобрена и вече е активна на нашата платформа.',
        'company_approved_info' => 'Информация за компанията',
        'company_approved_name' => 'Име: <strong>:company_name</strong>',
        'company_approved_view' => 'Преглед',
        'company_approved_here' => 'тук',

        // Confirm email template
        'confirm_email_greeting' => 'Здравейте!',
        'confirm_email_message' => 'Моля, потвърдете вашия имейл адрес, за да получите достъп до този уебсайт. Кликнете на бутона по-долу за потвърждение на имейла.',
        'confirm_email_button' => 'Потвърдете сега',
        'confirm_email_regards' => 'Поздрави,',
        'confirm_email_trouble' => 'Ако имате проблеми с кликването на бутона "Потвърдете сега", копирайте и поставете URL адреса по-долу във вашия уеб браузър: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Работата е одобрена',
        'job_approved_greeting' => 'Здравей, :job_author,',
        'job_approved_message' => 'Имаме удоволствието да ви информираме, че вашата обява за работа е одобрена и вече е активна на нашата платформа.',
        'job_approved_info' => 'Информация за работата',
        'job_approved_job_title' => 'Заглавие на работата: <strong>:job_name</strong>',
        'job_approved_view' => 'Преглед',
        'job_approved_here' => 'тук',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Здравей, :job_author!',
        'job_expired_soon_message' => 'Вашата работа <a href=":job_url">:job_name</a> ще изтече след :job_expired_after дни.',
        'job_expired_soon_renew' => 'Моля, <a href=":job_list">отидете тук</a> за да подновите вашата работа.',

        // Job renewed email template
        'job_renewed_greeting' => 'Здравей, :job_author!',
        'job_renewed_message' => 'Вашата работа <a href=":job_url">:job_name</a> е подновена автоматично.',

        // New job posted email template
        'new_job_posted_title' => 'Публикувана нова работа',
        'new_job_posted_admin_greeting' => 'Здравей, Администратор,',
        'new_job_posted_message' => 'Имаме удоволствието да ви информираме, че нова обява за работа е публикувана от работодател на нашата платформа.',
        'new_job_posted_info' => 'Публикация за работа',
        'new_job_posted_employer' => 'Работодател: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Заглавие на работата: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Връзка към админ панела',
        'new_job_posted_here' => 'тук',

        // New company profile created email template
        'new_company_profile_title' => 'Създаден нов профил на компания',
        'new_company_profile_admin_greeting' => 'Здравей, Администратор!',
        'new_company_profile_message' => 'Нов профил на компания е създаден от :employer_name ":company_name"',
        'new_company_profile_info' => 'Информация за компанията',
        'new_company_profile_employer' => 'Работодател: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Име на компанията: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Връзка към админ панела',
        'new_company_profile_here' => 'тук',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Здравей, :account_name!',
        'payment_receipt_message' => 'Разписка за плащане за вашата покупка:',
        'payment_receipt_package' => 'Пакет: :package_name',
        'payment_receipt_price' => 'Цена: :package_price_per_credit/кредит',
        'payment_receipt_total' => 'Общо: :package_price за :package_number_of_listings кредити',
        'payment_receipt_save' => '(Спестяване :package_percent_discount%)',
        'payment_receipt_thanks' => 'Благодарим ви за плащането!',
        'payment_receipt_info' => 'Информация за плащане',
        'payment_receipt_amount' => 'Сума: :package_price',
        'payment_receipt_invoice' => 'Код на фактура: :invoice_code',
        'payment_receipt_view_invoice' => 'Преглед на фактура',

        // Payment received email template
        'payment_received_admin_greeting' => 'Здравей, Администратор!',
        'payment_received_message' => 'Плащане получено от :account_name:',
        'payment_received_account' => 'Акаунт: :account_name (:account_email)',
        'payment_received_package' => 'Пакет: :package_name',
        'payment_received_price' => 'Цена: :package_price_per_credit/кредит',
        'payment_received_total' => 'Общо: :package_price за :package_number_of_listings кредити',
        'payment_received_save' => '(Спестяване :package_percent_discount%)',
        'payment_received_info' => 'Информация за плащане',
        'payment_received_customer' => 'Клиент: :account_name',
        'payment_received_amount' => 'Сума: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Здравей, :account_name,',
        'invoice_payment_from' => 'Получавате имейл от :site_title',
        'invoice_payment_attached' => 'Фактура #:invoice_code е прикачена към този имейл.',
        'invoice_payment_view_online' => 'Преглед онлайн',
        'invoice_payment_thanks' => 'Благодарим ви за плащането!',
        'invoice_payment_info' => 'Информация за фактурата',
        'invoice_payment_code' => 'Код на фактура: :invoice_code',
        'invoice_payment_view' => 'Преглед на фактура',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Здравей, Администратор,',
        'free_credit_claimed_message' => ':account_name получи безплатен кредит на :site_title',
        'free_credit_claimed_info' => 'Информация за акаунта',
        'free_credit_claimed_name' => 'Име: :account_name',
        'free_credit_claimed_email' => 'Имейл: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Здравейте!',
        'password_reminder_message' => 'Получавате този имейл, защото получихме заявка за нулиране на парола за вашия акаунт.',
        'password_reminder_button' => 'Нулиране на парола',
        'password_reminder_no_action' => 'Ако не сте заявили нулиране на парола, не е необходимо допълнително действие.',
        'password_reminder_regards' => 'Поздрави,',
        'password_reminder_trouble' => 'Ако имате проблеми с кликването на бутона "Нулиране на парола", копирайте и поставете URL адреса по-долу във вашия уеб браузър: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Здравей, :account_name!',
        'job_alert_hiring' => 'Набиране на :job_name в :company_name',
        'job_alert_apply_forward' => '👇 Кандидатствайте или препратете на приятел: :job_url',
        'job_alert_message' => 'Публикувани са нови възможности за работа, съответстващи на вашите предпочитания!',
        'job_alert_job_info' => 'Работа: :job_name',
        'job_alert_company_info' => 'Компания: :company_name',
        'job_alert_view_job' => 'Преглед на работата',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Потвърждение на кандидатура за работа',
        'job_application_confirmation_greeting' => 'Уважаеми/а :job_application_name,',
        'job_application_confirmation_thanks' => 'Благодарим ви за интереса към позицията :job_name в :company_name. Имаме удоволствието да потвърдим, че вашата кандидатура е успешно подадена чрез нашата система.',
        'job_application_confirmation_reviewing' => 'Нашият екип по набиране на персонал преглежда вашите квалификации и ще се свържем с вас, ако вашите умения и опит съответстват на изискванията за тази роля. Моля, имайте предвид, че поради голямото количество кандидатури, този процес може да отнеме известно време.',
        'job_application_confirmation_thanks_again' => 'Благодарим ви отново за кандидатурата!',
        'job_application_confirmation_regards' => 'С най-добри пожелания,',
        'job_application_confirmation_team' => 'Екипът на :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Здравейте,',
        'new_job_application_received' => 'Получихте нова кандидатура за работа.',
        'new_job_application_details' => 'Детайли на кандидатурата:',
        'new_job_application_name' => 'Име: :job_application_name',
        'new_job_application_position' => 'Позиция: :job_application_position',
        'new_job_application_email' => 'Имейл: :job_application_email',
        'new_job_application_phone' => 'Телефон: :job_application_phone',
    ],
];
