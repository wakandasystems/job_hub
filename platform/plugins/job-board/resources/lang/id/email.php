<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Lamaran kerja baru (ke admin)',
            'description' => 'Template email untuk mengirim pemberitahuan ke administrator ketika sistem menerima lamaran kerja baru',
            'subject' => 'Lamaran kerja baru',
        ],
        'employer-new-job-application' => [
            'title' => 'Lamaran kerja baru (ke pemberi kerja dan rekan)',
            'description' => 'Template email untuk mengirim pemberitahuan ke pemberi kerja dan rekan ketika sistem menerima lamaran kerja baru',
            'subject' => 'Lamaran kerja baru',
        ],
        'new-job-posted' => [
            'title' => 'Lowongan baru diposting',
            'description' => 'Kirim email ke admin ketika lowongan baru diposting',
            'subject' => 'Lowongan baru diposting di {{ site_title }} oleh {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Profil perusahaan baru dibuat',
            'description' => 'Kirim email ke admin ketika pemberi kerja membuat profil perusahaan baru',
            'subject' => 'Profil perusahaan baru dibuat di {{ site_title }} oleh {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Lowongan akan segera berakhir',
            'description' => 'Kirim email ke pembuat jika lowongan mereka akan berakhir dalam 3 hari ke depan',
            'subject' => 'Lowongan Anda "{{ job_name }}" akan berakhir dalam {{ job_expired_after }} hari',
        ],
        'job-renewed' => [
            'title' => 'Lowongan diperpanjang',
            'description' => 'Kirim email ke pembuat ketika lowongan mereka diperpanjang',
            'subject' => 'Lowongan Anda "{{ job_name }}" telah diperpanjang secara otomatis',
        ],
        'payment-receipt' => [
            'title' => 'Bukti pembayaran',
            'description' => 'Kirim pemberitahuan ke pengguna ketika mereka membeli kredit',
            'subject' => 'Bukti pembayaran untuk paket {{ package_name }} di {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Akun terdaftar',
            'description' => 'Kirim pemberitahuan ke admin ketika pemberi kerja/pencari kerja baru mendaftar',
            'subject' => '{{ account_type }} baru terdaftar di {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Konfirmasi email',
            'description' => 'Kirim email ke pengguna ketika mereka mendaftar akun untuk memverifikasi email mereka',
            'subject' => 'Pemberitahuan Konfirmasi Email',
        ],
        'password-reminder' => [
            'title' => 'Reset kata sandi',
            'description' => 'Kirim email ke pengguna ketika meminta reset kata sandi',
            'subject' => 'Reset Kata Sandi',
        ],
        'free-credit-claimed' => [
            'title' => 'Kredit gratis diklaim',
            'description' => 'Kirim pemberitahuan ke admin ketika kredit gratis diklaim',
            'subject' => '{{ account_name }} telah mengklaim kredit gratis di {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Pembayaran diterima',
            'description' => 'Kirim pemberitahuan ke admin ketika seseorang membeli kredit',
            'subject' => 'Pembayaran diterima dari {{ account_name }} di {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Detail Pembayaran Invoice',
            'description' => 'Kirim pemberitahuan ke pelanggan yang melakukan pembayaran posting lowongan',
            'subject' => 'Pembayaran diterima dari {{ account_name }} di {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Lowongan baru diposting',
            'description' => 'Kirim email ke pencari kerja ketika lowongan baru diposting',
            'subject' => 'Lowongan {{ job_name }} di {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Lowongan disetujui',
            'description' => 'Kirim email ke pembuat ketika lowongan mereka disetujui',
            'subject' => 'Lowongan Anda "{{ job_name }}" telah disetujui',
        ],
        'company-approved' => [
            'title' => 'Perusahaan disetujui',
            'description' => 'Kirim email ke pembuat ketika perusahaan mereka disetujui',
            'subject' => 'Perusahaan Anda "{{ company_name }}" telah disetujui',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Konfirmasi lamaran kerja',
            'description' => 'Kirim email ke pencari kerja ketika mereka melamar pekerjaan',
            'subject' => 'Konfirmasi Lamaran untuk {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Nama',
        'position' => 'Posisi',
        'email' => 'Email',
        'phone' => 'Telepon',
        'summary' => 'Ringkasan',
        'resume' => 'Resume',
        'cover_letter' => 'Surat Lamaran',
        'job_application' => 'Lamaran kerja',
        'job_name' => 'Nama lowongan',
        'job_url' => 'URL lowongan',
        'job_author' => 'Pembuat lowongan',
        'company_name' => 'Nama perusahaan',
        'company_url' => 'URL perusahaan',
        'employer_name' => 'Nama pemberi kerja',
        'job_list' => 'URL daftar lowongan',
        'job_expired_after' => 'Lowongan berakhir setelah x hari',
        'account_name' => 'Nama akun',
        'account_email' => 'Email akun',
        'package_name' => 'Nama paket',
        'package_price' => 'Harga',
        'package_percent_discount' => 'Persentase diskon',
        'package_number_of_listings' => 'Jumlah listing',
        'package_price_per_credit' => 'Harga per kredit',
        'account_type' => 'Tipe akun (pemberi kerja/pencari kerja)',
        'verify_link' => 'Tautan verifikasi',
        'reset_link' => 'Tautan reset',
        'invoice_code' => 'Kode Invoice',
        'invoice_link' => 'Tautan Invoice',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hai Admin!',
        'account_registered_new_account' => ':account_type baru terdaftar:',
        'account_registered_name' => 'Nama: <strong>:account_name</strong>',
        'account_registered_email' => 'Email: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Halo, Kami menerima lamaran kerja baru dari :site_title!',
        'admin_job_application_name' => 'Nama: :job_application_name',
        'admin_job_application_position' => 'Posisi: :job_application_position',
        'admin_job_application_email' => 'Email: :job_application_email',
        'admin_job_application_phone' => 'Telepon: :job_application_phone',
        'admin_job_application_summary' => 'Ringkasan: :job_application_summary',
        'admin_job_application_resume' => 'Resume: :job_application_resume',
        'admin_job_application_cover_letter' => 'Surat Lamaran: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Halo, Kami menerima lamaran kerja baru dari :site_title!',
        'employer_job_application_name' => 'Nama: :job_application_name',
        'employer_job_application_position' => 'Posisi: :job_application_position',
        'employer_job_application_email' => 'Email: :job_application_email',
        'employer_job_application_phone' => 'Telepon: :job_application_phone',
        'employer_job_application_summary' => 'Ringkasan: :job_application_summary',
        'employer_job_application_resume' => 'Resume: :job_application_resume',
        'employer_job_application_cover_letter' => 'Surat Lamaran: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Perusahaan Disetujui',
        'company_approved_greeting' => 'Hai,',
        'company_approved_message' => 'Kami dengan senang hati menginformasikan bahwa perusahaan Anda telah disetujui dan sekarang aktif di platform kami.',
        'company_approved_info' => 'Informasi Perusahaan',
        'company_approved_name' => 'Nama: <strong>:company_name</strong>',
        'company_approved_view' => 'Lihat',
        'company_approved_here' => 'di sini',

        // Confirm email template
        'confirm_email_greeting' => 'Halo!',
        'confirm_email_message' => 'Silakan verifikasi alamat email Anda untuk mengakses website ini. Klik tombol di bawah untuk memverifikasi email Anda.',
        'confirm_email_button' => 'Verifikasi sekarang',
        'confirm_email_regards' => 'Salam,',
        'confirm_email_trouble' => 'Jika Anda mengalami masalah mengklik tombol "Verifikasi sekarang", salin dan tempel URL berikut ke browser web Anda: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Lowongan Disetujui',
        'job_approved_greeting' => 'Hai :job_author,',
        'job_approved_message' => 'Kami dengan senang hati menginformasikan bahwa listing lowongan Anda telah disetujui dan sekarang aktif di platform kami.',
        'job_approved_info' => 'Informasi Lowongan',
        'job_approved_job_title' => 'Judul Lowongan: <strong>:job_name</strong>',
        'job_approved_view' => 'Lihat',
        'job_approved_here' => 'di sini',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hai :job_author!',
        'job_expired_soon_message' => 'Lowongan Anda <a href=":job_url">:job_name</a> akan berakhir dalam :job_expired_after hari.',
        'job_expired_soon_renew' => 'Silakan <a href=":job_list">klik di sini</a> untuk memperpanjang lowongan Anda.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hai :job_author!',
        'job_renewed_message' => 'Lowongan Anda <a href=":job_url">:job_name</a> telah diperpanjang secara otomatis.',

        // New job posted email template
        'new_job_posted_title' => 'Lowongan Baru Diposting',
        'new_job_posted_admin_greeting' => 'Hai Admin,',
        'new_job_posted_message' => 'Kami dengan senang hati menginformasikan bahwa listing lowongan baru telah diposting oleh pemberi kerja di platform kami.',
        'new_job_posted_info' => 'Posting Lowongan',
        'new_job_posted_employer' => 'Pemberi Kerja: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Judul Lowongan: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Tautan Panel Admin',
        'new_job_posted_here' => 'di sini',

        // New company profile created email template
        'new_company_profile_title' => 'Profil Perusahaan Baru Dibuat',
        'new_company_profile_admin_greeting' => 'Hai Admin!',
        'new_company_profile_message' => 'Profil perusahaan baru dibuat oleh :employer_name ":company_name"',
        'new_company_profile_info' => 'Informasi Perusahaan',
        'new_company_profile_employer' => 'Pemberi Kerja: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Nama Perusahaan: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Tautan Panel Admin',
        'new_company_profile_here' => 'di sini',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hai :account_name!',
        'payment_receipt_message' => 'Bukti pembayaran untuk pembelian Anda:',
        'payment_receipt_package' => 'Paket: :package_name',
        'payment_receipt_price' => 'Harga: :package_price_per_credit/kredit',
        'payment_receipt_total' => 'Total: :package_price untuk :package_number_of_listings kredit',
        'payment_receipt_save' => '(Hemat :package_percent_discount%)',
        'payment_receipt_thanks' => 'Terima kasih atas pembayaran Anda!',
        'payment_receipt_info' => 'Informasi Pembayaran',
        'payment_receipt_amount' => 'Jumlah: :package_price',
        'payment_receipt_invoice' => 'Kode Invoice: :invoice_code',
        'payment_receipt_view_invoice' => 'Lihat Invoice',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hai Admin!',
        'payment_received_message' => 'Pembayaran diterima dari :account_name:',
        'payment_received_account' => 'Akun: :account_name (:account_email)',
        'payment_received_package' => 'Paket: :package_name',
        'payment_received_price' => 'Harga: :package_price_per_credit/kredit',
        'payment_received_total' => 'Total: :package_price untuk :package_number_of_listings kredit',
        'payment_received_save' => '(Hemat :package_percent_discount%)',
        'payment_received_info' => 'Informasi Pembayaran',
        'payment_received_customer' => 'Pelanggan: :account_name',
        'payment_received_amount' => 'Jumlah: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hai :account_name,',
        'invoice_payment_from' => 'Anda menerima email dari :site_title',
        'invoice_payment_attached' => 'Invoice #:invoice_code dilampirkan dengan email ini.',
        'invoice_payment_view_online' => 'Lihat Online',
        'invoice_payment_thanks' => 'Terima kasih atas pembayaran Anda!',
        'invoice_payment_info' => 'Informasi Invoice',
        'invoice_payment_code' => 'Kode Invoice: :invoice_code',
        'invoice_payment_view' => 'Lihat Invoice',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hai Admin,',
        'free_credit_claimed_message' => ':account_name telah mengklaim kredit gratis di :site_title',
        'free_credit_claimed_info' => 'Informasi Akun',
        'free_credit_claimed_name' => 'Nama: :account_name',
        'free_credit_claimed_email' => 'Email: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Halo!',
        'password_reminder_message' => 'Anda menerima email ini karena kami menerima permintaan reset kata sandi untuk akun Anda.',
        'password_reminder_button' => 'Reset kata sandi',
        'password_reminder_no_action' => 'Jika Anda tidak meminta reset kata sandi, tidak diperlukan tindakan lebih lanjut.',
        'password_reminder_regards' => 'Salam,',
        'password_reminder_trouble' => 'Jika Anda mengalami masalah mengklik tombol "Reset Kata Sandi", salin dan tempel URL berikut ke browser web Anda: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hai :account_name!',
        'job_alert_hiring' => 'Lowongan :job_name di :company_name',
        'job_alert_apply_forward' => '👇 Lamar atau Teruskan ke teman: :job_url',
        'job_alert_message' => 'Peluang kerja baru yang sesuai dengan preferensi Anda telah diposting!',
        'job_alert_job_info' => 'Lowongan: :job_name',
        'job_alert_company_info' => 'Perusahaan: :company_name',
        'job_alert_view_job' => 'Lihat Lowongan',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Konfirmasi Lamaran Kerja',
        'job_application_confirmation_greeting' => 'Kepada :job_application_name,',
        'job_application_confirmation_thanks' => 'Terima kasih atas minat Anda pada posisi :job_name di :company_name. Kami dengan senang hati mengkonfirmasi bahwa lamaran Anda telah berhasil dikirim melalui sistem kami.',
        'job_application_confirmation_reviewing' => 'Tim rekrutmen kami sedang meninjau kualifikasi Anda, dan kami akan menghubungi Anda jika keterampilan dan pengalaman Anda sesuai dengan persyaratan untuk peran ini. Harap dicatat bahwa karena tingginya volume lamaran, proses ini mungkin memakan waktu.',
        'job_application_confirmation_thanks_again' => 'Terima kasih sekali lagi telah melamar!',
        'job_application_confirmation_regards' => 'Salam terbaik,',
        'job_application_confirmation_team' => 'Tim :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Halo,',
        'new_job_application_received' => 'Anda telah menerima lamaran kerja baru.',
        'new_job_application_details' => 'Detail Lamaran:',
        'new_job_application_name' => 'Nama: :job_application_name',
        'new_job_application_position' => 'Posisi: :job_application_position',
        'new_job_application_email' => 'Email: :job_application_email',
        'new_job_application_phone' => 'Telepon: :job_application_phone',
    ],
];
