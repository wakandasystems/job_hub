<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => '新职位申请（发送给管理员）',
            'description' => '系统收到新职位申请时发送给管理员的邮件模板',
            'subject' => '新职位申请',
        ],
        'employer-new-job-application' => [
            'title' => '新职位申请（发送给雇主和同事）',
            'description' => '系统收到新职位申请时发送给雇主和同事的邮件模板',
            'subject' => '新职位申请',
        ],
        'new-job-posted' => [
            'title' => '新职位发布',
            'description' => '发布新职位时发送给管理员的邮件',
            'subject' => '{{ job_author }} 在 {{ site_title }} 发布了新职位',
        ],
        'new-company-profile-created' => [
            'title' => '新公司资料创建',
            'description' => '雇主创建新公司资料时发送给管理员的邮件',
            'subject' => '{{ employer_name }} 在 {{ site_title }} 创建了新公司资料',
        ],
        'job-expired-soon' => [
            'title' => '职位即将过期',
            'description' => '职位即将在3天内过期时发送给作者的邮件',
            'subject' => '您的职位 "{{ job_name }}" 将在 {{ job_expired_after }} 天后过期',
        ],
        'job-renewed' => [
            'title' => '职位已续订',
            'description' => '职位续订时发送给作者的邮件',
            'subject' => '您的职位 "{{ job_name }}" 已自动续订',
        ],
        'payment-receipt' => [
            'title' => '支付收据',
            'description' => '用户购买积分时发送通知',
            'subject' => '{{ site_title }} 上 {{ package_name }} 套餐的支付收据',
        ],
        'account-registered' => [
            'title' => '账户注册',
            'description' => '新雇主/求职者注册时发送给管理员的通知',
            'subject' => '新 {{ account_type }} 在 {{ site_title }} 注册',
        ],
        'confirm-email' => [
            'title' => '确认邮箱',
            'description' => '用户注册账户时发送验证邮箱的邮件',
            'subject' => '确认邮箱通知',
        ],
        'password-reminder' => [
            'title' => '重置密码',
            'description' => '用户请求重置密码时发送的邮件',
            'subject' => '重置密码',
        ],
        'free-credit-claimed' => [
            'title' => '免费积分已领取',
            'description' => '领取免费积分时发送给管理员的通知',
            'subject' => '{{ account_name }} 在 {{ site_title }} 领取了免费积分',
        ],
        'payment-received' => [
            'title' => '收到付款',
            'description' => '有人购买积分时发送给管理员的通知',
            'subject' => '{{ site_title }} 上收到来自 {{ account_name }} 的付款',
        ],
        'invoice-payment-created' => [
            'title' => '发票付款详情',
            'description' => '发送给进行职位发布付款的客户的通知',
            'subject' => '{{ site_title }} 上收到来自 {{ account_name }} 的付款',
        ],
        'job-seeker-job-alert' => [
            'title' => '新职位发布',
            'description' => '发布新职位时发送给求职者的邮件',
            'subject' => '{{ company_name }} 招聘 {{ job_name }}',
        ],
        'job-approved' => [
            'title' => '职位已批准',
            'description' => '职位被批准时发送给作者的邮件',
            'subject' => '您的职位 "{{ job_name }}" 已被批准',
        ],
        'company-approved' => [
            'title' => '公司已批准',
            'description' => '公司被批准时发送给作者的邮件',
            'subject' => '您的公司 "{{ company_name }}" 已被批准',
        ],
        'job-seeker-applied-job' => [
            'title' => '职位申请确认',
            'description' => '求职者申请职位时发送给他们的邮件',
            'subject' => '{{ job_name }} 的申请确认',
        ],
    ],
    'variables' => [
        'name' => '姓名',
        'position' => '职位',
        'email' => '邮箱',
        'phone' => '电话',
        'summary' => '摘要',
        'resume' => '简历',
        'cover_letter' => '求职信',
        'job_application' => '职位申请',
        'job_name' => '职位名称',
        'job_url' => '职位链接',
        'job_author' => '职位发布者',
        'company_name' => '公司名称',
        'company_url' => '公司链接',
        'employer_name' => '雇主姓名',
        'job_list' => '职位列表链接',
        'job_expired_after' => '职位在x天后过期',
        'account_name' => '账户名称',
        'account_email' => '账户邮箱',
        'package_name' => '套餐名称',
        'package_price' => '价格',
        'package_percent_discount' => '折扣百分比',
        'package_number_of_listings' => '列表数量',
        'package_price_per_credit' => '每积分价格',
        'account_type' => '账户类型（雇主/求职者）',
        'verify_link' => '验证链接',
        'reset_link' => '重置链接',
        'invoice_code' => '发票代码',
        'invoice_link' => '发票链接',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => '您好，管理员！',
        'account_registered_new_account' => '新 :account_type 已注册：',
        'account_registered_name' => '姓名：<strong>:account_name</strong>',
        'account_registered_email' => '邮箱：<strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => '您好，我们从 :site_title 收到了新的职位申请！',
        'admin_job_application_name' => '姓名：:job_application_name',
        'admin_job_application_position' => '职位：:job_application_position',
        'admin_job_application_email' => '邮箱：:job_application_email',
        'admin_job_application_phone' => '电话：:job_application_phone',
        'admin_job_application_summary' => '摘要：:job_application_summary',
        'admin_job_application_resume' => '简历：:job_application_resume',
        'admin_job_application_cover_letter' => '求职信：:job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => '您好，我们从 :site_title 收到了新的职位申请！',
        'employer_job_application_name' => '姓名：:job_application_name',
        'employer_job_application_position' => '职位：:job_application_position',
        'employer_job_application_email' => '邮箱：:job_application_email',
        'employer_job_application_phone' => '电话：:job_application_phone',
        'employer_job_application_summary' => '摘要：:job_application_summary',
        'employer_job_application_resume' => '简历：:job_application_resume',
        'employer_job_application_cover_letter' => '求职信：:job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => '公司已批准',
        'company_approved_greeting' => '您好，',
        'company_approved_message' => '我们很高兴地通知您，您的公司已被批准，现已在我们的平台上线。',
        'company_approved_info' => '公司信息',
        'company_approved_name' => '名称：<strong>:company_name</strong>',
        'company_approved_view' => '查看',
        'company_approved_here' => '这里',

        // Confirm email template
        'confirm_email_greeting' => '您好！',
        'confirm_email_message' => '请验证您的邮箱地址以访问此网站。点击下面的按钮验证您的邮箱。',
        'confirm_email_button' => '立即验证',
        'confirm_email_regards' => '此致，',
        'confirm_email_trouble' => '如果您无法点击"立即验证"按钮，请将以下链接复制并粘贴到您的网络浏览器中：:verify_link',

        // Job approved email template
        'job_approved_title' => '职位已批准',
        'job_approved_greeting' => '您好，:job_author，',
        'job_approved_message' => '我们很高兴地通知您，您的职位发布已被批准，现已在我们的平台上线。',
        'job_approved_info' => '职位信息',
        'job_approved_job_title' => '职位名称：<strong>:job_name</strong>',
        'job_approved_view' => '查看',
        'job_approved_here' => '这里',

        // Job expired soon email template
        'job_expired_soon_greeting' => '您好，:job_author！',
        'job_expired_soon_message' => '您的职位 <a href=":job_url">:job_name</a> 将在 :job_expired_after 天后过期。',
        'job_expired_soon_renew' => '请<a href=":job_list">点击这里</a>续订您的职位。',

        // Job renewed email template
        'job_renewed_greeting' => '您好，:job_author！',
        'job_renewed_message' => '您的职位 <a href=":job_url">:job_name</a> 已自动续订。',

        // New job posted email template
        'new_job_posted_title' => '新职位发布',
        'new_job_posted_admin_greeting' => '您好，管理员，',
        'new_job_posted_message' => '我们很高兴地通知您，雇主在我们的平台上发布了一个新职位。',
        'new_job_posted_info' => '职位发布',
        'new_job_posted_employer' => '雇主：<strong>:job_author</strong>',
        'new_job_posted_job_title' => '职位名称：<strong>:job_name</strong>',
        'new_job_posted_admin_link' => '管理面板链接',
        'new_job_posted_here' => '这里',

        // New company profile created email template
        'new_company_profile_title' => '新公司资料创建',
        'new_company_profile_admin_greeting' => '您好，管理员！',
        'new_company_profile_message' => ':employer_name 创建了新的公司资料 ":company_name"',
        'new_company_profile_info' => '公司信息',
        'new_company_profile_employer' => '雇主：<strong>:employer_name</strong>',
        'new_company_profile_name' => '公司名称：<strong>:company_name</strong>',
        'new_company_profile_admin_link' => '管理面板链接',
        'new_company_profile_here' => '这里',

        // Payment receipt email template
        'payment_receipt_greeting' => '您好，:account_name！',
        'payment_receipt_message' => '您购买的支付收据：',
        'payment_receipt_package' => '套餐：:package_name',
        'payment_receipt_price' => '价格：:package_price_per_credit/积分',
        'payment_receipt_total' => '总计：:package_price，获得 :package_number_of_listings 积分',
        'payment_receipt_save' => '（节省 :package_percent_discount%）',
        'payment_receipt_thanks' => '感谢您的付款！',
        'payment_receipt_info' => '付款信息',
        'payment_receipt_amount' => '金额：:package_price',
        'payment_receipt_invoice' => '发票代码：:invoice_code',
        'payment_receipt_view_invoice' => '查看发票',

        // Payment received email template
        'payment_received_admin_greeting' => '您好，管理员！',
        'payment_received_message' => '收到来自 :account_name 的付款：',
        'payment_received_account' => '账户：:account_name（:account_email）',
        'payment_received_package' => '套餐：:package_name',
        'payment_received_price' => '价格：:package_price_per_credit/积分',
        'payment_received_total' => '总计：:package_price，获得 :package_number_of_listings 积分',
        'payment_received_save' => '（节省 :package_percent_discount%）',
        'payment_received_info' => '付款信息',
        'payment_received_customer' => '客户：:account_name',
        'payment_received_amount' => '金额：:package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => '您好，:account_name，',
        'invoice_payment_from' => '您收到了来自 :site_title 的邮件',
        'invoice_payment_attached' => '发票 #:invoice_code 附在此邮件中。',
        'invoice_payment_view_online' => '在线查看',
        'invoice_payment_thanks' => '感谢您的付款！',
        'invoice_payment_info' => '发票信息',
        'invoice_payment_code' => '发票代码：:invoice_code',
        'invoice_payment_view' => '查看发票',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => '您好，管理员，',
        'free_credit_claimed_message' => ':account_name 在 :site_title 上领取了免费积分',
        'free_credit_claimed_info' => '账户信息',
        'free_credit_claimed_name' => '姓名：:account_name',
        'free_credit_claimed_email' => '邮箱：:account_email',

        // Password reminder email template
        'password_reminder_greeting' => '您好！',
        'password_reminder_message' => '您收到此邮件是因为我们收到了您账户的密码重置请求。',
        'password_reminder_button' => '重置密码',
        'password_reminder_no_action' => '如果您没有请求重置密码，无需进一步操作。',
        'password_reminder_regards' => '此致，',
        'password_reminder_trouble' => '如果您无法点击"重置密码"按钮，请将以下链接复制并粘贴到您的网络浏览器中：:reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => '您好，:account_name！',
        'job_alert_hiring' => ':company_name 招聘 :job_name',
        'job_alert_apply_forward' => '👇 申请或转发给朋友：:job_url',
        'job_alert_message' => '符合您偏好的新工作机会已发布！',
        'job_alert_job_info' => '职位：:job_name',
        'job_alert_company_info' => '公司：:company_name',
        'job_alert_view_job' => '查看职位',

        // Job seeker applied job email template
        'job_application_confirmation_title' => '职位申请确认',
        'job_application_confirmation_greeting' => '尊敬的 :job_application_name，',
        'job_application_confirmation_thanks' => '感谢您对 :company_name 的 :job_name 职位感兴趣。我们很高兴确认您的申请已通过我们的系统成功提交。',
        'job_application_confirmation_reviewing' => '我们的招聘团队正在审核您的资格，如果您的技能和经验符合此职位的要求，我们将与您联系。请注意，由于申请量大，此过程可能需要一些时间。',
        'job_application_confirmation_thanks_again' => '再次感谢您的申请！',
        'job_application_confirmation_regards' => '此致敬礼，',
        'job_application_confirmation_team' => ':company_name 团队',

        // New job application (simplified) template
        'new_job_application_greeting' => '您好，',
        'new_job_application_received' => '您收到了新的职位申请。',
        'new_job_application_details' => '申请详情：',
        'new_job_application_name' => '姓名：:job_application_name',
        'new_job_application_position' => '职位：:job_application_position',
        'new_job_application_email' => '邮箱：:job_application_email',
        'new_job_application_phone' => '电话：:job_application_phone',
    ],
];
