<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Permohonan pekerjaan baharu (kepada pengguna admin)',
            'description' => 'Templat e-mel untuk menghantar notis kepada pentadbir apabila sistem menerima permohonan pekerjaan baharu',
            'subject' => 'Permohonan pekerjaan baharu',
        ],
        'employer-new-job-application' => [
            'title' => 'Permohonan pekerjaan baharu (kepada majikan dan rakan sekerja)',
            'description' => 'Templat e-mel untuk menghantar notis kepada majikan dan rakan sekerja apabila sistem menerima permohonan pekerjaan baharu',
            'subject' => 'Permohonan pekerjaan baharu',
        ],
        'new-job-posted' => [
            'title' => 'Pekerjaan baharu diiklankan',
            'description' => 'Hantar e-mel kepada admin apabila pekerjaan baharu diiklankan',
            'subject' => 'Pekerjaan baharu diiklankan di {{ site_title }} oleh {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Profil syarikat baharu dicipta',
            'description' => 'Hantar e-mel kepada admin apabila majikan mencipta profil syarikat baharu',
            'subject' => 'Profil syarikat baharu dicipta di {{ site_title }} oleh {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Pekerjaan akan tamat tempoh tidak lama lagi',
            'description' => 'Hantar e-mel kepada penulis jika pekerjaan mereka akan tamat tempoh dalam 3 hari akan datang',
            'subject' => 'Pekerjaan anda "{{ job_name }}" akan tamat tempoh dalam {{ job_expired_after }} hari',
        ],
        'job-renewed' => [
            'title' => 'Pekerjaan diperbaharui',
            'description' => 'Hantar e-mel kepada penulis apabila pekerjaan mereka diperbaharui',
            'subject' => 'Pekerjaan anda "{{ job_name }}" telah diperbaharui secara automatik',
        ],
        'payment-receipt' => [
            'title' => 'Resit pembayaran',
            'description' => 'Hantar notifikasi kepada pengguna apabila mereka membeli kredit',
            'subject' => 'Resit pembayaran untuk pakej {{ package_name }} di {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Akaun didaftarkan',
            'description' => 'Hantar notifikasi kepada admin apabila majikan/pencari kerja baharu mendaftar',
            'subject' => '{{ account_type }} baharu didaftarkan di {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Sahkan e-mel',
            'description' => 'Hantar e-mel kepada pengguna apabila mereka mendaftar akaun untuk mengesahkan e-mel mereka',
            'subject' => 'Notifikasi Pengesahan E-mel',
        ],
        'password-reminder' => [
            'title' => 'Set semula kata laluan',
            'description' => 'Hantar e-mel kepada pengguna apabila meminta set semula kata laluan',
            'subject' => 'Set Semula Kata Laluan',
        ],
        'free-credit-claimed' => [
            'title' => 'Kredit percuma dituntut',
            'description' => 'Hantar notifikasi kepada admin apabila kredit percuma dituntut',
            'subject' => '{{ account_name }} telah menuntut kredit percuma di {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Pembayaran diterima',
            'description' => 'Hantar notifikasi kepada admin apabila seseorang membeli kredit',
            'subject' => 'Pembayaran diterima dari {{ account_name }} di {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Butiran Pembayaran Invois',
            'description' => 'Hantar notifikasi kepada pelanggan yang membuat pembayaran iklan pekerjaan',
            'subject' => 'Pembayaran diterima dari {{ account_name }} di {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Pekerjaan baharu diiklankan',
            'description' => 'Hantar e-mel kepada pencari kerja apabila pekerjaan baharu diiklankan',
            'subject' => 'Merekrut {{ job_name }} di {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Pekerjaan diluluskan',
            'description' => 'Hantar e-mel kepada penulis apabila pekerjaan mereka diluluskan',
            'subject' => 'Pekerjaan anda "{{ job_name }}" telah diluluskan',
        ],
        'company-approved' => [
            'title' => 'Syarikat diluluskan',
            'description' => 'Hantar e-mel kepada penulis apabila syarikat mereka diluluskan',
            'subject' => 'Syarikat anda "{{ company_name }}" telah diluluskan',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Pengesahan permohonan pekerjaan',
            'description' => 'Hantar e-mel kepada pencari kerja apabila mereka memohon pekerjaan',
            'subject' => 'Pengesahan Permohonan untuk {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Nama',
        'position' => 'Jawatan',
        'email' => 'E-mel',
        'phone' => 'Telefon',
        'summary' => 'Ringkasan',
        'resume' => 'Resume',
        'cover_letter' => 'Surat Iringan',
        'job_application' => 'Permohonan pekerjaan',
        'job_name' => 'Nama pekerjaan',
        'job_url' => 'URL pekerjaan',
        'job_author' => 'Penulis pekerjaan',
        'company_name' => 'Nama syarikat',
        'company_url' => 'URL syarikat',
        'employer_name' => 'Nama majikan',
        'job_list' => 'URL senarai pekerjaan',
        'job_expired_after' => 'Pekerjaan tamat tempoh selepas x hari',
        'account_name' => 'Nama akaun',
        'account_email' => 'E-mel akaun',
        'package_name' => 'Nama pakej',
        'package_price' => 'Harga',
        'package_percent_discount' => 'Peratusan diskaun',
        'package_number_of_listings' => 'Bilangan penyenaraian',
        'package_price_per_credit' => 'Harga setiap kredit',
        'account_type' => 'Jenis akaun (majikan/pencari kerja)',
        'verify_link' => 'Pautan pengesahan',
        'reset_link' => 'Pautan set semula',
        'invoice_code' => 'Kod Invois',
        'invoice_link' => 'Pautan Invois',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hai Admin!',
        'account_registered_new_account' => ':account_type baharu didaftarkan:',
        'account_registered_name' => 'Nama: <strong>:account_name</strong>',
        'account_registered_email' => 'E-mel: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hello, Kami menerima permohonan pekerjaan baharu dari :site_title!',
        'admin_job_application_name' => 'Nama: :job_application_name',
        'admin_job_application_position' => 'Jawatan: :job_application_position',
        'admin_job_application_email' => 'E-mel: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Ringkasan: :job_application_summary',
        'admin_job_application_resume' => 'Resume: :job_application_resume',
        'admin_job_application_cover_letter' => 'Surat Iringan: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hello, Kami menerima permohonan pekerjaan baharu dari :site_title!',
        'employer_job_application_name' => 'Nama: :job_application_name',
        'employer_job_application_position' => 'Jawatan: :job_application_position',
        'employer_job_application_email' => 'E-mel: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Ringkasan: :job_application_summary',
        'employer_job_application_resume' => 'Resume: :job_application_resume',
        'employer_job_application_cover_letter' => 'Surat Iringan: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Syarikat Diluluskan',
        'company_approved_greeting' => 'Hai,',
        'company_approved_message' => 'Kami dengan gembira memaklumkan bahawa syarikat anda telah diluluskan dan kini aktif di platform kami.',
        'company_approved_info' => 'Maklumat Syarikat',
        'company_approved_name' => 'Nama: <strong>:company_name</strong>',
        'company_approved_view' => 'Lihat',
        'company_approved_here' => 'di sini',

        // Confirm email template
        'confirm_email_greeting' => 'Hello!',
        'confirm_email_message' => 'Sila sahkan alamat e-mel anda untuk mengakses laman web ini. Klik pada butang di bawah untuk mengesahkan e-mel anda.',
        'confirm_email_button' => 'Sahkan sekarang',
        'confirm_email_regards' => 'Salam,',
        'confirm_email_trouble' => 'Jika anda menghadapi masalah mengklik butang "Sahkan sekarang", salin dan tampal URL di bawah ke pelayar web anda: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Pekerjaan Diluluskan',
        'job_approved_greeting' => 'Hai :job_author,',
        'job_approved_message' => 'Kami dengan gembira memaklumkan bahawa iklan pekerjaan anda telah diluluskan dan kini aktif di platform kami.',
        'job_approved_info' => 'Maklumat Pekerjaan',
        'job_approved_job_title' => 'Tajuk Pekerjaan: <strong>:job_name</strong>',
        'job_approved_view' => 'Lihat',
        'job_approved_here' => 'di sini',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hai :job_author!',
        'job_expired_soon_message' => 'Pekerjaan anda <a href=":job_url">:job_name</a> akan tamat tempoh dalam :job_expired_after hari.',
        'job_expired_soon_renew' => 'Sila <a href=":job_list">pergi ke sini</a> untuk memperbaharui pekerjaan anda.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hai :job_author!',
        'job_renewed_message' => 'Pekerjaan anda <a href=":job_url">:job_name</a> telah diperbaharui secara automatik.',

        // New job posted email template
        'new_job_posted_title' => 'Pekerjaan Baharu Diiklankan',
        'new_job_posted_admin_greeting' => 'Hai Admin,',
        'new_job_posted_message' => 'Kami dengan gembira memaklumkan bahawa iklan pekerjaan baharu telah diiklankan oleh majikan di platform kami.',
        'new_job_posted_info' => 'Iklan Pekerjaan',
        'new_job_posted_employer' => 'Majikan: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Tajuk Pekerjaan: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Pautan Panel Admin',
        'new_job_posted_here' => 'di sini',

        // New company profile created email template
        'new_company_profile_title' => 'Profil Syarikat Baharu Dicipta',
        'new_company_profile_admin_greeting' => 'Hai Admin!',
        'new_company_profile_message' => 'Profil syarikat baharu dicipta oleh :employer_name ":company_name"',
        'new_company_profile_info' => 'Maklumat Syarikat',
        'new_company_profile_employer' => 'Majikan: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Nama Syarikat: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Pautan Panel Admin',
        'new_company_profile_here' => 'di sini',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hai :account_name!',
        'payment_receipt_message' => 'Resit pembayaran untuk pembelian anda:',
        'payment_receipt_package' => 'Pakej: :package_name',
        'payment_receipt_price' => 'Harga: :package_price_per_credit/kredit',
        'payment_receipt_total' => 'Jumlah: :package_price untuk :package_number_of_listings kredit',
        'payment_receipt_save' => '(Jimat :package_percent_discount%)',
        'payment_receipt_thanks' => 'Terima kasih atas pembayaran anda!',
        'payment_receipt_info' => 'Maklumat Pembayaran',
        'payment_receipt_amount' => 'Jumlah: :package_price',
        'payment_receipt_invoice' => 'Kod Invois: :invoice_code',
        'payment_receipt_view_invoice' => 'Lihat Invois',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hai Admin!',
        'payment_received_message' => 'Pembayaran diterima dari :account_name:',
        'payment_received_account' => 'Akaun: :account_name (:account_email)',
        'payment_received_package' => 'Pakej: :package_name',
        'payment_received_price' => 'Harga: :package_price_per_credit/kredit',
        'payment_received_total' => 'Jumlah: :package_price untuk :package_number_of_listings kredit',
        'payment_received_save' => '(Jimat :package_percent_discount%)',
        'payment_received_info' => 'Maklumat Pembayaran',
        'payment_received_customer' => 'Pelanggan: :account_name',
        'payment_received_amount' => 'Jumlah: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hai :account_name,',
        'invoice_payment_from' => 'Anda menerima e-mel dari :site_title',
        'invoice_payment_attached' => 'Invois #:invoice_code dilampirkan dengan e-mel ini.',
        'invoice_payment_view_online' => 'Lihat Dalam Talian',
        'invoice_payment_thanks' => 'Terima kasih atas pembayaran anda!',
        'invoice_payment_info' => 'Maklumat Invois',
        'invoice_payment_code' => 'Kod Invois: :invoice_code',
        'invoice_payment_view' => 'Lihat Invois',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hai Admin,',
        'free_credit_claimed_message' => ':account_name telah menuntut kredit percuma di :site_title',
        'free_credit_claimed_info' => 'Maklumat Akaun',
        'free_credit_claimed_name' => 'Nama: :account_name',
        'free_credit_claimed_email' => 'E-mel: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hello!',
        'password_reminder_message' => 'Anda menerima e-mel ini kerana kami menerima permintaan set semula kata laluan untuk akaun anda.',
        'password_reminder_button' => 'Set semula kata laluan',
        'password_reminder_no_action' => 'Jika anda tidak meminta set semula kata laluan, tiada tindakan lanjut diperlukan.',
        'password_reminder_regards' => 'Salam,',
        'password_reminder_trouble' => 'Jika anda menghadapi masalah mengklik butang "Set Semula Kata Laluan", salin dan tampal URL di bawah ke pelayar web anda: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hai :account_name!',
        'job_alert_hiring' => 'Merekrut :job_name di :company_name',
        'job_alert_apply_forward' => 'Mohon atau Kongsi kepada rakan: :job_url',
        'job_alert_message' => 'Peluang pekerjaan baharu yang sepadan dengan pilihan anda telah diiklankan!',
        'job_alert_job_info' => 'Pekerjaan: :job_name',
        'job_alert_company_info' => 'Syarikat: :company_name',
        'job_alert_view_job' => 'Lihat Pekerjaan',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Pengesahan Permohonan Pekerjaan',
        'job_application_confirmation_greeting' => 'Yang Berhormat :job_application_name,',
        'job_application_confirmation_thanks' => 'Terima kasih atas minat anda terhadap jawatan :job_name di :company_name. Kami dengan gembira mengesahkan bahawa permohonan anda telah berjaya dihantar melalui sistem kami.',
        'job_application_confirmation_reviewing' => 'Pasukan pengambilan kami sedang menyemak kelayakan anda, dan kami akan menghubungi anda jika kemahiran dan pengalaman anda sepadan dengan keperluan untuk peranan ini. Sila ambil perhatian bahawa disebabkan oleh jumlah permohonan yang tinggi, proses ini mungkin mengambil sedikit masa.',
        'job_application_confirmation_thanks_again' => 'Terima kasih sekali lagi kerana memohon!',
        'job_application_confirmation_regards' => 'Salam hormat,',
        'job_application_confirmation_team' => 'Pasukan :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hello,',
        'new_job_application_received' => 'Anda telah menerima permohonan pekerjaan baharu.',
        'new_job_application_details' => 'Butiran Permohonan:',
        'new_job_application_name' => 'Nama: :job_application_name',
        'new_job_application_position' => 'Jawatan: :job_application_position',
        'new_job_application_email' => 'E-mel: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
