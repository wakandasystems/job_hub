<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'New job application (to admin users)',
            'description' => 'Email template to send notice to administrators when system get new job application',
            'subject' => 'New job application',
        ],
        'employer-new-job-application' => [
            'title' => 'New job application (to employer and colleagues)',
            'description' => 'Email template to send notice to employer and colleagues when system get new job application',
            'subject' => 'New job application',
        ],
        'new-job-posted' => [
            'title' => 'New job posted',
            'description' => 'Send email to admin when a new job posted',
            'subject' => 'New job is posted on {{ site_title }} by {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'New company profile created',
            'description' => 'Send email to admin when a employer create a new company profile',
            'subject' => 'New company profile is created on {{ site_title }} by {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Job expired soon',
            'description' => 'Send email to the author if their job will be expired in next 3 days',
            'subject' => 'Your job "{{ job_name }}" will be expired in {{ job_expired_after }} days',
        ],
        'job-renewed' => [
            'title' => 'Job renewed',
            'description' => 'Send email to the author when their job renewed',
            'subject' => 'Your job "{{ job_name }}" has been renewed automatically',
        ],
        'payment-receipt' => [
            'title' => 'Payment receipt',
            'description' => 'Send a notification to user when they buy credits',
            'subject' => 'Payment receipt for package {{ package_name }} on {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Account registered',
            'description' => 'Send a notification to admin when a new employer/job seeker registered',
            'subject' => 'New {{ account_type }} registered on {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Confirm email',
            'description' => 'Send email to user when they register an account to verify their email',
            'subject' => 'Confirm Email Notification',
        ],
        'password-reminder' => [
            'title' => 'Reset password',
            'description' => 'Send email to user when requesting reset password',
            'subject' => 'Reset Password',
        ],
        'free-credit-claimed' => [
            'title' => 'Free credit claimed',
            'description' => 'Send a notification to admin when free credit is claimed',
            'subject' => '{{ account_name }} has claimed free credit on {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Payment received',
            'description' => 'Send a notification to admin when someone buy credits',
            'subject' => 'Payment received from {{ account_name }} on {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Invoice Payment Detail',
            'description' => 'Send a notification to the customer who makes the job posting payment',
            'subject' => 'Payment received from {{ account_name }} on {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'New job posted',
            'description' => 'Send email to job seeker when a new job posted',
            'subject' => 'Hiring {{ job_name }} at {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Job approved',
            'description' => 'Send email to the author when their job is approved',
            'subject' => 'Your job "{{ job_name }}" has been approved',
        ],
        'company-approved' => [
            'title' => 'Company approved',
            'description' => 'Send email to the author when their company is approved',
            'subject' => 'Your company "{{ company_name }}" has been approved',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Job application confirmation',
            'description' => 'Send email to job seeker when they applied for a job',
            'subject' => 'Application Confirmation for {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Name',
        'position' => 'Posizione',
        'email' => 'Email',
        'phone' => 'Telefono',
        'summary' => 'Summary',
        'resume' => 'Curriculum',
        'cover_letter' => 'Cover Letter',
        'job_application' => 'Job application',
        'job_name' => 'Job name',
        'job_url' => 'Job URL',
        'job_author' => 'Job author',
        'company_name' => 'Company name',
        'company_url' => 'Company URL',
        'employer_name' => 'Employer name',
        'job_list' => 'Job list URL',
        'job_expired_after' => 'Job expired after x days',
        'account_name' => 'Account name',
        'account_email' => 'Account email',
        'package_name' => 'Name of package',
        'package_price' => 'Price',
        'package_percent_discount' => 'Percent discount',
        'package_number_of_listings' => 'Number of listings',
        'package_price_per_credit' => 'Price per credit',
        'account_type' => 'Account type (employer/job seeker)',
        'verify_link' => 'Verify link',
        'reset_link' => 'Reset link',
        'invoice_code' => 'Invoice Code',
        'invoice_link' => 'Invoice Link',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hi Admin!',
        'account_registered_new_account' => 'A new :account_type registered:',
        'account_registered_name' => 'Name: <strong>:account_name</strong>',
        'account_registered_email' => 'Email: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hello, We received a new job application from :site_title!',
        'admin_job_application_name' => 'Name: :job_application_name',
        'admin_job_application_position' => 'Position: :job_application_position',
        'admin_job_application_email' => 'Email: :job_application_email',
        'admin_job_application_phone' => 'Phone: :job_application_phone',
        'admin_job_application_summary' => 'Summary: :job_application_summary',
        'admin_job_application_resume' => 'Resume: :job_application_resume',
        'admin_job_application_cover_letter' => 'Cover Letter: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hello, We received a new job application from :site_title!',
        'employer_job_application_name' => 'Name: :job_application_name',
        'employer_job_application_position' => 'Position: :job_application_position',
        'employer_job_application_email' => 'Email: :job_application_email',
        'employer_job_application_phone' => 'Phone: :job_application_phone',
        'employer_job_application_summary' => 'Summary: :job_application_summary',
        'employer_job_application_resume' => 'Resume: :job_application_resume',
        'employer_job_application_cover_letter' => 'Cover Letter: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Company Approved',
        'company_approved_greeting' => 'Hi,',
        'company_approved_message' => 'We are pleased to inform you that your company has been approved and is now live on our platform.',
        'company_approved_info' => 'Company Information',
        'company_approved_name' => 'Name: <strong>:company_name</strong>',
        'company_approved_view' => 'View',
        'company_approved_here' => 'here',

        // Confirm email template
        'confirm_email_greeting' => 'Hello!',
        'confirm_email_message' => 'Please verify your email address in order to access this website. Click on the button below to verify your email..',
        'confirm_email_button' => 'Verify now',
        'confirm_email_regards' => 'Regards,',
        'confirm_email_trouble' => 'If you\'re having trouble clicking the "Verify now" button, copy and paste the URL below into your web browser: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Job Approved',
        'job_approved_greeting' => 'Hi :job_author,',
        'job_approved_message' => 'We are pleased to inform you that your job listing has been approved and is now live on our platform.',
        'job_approved_info' => 'Job Information',
        'job_approved_job_title' => 'Job Title: <strong>:job_name</strong>',
        'job_approved_view' => 'View',
        'job_approved_here' => 'here',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hi :job_author!',
        'job_expired_soon_message' => 'Your job <a href=":job_url">:job_name</a> will be expired in :job_expired_after days.',
        'job_expired_soon_renew' => 'Please <a href=":job_list">go here</a> to renew your job.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hi :job_author!',
        'job_renewed_message' => 'Your job <a href=":job_url">:job_name</a> has been renewed automatically.',

        // New job posted email template
        'new_job_posted_title' => 'New Job Posted',
        'new_job_posted_admin_greeting' => 'Hi Admin,',
        'new_job_posted_message' => 'We are pleased to inform you that a new job listing has been posted by an employer on our platform.',
        'new_job_posted_info' => 'Job Post',
        'new_job_posted_employer' => 'Employer: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Job Title: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Admin Panel Link',
        'new_job_posted_here' => 'here',

        // New company profile created email template
        'new_company_profile_title' => 'New Company Profile Created',
        'new_company_profile_admin_greeting' => 'Hi Admin!',
        'new_company_profile_message' => 'A new company profile is created by :employer_name ":company_name"',
        'new_company_profile_info' => 'Company Information',
        'new_company_profile_employer' => 'Employer: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Company Name: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Admin Panel Link',
        'new_company_profile_here' => 'here',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hi :account_name!',
        'payment_receipt_message' => 'Payment receipt for your purchase:',
        'payment_receipt_package' => 'Package: :package_name',
        'payment_receipt_price' => 'Price: :package_price_per_credit/credit',
        'payment_receipt_total' => 'Total: :package_price for :package_number_of_listings credits',
        'payment_receipt_save' => '(Save :package_percent_discount%)',
        'payment_receipt_thanks' => 'Thank you for your payment!',
        'payment_receipt_info' => 'Payment Information',
        'payment_receipt_amount' => 'Amount: :package_price',
        'payment_receipt_invoice' => 'Invoice Code: :invoice_code',
        'payment_receipt_view_invoice' => 'View Invoice',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hi Admin!',
        'payment_received_message' => 'Payment received from :account_name:',
        'payment_received_account' => 'Account: :account_name (:account_email)',
        'payment_received_package' => 'Package: :package_name',
        'payment_received_price' => 'Price: :package_price_per_credit/credit',
        'payment_received_total' => 'Total: :package_price for :package_number_of_listings credits',
        'payment_received_save' => '(Save :package_percent_discount%)',
        'payment_received_info' => 'Payment Information',
        'payment_received_customer' => 'Customer: :account_name',
        'payment_received_amount' => 'Amount: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hi :account_name,',
        'invoice_payment_from' => 'You\'re receiving email from :site_title',
        'invoice_payment_attached' => 'The invoice #:invoice_code is attached with this email.',
        'invoice_payment_view_online' => 'View Online',
        'invoice_payment_thanks' => 'Thank you for your payment!',
        'invoice_payment_info' => 'Invoice Information',
        'invoice_payment_code' => 'Invoice Code: :invoice_code',
        'invoice_payment_view' => 'View Invoice',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hi Admin,',
        'free_credit_claimed_message' => ':account_name has claimed free credit on :site_title',
        'free_credit_claimed_info' => 'Account Information',
        'free_credit_claimed_name' => 'Name: :account_name',
        'free_credit_claimed_email' => 'Email: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hello!',
        'password_reminder_message' => 'You are receiving this email because we received a password reset request for your account.',
        'password_reminder_button' => 'Reset password',
        'password_reminder_no_action' => 'If you did not request a password reset, no further action is required.',
        'password_reminder_regards' => 'Regards,',
        'password_reminder_trouble' => 'If you\'re having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hi :account_name!',
        'job_alert_hiring' => 'Hiring :job_name at :company_name',
        'job_alert_apply_forward' => '👇 Apply or Forward to a friend: :job_url',
        'job_alert_message' => 'New job opportunities matching your preferences have been posted!',
        'job_alert_job_info' => 'Job: :job_name',
        'job_alert_company_info' => 'Company: :company_name',
        'job_alert_view_job' => 'View Job',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Job Application Confirmation',
        'job_application_confirmation_greeting' => 'Dear :job_application_name,',
        'job_application_confirmation_thanks' => 'Thank you for your interest in the :job_name position at :company_name. We are pleased to confirm that your application has been successfully submitted through our system.',
        'job_application_confirmation_reviewing' => 'Our recruitment team is reviewing your qualifications, and we will contact you if your skills and experience match the requirements for this role. Please note that due to the high volume of applications, this process may take some time.',
        'job_application_confirmation_thanks_again' => 'Thank you again for applying!',
        'job_application_confirmation_regards' => 'Best regards,',
        'job_application_confirmation_team' => ':company_name Team',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hello,',
        'new_job_application_received' => 'You have received a new job application.',
        'new_job_application_details' => 'Application Details:',
        'new_job_application_name' => 'Name: :job_application_name',
        'new_job_application_position' => 'Position: :job_application_position',
        'new_job_application_email' => 'Email: :job_application_email',
        'new_job_application_phone' => 'Phone: :job_application_phone',
    ],
];
