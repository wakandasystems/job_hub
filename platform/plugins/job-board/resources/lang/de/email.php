<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Neue Bewerbung (an Administratoren)',
            'description' => 'E-Mail-Vorlage zur Benachrichtigung der Administratoren bei neuen Bewerbungen',
            'subject' => 'Neue Bewerbung',
        ],
        'employer-new-job-application' => [
            'title' => 'Neue Bewerbung (an Arbeitgeber und Kollegen)',
            'description' => 'E-Mail-Vorlage zur Benachrichtigung von Arbeitgebern und Kollegen bei neuen Bewerbungen',
            'subject' => 'Neue Bewerbung',
        ],
        'new-job-posted' => [
            'title' => 'Neue Stellenanzeige veröffentlicht',
            'description' => 'E-Mail an Administrator senden, wenn eine neue Stelle veröffentlicht wurde',
            'subject' => 'Neue Stelle auf {{ site_title }} von {{ job_author }} veröffentlicht',
        ],
        'new-company-profile-created' => [
            'title' => 'Neues Unternehmensprofil erstellt',
            'description' => 'E-Mail an Administrator senden, wenn ein Arbeitgeber ein neues Unternehmensprofil erstellt',
            'subject' => 'Neues Unternehmensprofil auf {{ site_title }} von {{ employer_name }} erstellt',
        ],
        'job-expired-soon' => [
            'title' => 'Stelle läuft bald ab',
            'description' => 'E-Mail an den Autor senden, wenn die Stelle in den nächsten 3 Tagen abläuft',
            'subject' => 'Ihre Stelle "{{ job_name }}" läuft in {{ job_expired_after }} Tagen ab',
        ],
        'job-renewed' => [
            'title' => 'Stelle verlängert',
            'description' => 'E-Mail an den Autor senden, wenn die Stelle verlängert wurde',
            'subject' => 'Ihre Stelle "{{ job_name }}" wurde automatisch verlängert',
        ],
        'payment-receipt' => [
            'title' => 'Zahlungsbestätigung',
            'description' => 'Benachrichtigung an Benutzer senden, wenn sie Credits kaufen',
            'subject' => 'Zahlungsbestätigung für Paket {{ package_name }} auf {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Konto registriert',
            'description' => 'Benachrichtigung an Administrator senden, wenn sich ein neuer Arbeitgeber/Bewerber registriert',
            'subject' => 'Neuer {{ account_type }} auf {{ site_title }} registriert',
        ],
        'confirm-email' => [
            'title' => 'E-Mail bestätigen',
            'description' => 'E-Mail an Benutzer senden, wenn sie ein Konto registrieren, um ihre E-Mail zu verifizieren',
            'subject' => 'E-Mail-Bestätigung',
        ],
        'password-reminder' => [
            'title' => 'Passwort zurücksetzen',
            'description' => 'E-Mail an Benutzer senden, wenn sie ein Passwort-Reset anfordern',
            'subject' => 'Passwort zurücksetzen',
        ],
        'free-credit-claimed' => [
            'title' => 'Kostenlose Credits eingelöst',
            'description' => 'Benachrichtigung an Administrator senden, wenn kostenlose Credits eingelöst werden',
            'subject' => '{{ account_name }} hat kostenlose Credits auf {{ site_title }} eingelöst',
        ],
        'payment-received' => [
            'title' => 'Zahlung erhalten',
            'description' => 'Benachrichtigung an Administrator senden, wenn jemand Credits kauft',
            'subject' => 'Zahlung von {{ account_name }} auf {{ site_title }} erhalten',
        ],
        'invoice-payment-created' => [
            'title' => 'Rechnungs-Zahlungsdetails',
            'description' => 'Benachrichtigung an den Kunden senden, der die Stellenanzeigen-Zahlung tätigt',
            'subject' => 'Zahlung von {{ account_name }} auf {{ site_title }} erhalten',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Neue Stelle veröffentlicht',
            'description' => 'E-Mail an Bewerber senden, wenn eine neue Stelle veröffentlicht wurde',
            'subject' => 'Einstellung {{ job_name }} bei {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Stelle genehmigt',
            'description' => 'E-Mail an den Autor senden, wenn seine Stelle genehmigt wurde',
            'subject' => 'Ihre Stelle "{{ job_name }}" wurde genehmigt',
        ],
        'company-approved' => [
            'title' => 'Unternehmen genehmigt',
            'description' => 'E-Mail an den Autor senden, wenn sein Unternehmen genehmigt wurde',
            'subject' => 'Ihr Unternehmen "{{ company_name }}" wurde genehmigt',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Bewerbungsbestätigung',
            'description' => 'E-Mail an Bewerber senden, wenn sie sich auf eine Stelle beworben haben',
            'subject' => 'Bewerbungsbestätigung für {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Name',
        'position' => 'Position',
        'email' => 'E-Mail',
        'phone' => 'Telefon',
        'summary' => 'Zusammenfassung',
        'resume' => 'Lebenslauf',
        'cover_letter' => 'Anschreiben',
        'job_application' => 'Bewerbung',
        'job_name' => 'Stellenbezeichnung',
        'job_url' => 'Stellen-URL',
        'job_author' => 'Stellen-Autor',
        'company_name' => 'Firmenname',
        'company_url' => 'Firmen-URL',
        'employer_name' => 'Arbeitgebername',
        'job_list' => 'Stellenliste URL',
        'job_expired_after' => 'Stelle läuft ab nach x Tagen',
        'account_name' => 'Kontoname',
        'account_email' => 'Konto-E-Mail',
        'package_name' => 'Paketname',
        'package_price' => 'Preis',
        'package_percent_discount' => 'Prozent Rabatt',
        'package_number_of_listings' => 'Anzahl der Anzeigen',
        'package_price_per_credit' => 'Preis pro Credit',
        'account_type' => 'Kontotyp (Arbeitgeber/Bewerber)',
        'verify_link' => 'Verifizierungslink',
        'reset_link' => 'Zurücksetzungslink',
        'invoice_code' => 'Rechnungscode',
        'invoice_link' => 'Rechnungslink',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Hallo Administrator!',
        'account_registered_new_account' => 'Ein neuer :account_type hat sich registriert:',
        'account_registered_name' => 'Name: <strong>:account_name</strong>',
        'account_registered_email' => 'E-Mail: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Hallo, wir haben eine neue Bewerbung von :site_title erhalten!',
        'admin_job_application_name' => 'Name: :job_application_name',
        'admin_job_application_position' => 'Position: :job_application_position',
        'admin_job_application_email' => 'E-Mail: :job_application_email',
        'admin_job_application_phone' => 'Telefon: :job_application_phone',
        'admin_job_application_summary' => 'Zusammenfassung: :job_application_summary',
        'admin_job_application_resume' => 'Lebenslauf: :job_application_resume',
        'admin_job_application_cover_letter' => 'Anschreiben: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Hallo, wir haben eine neue Bewerbung von :site_title erhalten!',
        'employer_job_application_name' => 'Name: :job_application_name',
        'employer_job_application_position' => 'Position: :job_application_position',
        'employer_job_application_email' => 'E-Mail: :job_application_email',
        'employer_job_application_phone' => 'Telefon: :job_application_phone',
        'employer_job_application_summary' => 'Zusammenfassung: :job_application_summary',
        'employer_job_application_resume' => 'Lebenslauf: :job_application_resume',
        'employer_job_application_cover_letter' => 'Anschreiben: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Unternehmen genehmigt',
        'company_approved_greeting' => 'Hallo,',
        'company_approved_message' => 'Wir freuen uns, Ihnen mitteilen zu können, dass Ihr Unternehmen genehmigt wurde und nun auf unserer Plattform live ist.',
        'company_approved_info' => 'Unternehmensinformationen',
        'company_approved_name' => 'Name: <strong>:company_name</strong>',
        'company_approved_view' => 'Ansehen',
        'company_approved_here' => 'hier',

        // Confirm email template
        'confirm_email_greeting' => 'Hallo!',
        'confirm_email_message' => 'Bitte verifizieren Sie Ihre E-Mail-Adresse, um auf diese Website zugreifen zu können. Klicken Sie auf die Schaltfläche unten, um Ihre E-Mail zu verifizieren.',
        'confirm_email_button' => 'Jetzt verifizieren',
        'confirm_email_regards' => 'Mit freundlichen Grüßen,',
        'confirm_email_trouble' => 'Falls Sie Probleme haben, auf die Schaltfläche "Jetzt verifizieren" zu klicken, kopieren Sie die folgende URL und fügen Sie sie in Ihren Webbrowser ein: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Stelle genehmigt',
        'job_approved_greeting' => 'Hallo :job_author,',
        'job_approved_message' => 'Wir freuen uns, Ihnen mitteilen zu können, dass Ihre Stellenanzeige genehmigt wurde und nun auf unserer Plattform live ist.',
        'job_approved_info' => 'Stelleninformationen',
        'job_approved_job_title' => 'Stellenbezeichnung: <strong>:job_name</strong>',
        'job_approved_view' => 'Ansehen',
        'job_approved_here' => 'hier',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Hallo :job_author!',
        'job_expired_soon_message' => 'Ihre Stelle <a href=":job_url">:job_name</a> läuft in :job_expired_after Tagen ab.',
        'job_expired_soon_renew' => 'Bitte <a href=":job_list">gehen Sie hier</a>, um Ihre Stelle zu verlängern.',

        // Job renewed email template
        'job_renewed_greeting' => 'Hallo :job_author!',
        'job_renewed_message' => 'Ihre Stelle <a href=":job_url">:job_name</a> wurde automatisch verlängert.',

        // New job posted email template
        'new_job_posted_title' => 'Neue Stelle veröffentlicht',
        'new_job_posted_admin_greeting' => 'Hallo Administrator,',
        'new_job_posted_message' => 'Wir freuen uns, Ihnen mitteilen zu können, dass eine neue Stellenanzeige von einem Arbeitgeber auf unserer Plattform veröffentlicht wurde.',
        'new_job_posted_info' => 'Stellenausschreibung',
        'new_job_posted_employer' => 'Arbeitgeber: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Stellenbezeichnung: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Admin-Panel-Link',
        'new_job_posted_here' => 'hier',

        // New company profile created email template
        'new_company_profile_title' => 'Neues Unternehmensprofil erstellt',
        'new_company_profile_admin_greeting' => 'Hallo Administrator!',
        'new_company_profile_message' => 'Ein neues Unternehmensprofil wurde von :employer_name ":company_name" erstellt',
        'new_company_profile_info' => 'Unternehmensinformationen',
        'new_company_profile_employer' => 'Arbeitgeber: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Firmenname: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Admin-Panel-Link',
        'new_company_profile_here' => 'hier',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Hallo :account_name!',
        'payment_receipt_message' => 'Zahlungsbestätigung für Ihren Kauf:',
        'payment_receipt_package' => 'Paket: :package_name',
        'payment_receipt_price' => 'Preis: :package_price_per_credit/Credit',
        'payment_receipt_total' => 'Gesamt: :package_price für :package_number_of_listings Credits',
        'payment_receipt_save' => '(Sparen Sie :package_percent_discount%)',
        'payment_receipt_thanks' => 'Vielen Dank für Ihre Zahlung!',
        'payment_receipt_info' => 'Zahlungsinformationen',
        'payment_receipt_amount' => 'Betrag: :package_price',
        'payment_receipt_invoice' => 'Rechnungscode: :invoice_code',
        'payment_receipt_view_invoice' => 'Rechnung ansehen',

        // Payment received email template
        'payment_received_admin_greeting' => 'Hallo Administrator!',
        'payment_received_message' => 'Zahlung von :account_name erhalten:',
        'payment_received_account' => 'Konto: :account_name (:account_email)',
        'payment_received_package' => 'Paket: :package_name',
        'payment_received_price' => 'Preis: :package_price_per_credit/Credit',
        'payment_received_total' => 'Gesamt: :package_price für :package_number_of_listings Credits',
        'payment_received_save' => '(Sparen Sie :package_percent_discount%)',
        'payment_received_info' => 'Zahlungsinformationen',
        'payment_received_customer' => 'Kunde: :account_name',
        'payment_received_amount' => 'Betrag: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hallo :account_name,',
        'invoice_payment_from' => 'Sie erhalten diese E-Mail von :site_title',
        'invoice_payment_attached' => 'Die Rechnung #:invoice_code ist dieser E-Mail beigefügt.',
        'invoice_payment_view_online' => 'Online ansehen',
        'invoice_payment_thanks' => 'Vielen Dank für Ihre Zahlung!',
        'invoice_payment_info' => 'Rechnungsinformationen',
        'invoice_payment_code' => 'Rechnungscode: :invoice_code',
        'invoice_payment_view' => 'Rechnung ansehen',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hallo Administrator,',
        'free_credit_claimed_message' => ':account_name hat kostenlose Credits auf :site_title eingelöst',
        'free_credit_claimed_info' => 'Kontoinformationen',
        'free_credit_claimed_name' => 'Name: :account_name',
        'free_credit_claimed_email' => 'E-Mail: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Hallo!',
        'password_reminder_message' => 'Sie erhalten diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.',
        'password_reminder_button' => 'Passwort zurücksetzen',
        'password_reminder_no_action' => 'Wenn Sie kein Zurücksetzen des Passworts angefordert haben, ist keine weitere Aktion erforderlich.',
        'password_reminder_regards' => 'Mit freundlichen Grüßen,',
        'password_reminder_trouble' => 'Falls Sie Probleme haben, auf die Schaltfläche "Passwort zurücksetzen" zu klicken, kopieren Sie die folgende URL und fügen Sie sie in Ihren Webbrowser ein: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Hallo :account_name!',
        'job_alert_hiring' => 'Einstellung :job_name bei :company_name',
        'job_alert_apply_forward' => '👇 Bewerben oder an einen Freund weiterleiten: :job_url',
        'job_alert_message' => 'Neue Stellenangebote, die Ihren Präferenzen entsprechen, wurden veröffentlicht!',
        'job_alert_job_info' => 'Stelle: :job_name',
        'job_alert_company_info' => 'Unternehmen: :company_name',
        'job_alert_view_job' => 'Stelle ansehen',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Bewerbungsbestätigung',
        'job_application_confirmation_greeting' => 'Sehr geehrte(r) :job_application_name,',
        'job_application_confirmation_thanks' => 'Vielen Dank für Ihr Interesse an der Position :job_name bei :company_name. Wir freuen uns, Ihnen zu bestätigen, dass Ihre Bewerbung erfolgreich über unser System eingereicht wurde.',
        'job_application_confirmation_reviewing' => 'Unser Recruiting-Team prüft Ihre Qualifikationen und wir werden uns bei Ihnen melden, wenn Ihre Fähigkeiten und Erfahrungen den Anforderungen für diese Position entsprechen. Bitte beachten Sie, dass dieser Prozess aufgrund der hohen Anzahl an Bewerbungen einige Zeit in Anspruch nehmen kann.',
        'job_application_confirmation_thanks_again' => 'Nochmals vielen Dank für Ihre Bewerbung!',
        'job_application_confirmation_regards' => 'Mit freundlichen Grüßen,',
        'job_application_confirmation_team' => ':company_name Team',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hallo,',
        'new_job_application_received' => 'Sie haben eine neue Bewerbung erhalten.',
        'new_job_application_details' => 'Bewerbungsdetails:',
        'new_job_application_name' => 'Name: :job_application_name',
        'new_job_application_position' => 'Position: :job_application_position',
        'new_job_application_email' => 'E-Mail: :job_application_email',
        'new_job_application_phone' => 'Telefon: :job_application_phone',
    ],
];
