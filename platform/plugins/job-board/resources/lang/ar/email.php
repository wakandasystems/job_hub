<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'طلب توظيف جديد (للمشرفين)',
            'description' => 'نموذج البريد الإلكتروني لإرسال إشعار للمديرين عند وصول طلب توظيف جديد للنظام',
            'subject' => 'طلب توظيف جديد',
        ],
        'employer-new-job-application' => [
            'title' => 'طلب توظيف جديد (لصاحب العمل والزملاء)',
            'description' => 'نموذج البريد الإلكتروني لإرسال إشعار لصاحب العمل والزملاء عند وصول طلب توظيف جديد للنظام',
            'subject' => 'طلب توظيف جديد',
        ],
        'new-job-posted' => [
            'title' => 'وظيفة جديدة تم نشرها',
            'description' => 'إرسال بريد إلكتروني للمدير عند نشر وظيفة جديدة',
            'subject' => 'تم نشر وظيفة جديدة على {{ site_title }} بواسطة {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'ملف شركة جديد تم إنشاؤه',
            'description' => 'إرسال بريد إلكتروني للمدير عندما ينشئ صاحب عمل ملف شركة جديد',
            'subject' => 'تم إنشاء ملف شركة جديد على {{ site_title }} بواسطة {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'الوظيفة ستنتهي قريباً',
            'description' => 'إرسال بريد إلكتروني للكاتب إذا كانت وظيفته ستنتهي في الأيام الثلاثة القادمة',
            'subject' => 'وظيفتك "{{ job_name }}" ستنتهي في {{ job_expired_after }} أيام',
        ],
        'job-renewed' => [
            'title' => 'تم تجديد الوظيفة',
            'description' => 'إرسال بريد إلكتروني للكاتب عند تجديد وظيفته',
            'subject' => 'تم تجديد وظيفتك "{{ job_name }}" تلقائياً',
        ],
        'payment-receipt' => [
            'title' => 'إيصال الدفع',
            'description' => 'إرسال إشعار للمستخدم عند شراء الرصيد',
            'subject' => 'إيصال الدفع للباقة {{ package_name }} على {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'تم تسجيل الحساب',
            'description' => 'إرسال إشعار للمدير عند تسجيل صاحب عمل/باحث عن عمل جديد',
            'subject' => 'تم تسجيل {{ account_type }} جديد على {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'تأكيد البريد الإلكتروني',
            'description' => 'إرسال بريد إلكتروني للمستخدم عند تسجيل حساب للتحقق من بريدهم الإلكتروني',
            'subject' => 'إشعار تأكيد البريد الإلكتروني',
        ],
        'password-reminder' => [
            'title' => 'إعادة تعيين كلمة المرور',
            'description' => 'إرسال بريد إلكتروني للمستخدم عند طلب إعادة تعيين كلمة المرور',
            'subject' => 'إعادة تعيين كلمة المرور',
        ],
        'free-credit-claimed' => [
            'title' => 'تم الحصول على رصيد مجاني',
            'description' => 'إرسال إشعار للمدير عند الحصول على رصيد مجاني',
            'subject' => '{{ account_name }} قد حصل على رصيد مجاني على {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'تم استلام الدفع',
            'description' => 'إرسال إشعار للمدير عندما يشتري شخص ما الرصيد',
            'subject' => 'تم استلام دفع من {{ account_name }} على {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'تفاصيل دفع الفاتورة',
            'description' => 'إرسال إشعار للعميل الذي يدفع مقابل نشر الوظيفة',
            'subject' => 'تم استلام دفع من {{ account_name }} على {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'وظيفة جديدة تم نشرها',
            'description' => 'إرسال بريد إلكتروني لباحث العمل عند نشر وظيفة جديدة',
            'subject' => 'توظيف {{ job_name }} في {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'تم قبول الوظيفة',
            'description' => 'إرسال بريد إلكتروني للكاتب عند قبول وظيفته',
            'subject' => 'تم قبول وظيفتك "{{ job_name }}"',
        ],
        'company-approved' => [
            'title' => 'تم قبول الشركة',
            'description' => 'إرسال بريد إلكتروني للكاتب عند قبول شركته',
            'subject' => 'تم قبول شركتك "{{ company_name }}"',
        ],
        'job-seeker-applied-job' => [
            'title' => 'تأكيد طلب التوظيف',
            'description' => 'إرسال بريد إلكتروني لباحث العمل عند التقدم لوظيفة',
            'subject' => 'تأكيد الطلب لـ {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'الاسم',
        'position' => 'المنصب',
        'email' => 'البريد الإلكتروني',
        'phone' => 'الهاتف',
        'summary' => 'الملخص',
        'resume' => 'السيرة الذاتية',
        'cover_letter' => 'خطاب التغطية',
        'job_application' => 'طلب التوظيف',
        'job_name' => 'اسم الوظيفة',
        'job_url' => 'رابط الوظيفة',
        'job_author' => 'كاتب الوظيفة',
        'company_name' => 'اسم الشركة',
        'company_url' => 'رابط الشركة',
        'employer_name' => 'اسم صاحب العمل',
        'job_list' => 'رابط قائمة الوظائف',
        'job_expired_after' => 'الوظيفة ستنتهي بعد x أيام',
        'account_name' => 'اسم الحساب',
        'account_email' => 'بريد الحساب الإلكتروني',
        'package_name' => 'اسم الباقة',
        'package_price' => 'السعر',
        'package_percent_discount' => 'نسبة الخصم',
        'package_number_of_listings' => 'عدد الإعلانات',
        'package_price_per_credit' => 'السعر لكل رصيد',
        'account_type' => 'نوع الحساب (صاحب عمل/باحث عن عمل)',
        'verify_link' => 'رابط التحقق',
        'reset_link' => 'رابط إعادة التعيين',
        'invoice_code' => 'رمز الفاتورة',
        'invoice_link' => 'رابط الفاتورة',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'مرحباً أيها المدير!',
        'account_registered_new_account' => 'تم تسجيل :account_type جديد:',
        'account_registered_name' => 'الاسم: <strong>:account_name</strong>',
        'account_registered_email' => 'البريد الإلكتروني: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'مرحباً، تلقينا طلب توظيف جديد من :site_title!',
        'admin_job_application_name' => 'الاسم: :job_application_name',
        'admin_job_application_position' => 'المنصب: :job_application_position',
        'admin_job_application_email' => 'البريد الإلكتروني: :job_application_email',
        'admin_job_application_phone' => 'الهاتف: :job_application_phone',
        'admin_job_application_summary' => 'الملخص: :job_application_summary',
        'admin_job_application_resume' => 'السيرة الذاتية: :job_application_resume',
        'admin_job_application_cover_letter' => 'خطاب التغطية: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'مرحباً، تلقينا طلب توظيف جديد من :site_title!',
        'employer_job_application_name' => 'الاسم: :job_application_name',
        'employer_job_application_position' => 'المنصب: :job_application_position',
        'employer_job_application_email' => 'البريد الإلكتروني: :job_application_email',
        'employer_job_application_phone' => 'الهاتف: :job_application_phone',
        'employer_job_application_summary' => 'الملخص: :job_application_summary',
        'employer_job_application_resume' => 'السيرة الذاتية: :job_application_resume',
        'employer_job_application_cover_letter' => 'خطاب التغطية: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'تم قبول الشركة',
        'company_approved_greeting' => 'مرحباً،',
        'company_approved_message' => 'يسعدنا إبلاغك أن شركتك تم قبولها وهي الآن نشطة على منصتنا.',
        'company_approved_info' => 'معلومات الشركة',
        'company_approved_name' => 'الاسم: <strong>:company_name</strong>',
        'company_approved_view' => 'عرض',
        'company_approved_here' => 'هنا',

        // Confirm email template
        'confirm_email_greeting' => 'مرحباً!',
        'confirm_email_message' => 'يرجى التحقق من عنوان بريدك الإلكتروني للوصول إلى هذا الموقع. انقر على الزر أدناه للتحقق من بريدك الإلكتروني..',
        'confirm_email_button' => 'تحقق الآن',
        'confirm_email_regards' => 'تحياتنا،',
        'confirm_email_trouble' => 'إذا كنت تواجه مشكلة في النقر على زر "تحقق الآن"، انسخ والصق الرابط أدناه في متصفح الويب الخاص بك: :verify_link',

        // Job approved email template
        'job_approved_title' => 'تم قبول الوظيفة',
        'job_approved_greeting' => 'مرحباً :job_author،',
        'job_approved_message' => 'يسعدنا إبلاغك أن إعلان وظيفتك تم قبوله وهو الآن نشط على منصتنا.',
        'job_approved_info' => 'معلومات الوظيفة',
        'job_approved_job_title' => 'عنوان الوظيفة: <strong>:job_name</strong>',
        'job_approved_view' => 'عرض',
        'job_approved_here' => 'هنا',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'مرحباً :job_author!',
        'job_expired_soon_message' => 'وظيفتك <a href=":job_url">:job_name</a> ستنتهي في :job_expired_after أيام.',
        'job_expired_soon_renew' => 'يرجى <a href=":job_list">الذهاب إلى هنا</a> لتجديد وظيفتك.',

        // Job renewed email template
        'job_renewed_greeting' => 'مرحباً :job_author!',
        'job_renewed_message' => 'تم تجديد وظيفتك <a href=":job_url">:job_name</a> تلقائياً.',

        // New job posted email template
        'new_job_posted_title' => 'وظيفة جديدة تم نشرها',
        'new_job_posted_admin_greeting' => 'مرحباً أيها المدير،',
        'new_job_posted_message' => 'يسعدنا إبلاغك أن إعلان وظيفة جديد تم نشره بواسطة صاحب عمل على منصتنا.',
        'new_job_posted_info' => 'منشور الوظيفة',
        'new_job_posted_employer' => 'صاحب العمل: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'عنوان الوظيفة: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'رابط لوحة الإدارة',
        'new_job_posted_here' => 'هنا',

        // New company profile created email template
        'new_company_profile_title' => 'تم إنشاء ملف شركة جديد',
        'new_company_profile_admin_greeting' => 'مرحباً أيها المدير!',
        'new_company_profile_message' => 'تم إنشاء ملف شركة جديد بواسطة :employer_name ":company_name"',
        'new_company_profile_info' => 'معلومات الشركة',
        'new_company_profile_employer' => 'صاحب العمل: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'اسم الشركة: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'رابط لوحة الإدارة',
        'new_company_profile_here' => 'هنا',

        // Payment receipt email template
        'payment_receipt_greeting' => 'مرحباً :account_name!',
        'payment_receipt_message' => 'إيصال الدفع لمشترياتك:',
        'payment_receipt_package' => 'الباقة: :package_name',
        'payment_receipt_price' => 'السعر: :package_price_per_credit/رصيد',
        'payment_receipt_total' => 'المجموع: :package_price لـ :package_number_of_listings رصيد',
        'payment_receipt_save' => '(وفر :package_percent_discount%)',
        'payment_receipt_thanks' => 'شكراً لك على دفعتك!',
        'payment_receipt_info' => 'معلومات الدفع',
        'payment_receipt_amount' => 'المبلغ: :package_price',
        'payment_receipt_invoice' => 'رمز الفاتورة: :invoice_code',
        'payment_receipt_view_invoice' => 'عرض الفاتورة',

        // Payment received email template
        'payment_received_admin_greeting' => 'مرحباً أيها المدير!',
        'payment_received_message' => 'تم استلام دفع من :account_name:',
        'payment_received_account' => 'الحساب: :account_name (:account_email)',
        'payment_received_package' => 'الباقة: :package_name',
        'payment_received_price' => 'السعر: :package_price_per_credit/رصيد',
        'payment_received_total' => 'المجموع: :package_price لـ :package_number_of_listings رصيد',
        'payment_received_save' => '(وفر :package_percent_discount%)',
        'payment_received_info' => 'معلومات الدفع',
        'payment_received_customer' => 'العميل: :account_name',
        'payment_received_amount' => 'المبلغ: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'مرحباً :account_name،',
        'invoice_payment_from' => 'أنت تتلقى بريد إلكتروني من :site_title',
        'invoice_payment_attached' => 'الفاتورة #:invoice_code مرفقة مع هذا البريد الإلكتروني.',
        'invoice_payment_view_online' => 'عرض أونلاين',
        'invoice_payment_thanks' => 'شكراً لك على دفعتك!',
        'invoice_payment_info' => 'معلومات الفاتورة',
        'invoice_payment_code' => 'رمز الفاتورة: :invoice_code',
        'invoice_payment_view' => 'عرض الفاتورة',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'مرحباً أيها المدير،',
        'free_credit_claimed_message' => ':account_name قد حصل على رصيد مجاني على :site_title',
        'free_credit_claimed_info' => 'معلومات الحساب',
        'free_credit_claimed_name' => 'الاسم: :account_name',
        'free_credit_claimed_email' => 'البريد الإلكتروني: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'مرحباً!',
        'password_reminder_message' => 'أنت تتلقى هذا البريد الإلكتروني لأننا تلقينا طلب إعادة تعيين كلمة المرور لحسابك.',
        'password_reminder_button' => 'إعادة تعيين كلمة المرور',
        'password_reminder_no_action' => 'إذا لم تطلب إعادة تعيين كلمة المرور، فلا حاجة لاتخاذ أي إجراء إضافي.',
        'password_reminder_regards' => 'تحياتنا،',
        'password_reminder_trouble' => 'إذا كنت تواجه مشكلة في النقر على زر "إعادة تعيين كلمة المرور"، انسخ والصق الرابط أدناه في متصفح الويب الخاص بك: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'مرحباً :account_name!',
        'job_alert_hiring' => 'توظيف :job_name في :company_name',
        'job_alert_apply_forward' => '👇 تقدم أو أرسل لصديق: :job_url',
        'job_alert_message' => 'تم نشر فرص عمل جديدة تطابق تفضيلاتك!',
        'job_alert_job_info' => 'الوظيفة: :job_name',
        'job_alert_company_info' => 'الشركة: :company_name',
        'job_alert_view_job' => 'عرض الوظيفة',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'تأكيد طلب التوظيف',
        'job_application_confirmation_greeting' => 'عزيزي :job_application_name،',
        'job_application_confirmation_thanks' => 'شكراً لك على اهتمامك بمنصب :job_name في :company_name. يسعدنا أن نؤكد أن طلبك تم تقديمه بنجاح من خلال نظامنا.',
        'job_application_confirmation_reviewing' => 'فريق التوظيف لدينا يراجع مؤهلاتك، وسنتواصل معك إذا كانت مهاراتك وخبرتك تتطابق مع متطلبات هذا المنصب. يرجى ملاحظة أنه بسبب الحجم الكبير للطلبات، قد تستغرق هذه العملية بعض الوقت.',
        'job_application_confirmation_thanks_again' => 'شكراً لك مرة أخرى على التقديم!',
        'job_application_confirmation_regards' => 'مع أطيب التحيات،',
        'job_application_confirmation_team' => 'فريق :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'مرحباً،',
        'new_job_application_received' => 'لقد تلقيت طلب توظيف جديد.',
        'new_job_application_details' => 'تفاصيل الطلب:',
        'new_job_application_name' => 'الاسم: :job_application_name',
        'new_job_application_position' => 'المنصب: :job_application_position',
        'new_job_application_email' => 'البريد الإلكتروني: :job_application_email',
        'new_job_application_phone' => 'الهاتف: :job_application_phone',
    ],
];
