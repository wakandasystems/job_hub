<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'ახალი განაცხადი სამუშაოზე (ადმინისტრატორებისთვის)',
            'description' => 'ელფოსტის შაბლონი შეტყობინების გასაგზავნად ადმინისტრატორებისთვის, როდესაც სისტემა იღებს ახალ განაცხადს',
            'subject' => 'ახალი განაცხადი სამუშაოზე',
        ],
        'employer-new-job-application' => [
            'title' => 'ახალი განაცხადი სამუშაოზე (დამსაქმებლისა და კოლეგებისთვის)',
            'description' => 'ელფოსტის შაბლონი შეტყობინების გასაგზავნად დამსაქმებლისა და კოლეგებისთვის, როდესაც სისტემა იღებს ახალ განაცხადს',
            'subject' => 'ახალი განაცხადი სამუშაოზე',
        ],
        'new-job-posted' => [
            'title' => 'ახალი სამუშაო გამოქვეყნდა',
            'description' => 'ელფოსტის გაგზავნა ადმინისტრატორისთვის ახალი სამუშაოს გამოქვეყნებისას',
            'subject' => 'ახალი სამუშაო გამოქვეყნდა {{ site_title }}-ზე {{ job_author }}-ის მიერ',
        ],
        'new-company-profile-created' => [
            'title' => 'ახალი კომპანიის პროფილი შეიქმნა',
            'description' => 'ელფოსტის გაგზავნა ადმინისტრატორისთვის, როდესაც დამსაქმებელი ქმნის ახალ კომპანიის პროფილს',
            'subject' => 'ახალი კომპანიის პროფილი შეიქმნა {{ site_title }}-ზე {{ employer_name }}-ის მიერ',
        ],
        'job-expired-soon' => [
            'title' => 'სამუშაო მალე ვადაგასული იქნება',
            'description' => 'ელფოსტის გაგზავნა ავტორისთვის, თუ მათი სამუშაო ვადაგასულია მომდევნო 3 დღეში',
            'subject' => 'თქვენი სამუშაო "{{ job_name }}" ვადაგასულია {{ job_expired_after }} დღეში',
        ],
        'job-renewed' => [
            'title' => 'სამუშაო განახლდა',
            'description' => 'ელფოსტის გაგზავნა ავტორისთვის, როდესაც მათი სამუშაო განახლდა',
            'subject' => 'თქვენი სამუშაო "{{ job_name }}" ავტომატურად განახლდა',
        ],
        'payment-receipt' => [
            'title' => 'გადახდის ქვითარი',
            'description' => 'შეტყობინების გაგზავნა მომხმარებლისთვის, როდესაც ისინი ყიდულობენ კრედიტებს',
            'subject' => 'გადახდის ქვითარი პაკეტისთვის {{ package_name }} {{ site_title }}-ზე',
        ],
        'account-registered' => [
            'title' => 'ანგარიში რეგისტრირებულია',
            'description' => 'შეტყობინების გაგზავნა ადმინისტრატორისთვის, როდესაც ახალი დამსაქმებელი/სამუშაოს მაძიებელი რეგისტრირდება',
            'subject' => 'ახალი {{ account_type }} რეგისტრირებულია {{ site_title }}-ზე',
        ],
        'confirm-email' => [
            'title' => 'ელფოსტის დადასტურება',
            'description' => 'ელფოსტის გაგზავნა მომხმარებლისთვის, როდესაც ისინი რეგისტრირდებიან ანგარიშზე მათი ელფოსტის დასადასტურებლად',
            'subject' => 'ელფოსტის დადასტურების შეტყობინება',
        ],
        'password-reminder' => [
            'title' => 'პაროლის აღდგენა',
            'description' => 'ელფოსტის გაგზავნა მომხმარებლისთვის პაროლის აღდგენის მოთხოვნისას',
            'subject' => 'პაროლის აღდგენა',
        ],
        'free-credit-claimed' => [
            'title' => 'უფასო კრედიტი მიღებულია',
            'description' => 'შეტყობინების გაგზავნა ადმინისტრატორისთვის, როდესაც უფასო კრედიტი მიღებულია',
            'subject' => '{{ account_name }}-მა მიიღო უფასო კრედიტი {{ site_title }}-ზე',
        ],
        'payment-received' => [
            'title' => 'გადახდა მიღებულია',
            'description' => 'შეტყობინების გაგზავნა ადმინისტრატორისთვის, როდესაც ვინმე ყიდულობს კრედიტებს',
            'subject' => 'გადახდა მიღებულია {{ account_name }}-გან {{ site_title }}-ზე',
        ],
        'invoice-payment-created' => [
            'title' => 'ინვოისის გადახდის დეტალები',
            'description' => 'შეტყობინების გაგზავნა მომხმარებლისთვის, ვინც ასრულებს სამუშაოს განთავსების გადახდას',
            'subject' => 'გადახდა მიღებულია {{ account_name }}-გან {{ site_title }}-ზე',
        ],
        'job-seeker-job-alert' => [
            'title' => 'ახალი სამუშაო გამოქვეყნდა',
            'description' => 'ელფოსტის გაგზავნა სამუშაოს მაძიებლისთვის ახალი სამუშაოს გამოქვეყნებისას',
            'subject' => 'ვაკანსია {{ job_name }} {{ company_name }}-ში',
        ],
        'job-approved' => [
            'title' => 'სამუშაო დამტკიცდა',
            'description' => 'ელფოსტის გაგზავნა ავტორისთვის, როდესაც მათი სამუშაო დამტკიცდა',
            'subject' => 'თქვენი სამუშაო "{{ job_name }}" დამტკიცდა',
        ],
        'company-approved' => [
            'title' => 'კომპანია დამტკიცდა',
            'description' => 'ელფოსტის გაგზავნა ავტორისთვის, როდესაც მათი კომპანია დამტკიცდა',
            'subject' => 'თქვენი კომპანია "{{ company_name }}" დამტკიცდა',
        ],
        'job-seeker-applied-job' => [
            'title' => 'განაცხადის დადასტურება',
            'description' => 'ელფოსტის გაგზავნა სამუშაოს მაძიებლისთვის, როდესაც ისინი აპლიკაციას სამუშაოზე',
            'subject' => 'განაცხადის დადასტურება {{ job_name }}-თვის',
        ],
    ],
    'variables' => [
        'name' => 'სახელი',
        'position' => 'პოზიცია',
        'email' => 'ელფოსტა',
        'phone' => 'ტელეფონი',
        'summary' => 'რეზიუმე',
        'resume' => 'CV',
        'cover_letter' => 'სამოტივაციო წერილი',
        'job_application' => 'განაცხადი სამუშაოზე',
        'job_name' => 'სამუშაოს სახელი',
        'job_url' => 'სამუშაოს URL',
        'job_author' => 'სამუშაოს ავტორი',
        'company_name' => 'კომპანიის სახელი',
        'company_url' => 'კომპანიის URL',
        'employer_name' => 'დამსაქმებლის სახელი',
        'job_list' => 'სამუშაოს სიის URL',
        'job_expired_after' => 'სამუშაო ვადაგასულია x დღეში',
        'account_name' => 'ანგარიშის სახელი',
        'account_email' => 'ანგარიშის ელფოსტა',
        'package_name' => 'პაკეტის სახელი',
        'package_price' => 'ფასი',
        'package_percent_discount' => 'ფასდაკლების პროცენტი',
        'package_number_of_listings' => 'განაცხადების რაოდენობა',
        'package_price_per_credit' => 'ფასი ერთ კრედიტზე',
        'account_type' => 'ანგარიშის ტიპი (დამსაქმებელი/სამუშაოს მაძიებელი)',
        'verify_link' => 'დადასტურების ბმული',
        'reset_link' => 'აღდგენის ბმული',
        'invoice_code' => 'ინვოისის კოდი',
        'invoice_link' => 'ინვოისის ბმული',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'გამარჯობა ადმინისტრატორო!',
        'account_registered_new_account' => 'ახალი :account_type რეგისტრირებულია:',
        'account_registered_name' => 'სახელი: <strong>:account_name</strong>',
        'account_registered_email' => 'ელფოსტა: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'გამარჯობა, ჩვენ მივიღეთ ახალი განაცხადი :site_title-დან!',
        'admin_job_application_name' => 'სახელი: :job_application_name',
        'admin_job_application_position' => 'პოზიცია: :job_application_position',
        'admin_job_application_email' => 'ელფოსტა: :job_application_email',
        'admin_job_application_phone' => 'ტელეფონი: :job_application_phone',
        'admin_job_application_summary' => 'რეზიუმე: :job_application_summary',
        'admin_job_application_resume' => 'CV: :job_application_resume',
        'admin_job_application_cover_letter' => 'სამოტივაციო წერილი: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'გამარჯობა, ჩვენ მივიღეთ ახალი განაცხადი :site_title-დან!',
        'employer_job_application_name' => 'სახელი: :job_application_name',
        'employer_job_application_position' => 'პოზიცია: :job_application_position',
        'employer_job_application_email' => 'ელფოსტა: :job_application_email',
        'employer_job_application_phone' => 'ტელეფონი: :job_application_phone',
        'employer_job_application_summary' => 'რეზიუმე: :job_application_summary',
        'employer_job_application_resume' => 'CV: :job_application_resume',
        'employer_job_application_cover_letter' => 'სამოტივაციო წერილი: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'კომპანია დამტკიცდა',
        'company_approved_greeting' => 'გამარჯობა,',
        'company_approved_message' => 'სიამოვნებით გაცნობებთ, რომ თქვენი კომპანია დამტკიცდა და ახლა ხელმისაწვდომია ჩვენს პლატფორმაზე.',
        'company_approved_info' => 'კომპანიის ინფორმაცია',
        'company_approved_name' => 'სახელი: <strong>:company_name</strong>',
        'company_approved_view' => 'ნახვა',
        'company_approved_here' => 'აქ',

        // Confirm email template
        'confirm_email_greeting' => 'გამარჯობა!',
        'confirm_email_message' => 'გთხოვთ დაადასტუროთ თქვენი ელფოსტის მისამართი ამ ვებსაიტზე წვდომისთვის. დააჭირეთ ღილაკს ქვემოთ თქვენი ელფოსტის დასადასტურებლად.',
        'confirm_email_button' => 'დაადასტურეთ ახლა',
        'confirm_email_regards' => 'პატივისცემით,',
        'confirm_email_trouble' => 'თუ პრობლემა გაქვთ "დაადასტურეთ ახლა" ღილაკზე დაჭერით, დააკოპირეთ და ჩასვით ქვემოთ მოცემული URL თქვენს ვებ ბრაუზერში: :verify_link',

        // Job approved email template
        'job_approved_title' => 'სამუშაო დამტკიცდა',
        'job_approved_greeting' => 'გამარჯობა :job_author,',
        'job_approved_message' => 'სიამოვნებით გაცნობებთ, რომ თქვენი სამუშაო დამტკიცდა და ახლა ხელმისაწვდომია ჩვენს პლატფორმაზე.',
        'job_approved_info' => 'სამუშაოს ინფორმაცია',
        'job_approved_job_title' => 'სამუშაოს სათაური: <strong>:job_name</strong>',
        'job_approved_view' => 'ნახვა',
        'job_approved_here' => 'აქ',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'გამარჯობა :job_author!',
        'job_expired_soon_message' => 'თქვენი სამუშაო <a href=":job_url">:job_name</a> ვადაგასულია :job_expired_after დღეში.',
        'job_expired_soon_renew' => 'გთხოვთ <a href=":job_list">გადადით აქ</a> თქვენი სამუშაოს განახლებისთვის.',

        // Job renewed email template
        'job_renewed_greeting' => 'გამარჯობა :job_author!',
        'job_renewed_message' => 'თქვენი სამუშაო <a href=":job_url">:job_name</a> ავტომატურად განახლდა.',

        // New job posted email template
        'new_job_posted_title' => 'ახალი სამუშაო გამოქვეყნდა',
        'new_job_posted_admin_greeting' => 'გამარჯობა ადმინისტრატორო,',
        'new_job_posted_message' => 'სიამოვნებით გაცნობებთ, რომ ახალი სამუშაო გამოქვეყნდა დამსაქმებლის მიერ ჩვენს პლატფორმაზე.',
        'new_job_posted_info' => 'სამუშაოს პოსტი',
        'new_job_posted_employer' => 'დამსაქმებელი: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'სამუშაოს სათაური: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'ადმინ პანელის ბმული',
        'new_job_posted_here' => 'აქ',

        // New company profile created email template
        'new_company_profile_title' => 'ახალი კომპანიის პროფილი შეიქმნა',
        'new_company_profile_admin_greeting' => 'გამარჯობა ადმინისტრატორო!',
        'new_company_profile_message' => 'ახალი კომპანიის პროფილი შეიქმნა :employer_name ":company_name"-ის მიერ',
        'new_company_profile_info' => 'კომპანიის ინფორმაცია',
        'new_company_profile_employer' => 'დამსაქმებელი: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'კომპანიის სახელი: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'ადმინ პანელის ბმული',
        'new_company_profile_here' => 'აქ',

        // Payment receipt email template
        'payment_receipt_greeting' => 'გამარჯობა :account_name!',
        'payment_receipt_message' => 'გადახდის ქვითარი თქვენი შესყიდვისთვის:',
        'payment_receipt_package' => 'პაკეტი: :package_name',
        'payment_receipt_price' => 'ფასი: :package_price_per_credit/კრედიტი',
        'payment_receipt_total' => 'სულ: :package_price :package_number_of_listings კრედიტისთვის',
        'payment_receipt_save' => '(დაზოგე :package_percent_discount%)',
        'payment_receipt_thanks' => 'გმადლობთ თქვენი გადახდისთვის!',
        'payment_receipt_info' => 'გადახდის ინფორმაცია',
        'payment_receipt_amount' => 'თანხა: :package_price',
        'payment_receipt_invoice' => 'ინვოისის კოდი: :invoice_code',
        'payment_receipt_view_invoice' => 'ინვოისის ნახვა',

        // Payment received email template
        'payment_received_admin_greeting' => 'გამარჯობა ადმინისტრატორო!',
        'payment_received_message' => 'გადახდა მიღებულია :account_name-გან:',
        'payment_received_account' => 'ანგარიში: :account_name (:account_email)',
        'payment_received_package' => 'პაკეტი: :package_name',
        'payment_received_price' => 'ფასი: :package_price_per_credit/კრედიტი',
        'payment_received_total' => 'სულ: :package_price :package_number_of_listings კრედიტისთვის',
        'payment_received_save' => '(დაზოგე :package_percent_discount%)',
        'payment_received_info' => 'გადახდის ინფორმაცია',
        'payment_received_customer' => 'მომხმარებელი: :account_name',
        'payment_received_amount' => 'თანხა: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'გამარჯობა :account_name,',
        'invoice_payment_from' => 'თქვენ იღებთ ელფოსტას :site_title-დან',
        'invoice_payment_attached' => 'ინვოისი #:invoice_code თან ერთვის ამ ელფოსტას.',
        'invoice_payment_view_online' => 'ონლაინ ნახვა',
        'invoice_payment_thanks' => 'გმადლობთ თქვენი გადახდისთვის!',
        'invoice_payment_info' => 'ინვოისის ინფორმაცია',
        'invoice_payment_code' => 'ინვოისის კოდი: :invoice_code',
        'invoice_payment_view' => 'ინვოისის ნახვა',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'გამარჯობა ადმინისტრატორო,',
        'free_credit_claimed_message' => ':account_name-მა მიიღო უფასო კრედიტი :site_title-ზე',
        'free_credit_claimed_info' => 'ანგარიშის ინფორმაცია',
        'free_credit_claimed_name' => 'სახელი: :account_name',
        'free_credit_claimed_email' => 'ელფოსტა: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'გამარჯობა!',
        'password_reminder_message' => 'თქვენ იღებთ ამ ელფოსტას, რადგან ჩვენ მივიღეთ პაროლის აღდგენის მოთხოვნა თქვენი ანგარიშისთვის.',
        'password_reminder_button' => 'პაროლის აღდგენა',
        'password_reminder_no_action' => 'თუ თქვენ არ მოითხოვეთ პაროლის აღდგენა, არანაირი ქმედება არ არის საჭირო.',
        'password_reminder_regards' => 'პატივისცემით,',
        'password_reminder_trouble' => 'თუ პრობლემა გაქვთ "პაროლის აღდგენა" ღილაკზე დაჭერით, დააკოპირეთ და ჩასვით ქვემოთ მოცემული URL თქვენს ვებ ბრაუზერში: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'გამარჯობა :account_name!',
        'job_alert_hiring' => 'ვაკანსია :job_name :company_name-ში',
        'job_alert_apply_forward' => '👇 აპლიკაცია ან გადაგზავნა მეგობრისთვის: :job_url',
        'job_alert_message' => 'ახალი სამუშაო შესაძლებლობები, რომლებიც შეესაბამება თქვენს პრეფერენციებს, გამოქვეყნდა!',
        'job_alert_job_info' => 'სამუშაო: :job_name',
        'job_alert_company_info' => 'კომპანია: :company_name',
        'job_alert_view_job' => 'სამუშაოს ნახვა',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'განაცხადის დადასტურება',
        'job_application_confirmation_greeting' => 'ძვირფასო :job_application_name,',
        'job_application_confirmation_thanks' => 'გმადლობთ თქვენი ინტერესისთვის :job_name პოზიციისთვის :company_name-ში. სიამოვნებით გაცნობებთ, რომ თქვენი განაცხადი წარმატებით გაიგზავნა ჩვენი სისტემის მეშვეობით.',
        'job_application_confirmation_reviewing' => 'ჩვენი რეკრუტმენტის გუნდი განიხილავს თქვენს კვალიფიკაციებს და დაგიკავშირდებით, თუ თქვენი უნარები და გამოცდილება შეესაბამება ამ როლის მოთხოვნებს. გთხოვთ გაითვალისწინოთ, რომ განაცხადების დიდი მოცულობის გამო, ეს პროცესი შეიძლება დრო მოითხოვოს.',
        'job_application_confirmation_thanks_again' => 'გმადლობთ კიდევ ერთხელ აპლიკაციისთვის!',
        'job_application_confirmation_regards' => 'პატივისცემით,',
        'job_application_confirmation_team' => ':company_name-ის გუნდი',

        // New job application (simplified) template
        'new_job_application_greeting' => 'გამარჯობა,',
        'new_job_application_received' => 'თქვენ მიიღეთ ახალი განაცხადი სამუშაოზე.',
        'new_job_application_details' => 'განაცხადის დეტალები:',
        'new_job_application_name' => 'სახელი: :job_application_name',
        'new_job_application_position' => 'პოზიცია: :job_application_position',
        'new_job_application_email' => 'ელფოსტა: :job_application_email',
        'new_job_application_phone' => 'ტელეფონი: :job_application_phone',
    ],
];
