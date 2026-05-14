<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'בקשת עבודה חדשה (למשתמשי מערכת)',
            'description' => 'תבנית דוא"ל לשליחת הודעה למנהלי מערכת כאשר המערכת מקבלת בקשת עבודה חדשה',
            'subject' => 'בקשת עבודה חדשה',
        ],
        'employer-new-job-application' => [
            'title' => 'בקשת עבודה חדשה (למעסיק ועמיתים)',
            'description' => 'תבנית דוא"ל לשליחת הודעה למעסיק ועמיתים כאשר המערכת מקבלת בקשת עבודה חדשה',
            'subject' => 'בקשת עבודה חדשה',
        ],
        'new-job-posted' => [
            'title' => 'משרה חדשה פורסמה',
            'description' => 'שלח דוא"ל למנהל מערכת כאשר משרה חדשה פורסמה',
            'subject' => 'משרה חדשה פורסמה ב-{{ site_title }} על ידי {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'פרופיל חברה חדש נוצר',
            'description' => 'שלח דוא"ל למנהל מערכת כאשר מעסיק יוצר פרופיל חברה חדש',
            'subject' => 'פרופיל חברה חדש נוצר ב-{{ site_title }} על ידי {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'משרה תפוג בקרוב',
            'description' => 'שלח דוא"ל למחבר אם המשרה שלו תפוג ב-3 הימים הבאים',
            'subject' => 'המשרה שלך "{{ job_name }}" תפוג בעוד {{ job_expired_after }} ימים',
        ],
        'job-renewed' => [
            'title' => 'משרה חודשה',
            'description' => 'שלח דוא"ל למחבר כאשר המשרה שלו חודשה',
            'subject' => 'המשרה שלך "{{ job_name }}" חודשה אוטומטית',
        ],
        'payment-receipt' => [
            'title' => 'קבלת תשלום',
            'description' => 'שלח הודעה למשתמש כאשר הוא קונה נקודות זכות',
            'subject' => 'קבלת תשלום עבור חבילה {{ package_name }} ב-{{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'חשבון נרשם',
            'description' => 'שלח הודעה למנהל מערכת כאשר מעסיק/מחפש עבודה חדש נרשם',
            'subject' => '{{ account_type }} חדש נרשם ב-{{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'אמת דוא"ל',
            'description' => 'שלח דוא"ל למשתמש כאשר הוא נרשם לחשבון כדי לאמת את הדוא"ל שלו',
            'subject' => 'הודעת אימות דוא"ל',
        ],
        'password-reminder' => [
            'title' => 'איפוס סיסמה',
            'description' => 'שלח דוא"ל למשתמש כאשר מבקש איפוס סיסמה',
            'subject' => 'איפוס סיסמה',
        ],
        'free-credit-claimed' => [
            'title' => 'נקודת זכות חינמית נתבעה',
            'description' => 'שלח הודעה למנהל מערכת כאשר נקודת זכות חינמית נתבעה',
            'subject' => '{{ account_name }} תבע נקודת זכות חינמית ב-{{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'תשלום התקבל',
            'description' => 'שלח הודעה למנהל מערכת כאשר מישהו קונה נקודות זכות',
            'subject' => 'תשלום התקבל מ-{{ account_name }} ב-{{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'פרטי תשלום חשבונית',
            'description' => 'שלח הודעה ללקוח שמבצע את תשלום פרסום המשרה',
            'subject' => 'תשלום התקבל מ-{{ account_name }} ב-{{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'משרה חדשה פורסמה',
            'description' => 'שלח דוא"ל למחפש עבודה כאשר משרה חדשה פורסמה',
            'subject' => 'מגייסים {{ job_name }} ב-{{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'משרה אושרה',
            'description' => 'שלח דוא"ל למחבר כאשר המשרה שלו אושרה',
            'subject' => 'המשרה שלך "{{ job_name }}" אושרה',
        ],
        'company-approved' => [
            'title' => 'חברה אושרה',
            'description' => 'שלח דוא"ל למחבר כאשר החברה שלו אושרה',
            'subject' => 'החברה שלך "{{ company_name }}" אושרה',
        ],
        'job-seeker-applied-job' => [
            'title' => 'אישור בקשת עבודה',
            'description' => 'שלח דוא"ל למחפש עבודה כאשר הוא הגיש בקשה למשרה',
            'subject' => 'אישור בקשה עבור {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'שם',
        'position' => 'תפקיד',
        'email' => 'דוא"ל',
        'phone' => 'טלפון',
        'summary' => 'סיכום',
        'resume' => 'קורות חיים',
        'cover_letter' => 'מכתב נלווה',
        'job_application' => 'בקשת עבודה',
        'job_name' => 'שם משרה',
        'job_url' => 'כתובת URL של משרה',
        'job_author' => 'מחבר משרה',
        'company_name' => 'שם חברה',
        'company_url' => 'כתובת URL של חברה',
        'employer_name' => 'שם מעסיק',
        'job_list' => 'כתובת URL של רשימת משרות',
        'job_expired_after' => 'המשרה תפוג אחרי x ימים',
        'account_name' => 'שם חשבון',
        'account_email' => 'דוא"ל חשבון',
        'package_name' => 'שם חבילה',
        'package_price' => 'מחיר',
        'package_percent_discount' => 'אחוז הנחה',
        'package_number_of_listings' => 'מספר רישומים',
        'package_price_per_credit' => 'מחיר לנקודת זכות',
        'account_type' => 'סוג חשבון (מעסיק/מחפש עבודה)',
        'verify_link' => 'קישור אימות',
        'reset_link' => 'קישור איפוס',
        'invoice_code' => 'קוד חשבונית',
        'invoice_link' => 'קישור לחשבונית',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'שלום מנהל!',
        'account_registered_new_account' => ':account_type חדש נרשם:',
        'account_registered_name' => 'שם: <strong>:account_name</strong>',
        'account_registered_email' => 'דוא"ל: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'שלום, קיבלנו בקשת עבודה חדשה מ-:site_title!',
        'admin_job_application_name' => 'שם: :job_application_name',
        'admin_job_application_position' => 'תפקיד: :job_application_position',
        'admin_job_application_email' => 'דוא"ל: :job_application_email',
        'admin_job_application_phone' => 'טלפון: :job_application_phone',
        'admin_job_application_summary' => 'סיכום: :job_application_summary',
        'admin_job_application_resume' => 'קורות חיים: :job_application_resume',
        'admin_job_application_cover_letter' => 'מכתב נלווה: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'שלום, קיבלנו בקשת עבודה חדשה מ-:site_title!',
        'employer_job_application_name' => 'שם: :job_application_name',
        'employer_job_application_position' => 'תפקיד: :job_application_position',
        'employer_job_application_email' => 'דוא"ל: :job_application_email',
        'employer_job_application_phone' => 'טלפון: :job_application_phone',
        'employer_job_application_summary' => 'סיכום: :job_application_summary',
        'employer_job_application_resume' => 'קורות חיים: :job_application_resume',
        'employer_job_application_cover_letter' => 'מכתב נלווה: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'חברה אושרה',
        'company_approved_greeting' => 'שלום,',
        'company_approved_message' => 'אנו שמחים להודיע לך שהחברה שלך אושרה וכעת היא פעילה בפלטפורמה שלנו.',
        'company_approved_info' => 'מידע על החברה',
        'company_approved_name' => 'שם: <strong>:company_name</strong>',
        'company_approved_view' => 'צפה',
        'company_approved_here' => 'כאן',

        // Confirm email template
        'confirm_email_greeting' => 'שלום!',
        'confirm_email_message' => 'אנא אמת את כתובת הדוא"ל שלך כדי לגשת לאתר זה. לחץ על הכפתור למטה כדי לאמת את הדוא"ל שלך.',
        'confirm_email_button' => 'אמת עכשיו',
        'confirm_email_regards' => 'בברכה,',
        'confirm_email_trouble' => 'אם אתה מתקשה ללחוץ על הכפתור "אמת עכשיו", העתק והדבק את כתובת ה-URL למטה בדפדפן האינטרנט שלך: :verify_link',

        // Job approved email template
        'job_approved_title' => 'משרה אושרה',
        'job_approved_greeting' => 'שלום :job_author,',
        'job_approved_message' => 'אנו שמחים להודיע לך שרישום המשרה שלך אושר וכעת הוא פעיל בפלטפורמה שלנו.',
        'job_approved_info' => 'מידע על המשרה',
        'job_approved_job_title' => 'כותרת משרה: <strong>:job_name</strong>',
        'job_approved_view' => 'צפה',
        'job_approved_here' => 'כאן',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'שלום :job_author!',
        'job_expired_soon_message' => 'המשרה שלך <a href=":job_url">:job_name</a> תפוג בעוד :job_expired_after ימים.',
        'job_expired_soon_renew' => 'אנא <a href=":job_list">לחץ כאן</a> כדי לחדש את המשרה שלך.',

        // Job renewed email template
        'job_renewed_greeting' => 'שלום :job_author!',
        'job_renewed_message' => 'המשרה שלך <a href=":job_url">:job_name</a> חודשה אוטומטית.',

        // New job posted email template
        'new_job_posted_title' => 'משרה חדשה פורסמה',
        'new_job_posted_admin_greeting' => 'שלום מנהל,',
        'new_job_posted_message' => 'אנו שמחים להודיע לך שרישום משרה חדש פורסם על ידי מעסיק בפלטפורמה שלנו.',
        'new_job_posted_info' => 'פרסום משרה',
        'new_job_posted_employer' => 'מעסיק: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'כותרת משרה: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'קישור לפאנל ניהול',
        'new_job_posted_here' => 'כאן',

        // New company profile created email template
        'new_company_profile_title' => 'פרופיל חברה חדש נוצר',
        'new_company_profile_admin_greeting' => 'שלום מנהל!',
        'new_company_profile_message' => 'פרופיל חברה חדש נוצר על ידי :employer_name ":company_name"',
        'new_company_profile_info' => 'מידע על החברה',
        'new_company_profile_employer' => 'מעסיק: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'שם החברה: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'קישור לפאנל ניהול',
        'new_company_profile_here' => 'כאן',

        // Payment receipt email template
        'payment_receipt_greeting' => 'שלום :account_name!',
        'payment_receipt_message' => 'קבלת תשלום עבור הרכישה שלך:',
        'payment_receipt_package' => 'חבילה: :package_name',
        'payment_receipt_price' => 'מחיר: :package_price_per_credit/נקודת זכות',
        'payment_receipt_total' => 'סה"כ: :package_price עבור :package_number_of_listings נקודות זכות',
        'payment_receipt_save' => '(חסוך :package_percent_discount%)',
        'payment_receipt_thanks' => 'תודה על התשלום שלך!',
        'payment_receipt_info' => 'מידע על התשלום',
        'payment_receipt_amount' => 'סכום: :package_price',
        'payment_receipt_invoice' => 'קוד חשבונית: :invoice_code',
        'payment_receipt_view_invoice' => 'צפה בחשבונית',

        // Payment received email template
        'payment_received_admin_greeting' => 'שלום מנהל!',
        'payment_received_message' => 'תשלום התקבל מ-:account_name:',
        'payment_received_account' => 'חשבון: :account_name (:account_email)',
        'payment_received_package' => 'חבילה: :package_name',
        'payment_received_price' => 'מחיר: :package_price_per_credit/נקודת זכות',
        'payment_received_total' => 'סה"כ: :package_price עבור :package_number_of_listings נקודות זכות',
        'payment_received_save' => '(חסוך :package_percent_discount%)',
        'payment_received_info' => 'מידע על התשלום',
        'payment_received_customer' => 'לקוח: :account_name',
        'payment_received_amount' => 'סכום: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'שלום :account_name,',
        'invoice_payment_from' => 'אתה מקבל דוא"ל מ-:site_title',
        'invoice_payment_attached' => 'החשבונית #:invoice_code מצורפת לדוא"ל זה.',
        'invoice_payment_view_online' => 'צפה באינטרנט',
        'invoice_payment_thanks' => 'תודה על התשלום שלך!',
        'invoice_payment_info' => 'מידע על החשבונית',
        'invoice_payment_code' => 'קוד חשבונית: :invoice_code',
        'invoice_payment_view' => 'צפה בחשבונית',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'שלום מנהל,',
        'free_credit_claimed_message' => ':account_name תבע נקודת זכות חינמית ב-:site_title',
        'free_credit_claimed_info' => 'מידע על החשבון',
        'free_credit_claimed_name' => 'שם: :account_name',
        'free_credit_claimed_email' => 'דוא"ל: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'שלום!',
        'password_reminder_message' => 'אתה מקבל דוא"ל זה מכיוון שקיבלנו בקשה לאיפוס סיסמה עבור החשבון שלך.',
        'password_reminder_button' => 'אפס סיסמה',
        'password_reminder_no_action' => 'אם לא ביקשת איפוס סיסמה, אין צורך בפעולה נוספת.',
        'password_reminder_regards' => 'בברכה,',
        'password_reminder_trouble' => 'אם אתה מתקשה ללחוץ על הכפתור "אפס סיסמה", העתק והדבק את כתובת ה-URL למטה בדפדפן האינטרנט שלך: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'שלום :account_name!',
        'job_alert_hiring' => 'מגייסים :job_name ב-:company_name',
        'job_alert_apply_forward' => '👇 הגש מועמדות או העבר לחבר: :job_url',
        'job_alert_message' => 'הזדמנויות עבודה חדשות התואמות את ההעדפות שלך פורסמו!',
        'job_alert_job_info' => 'משרה: :job_name',
        'job_alert_company_info' => 'חברה: :company_name',
        'job_alert_view_job' => 'צפה במשרה',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'אישור בקשת עבודה',
        'job_application_confirmation_greeting' => ':job_application_name היקר/ה,',
        'job_application_confirmation_thanks' => 'תודה על התעניינותך בתפקיד :job_name ב-:company_name. אנו שמחים לאשר שהבקשה שלך הוגשה בהצלחה דרך המערכת שלנו.',
        'job_application_confirmation_reviewing' => 'צוות הגיוס שלנו בודק את כישוריך, ונצור איתך קשר אם המיומנויות והניסיון שלך תואמים את הדרישות לתפקיד זה. שים לב שבשל נפח הבקשות הגבוה, תהליך זה עשוי לקחת זמן.',
        'job_application_confirmation_thanks_again' => 'תודה שוב על הגשת המועמדות!',
        'job_application_confirmation_regards' => 'בברכה,',
        'job_application_confirmation_team' => 'צוות :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'שלום,',
        'new_job_application_received' => 'קיבלת בקשת עבודה חדשה.',
        'new_job_application_details' => 'פרטי הבקשה:',
        'new_job_application_name' => 'שם: :job_application_name',
        'new_job_application_position' => 'תפקיד: :job_application_position',
        'new_job_application_email' => 'דוא"ל: :job_application_email',
        'new_job_application_phone' => 'טלפון: :job_application_phone',
    ],
];
