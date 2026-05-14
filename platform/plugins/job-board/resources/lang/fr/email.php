<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nouvelle candidature (aux administrateurs)',
            'description' => 'Modèle d\'email pour envoyer une notification aux administrateurs quand le système reçoit une nouvelle candidature',
            'subject' => 'Nouvelle candidature',
        ],
        'employer-new-job-application' => [
            'title' => 'Nouvelle candidature (à l\'employeur et aux collègues)',
            'description' => 'Modèle d\'email pour envoyer une notification à l\'employeur et aux collègues quand le système reçoit une nouvelle candidature',
            'subject' => 'Nouvelle candidature',
        ],
        'new-job-posted' => [
            'title' => 'Nouvel emploi publié',
            'description' => 'Envoyer un email à l\'administrateur quand un nouvel emploi est publié',
            'subject' => 'Nouvel emploi publié sur {{ site_title }} par {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Nouveau profil d\'entreprise créé',
            'description' => 'Envoyer un email à l\'administrateur quand un employeur crée un nouveau profil d\'entreprise',
            'subject' => 'Nouveau profil d\'entreprise créé sur {{ site_title }} par {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Emploi expire bientôt',
            'description' => 'Envoyer un email à l\'auteur si son emploi va expirer dans les 3 prochains jours',
            'subject' => 'Votre emploi "{{ job_name }}" va expirer dans {{ job_expired_after }} jours',
        ],
        'job-renewed' => [
            'title' => 'Emploi renouvelé',
            'description' => 'Envoyer un email à l\'auteur quand son emploi est renouvelé',
            'subject' => 'Votre emploi "{{ job_name }}" a été renouvelé automatiquement',
        ],
        'payment-receipt' => [
            'title' => 'Reçu de paiement',
            'description' => 'Envoyer une notification à l\'utilisateur quand il achète des crédits',
            'subject' => 'Reçu de paiement pour le package {{ package_name }} sur {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Compte enregistré',
            'description' => 'Envoyer une notification à l\'administrateur quand un nouvel employeur/demandeur d\'emploi s\'inscrit',
            'subject' => 'Nouveau {{ account_type }} enregistré sur {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Confirmer l\'email',
            'description' => 'Envoyer un email à l\'utilisateur quand il s\'inscrit pour vérifier son email',
            'subject' => 'Notification de confirmation d\'email',
        ],
        'password-reminder' => [
            'title' => 'Réinitialiser le mot de passe',
            'description' => 'Envoyer un email à l\'utilisateur quand il demande la réinitialisation du mot de passe',
            'subject' => 'Réinitialiser le mot de passe',
        ],
        'free-credit-claimed' => [
            'title' => 'Crédit gratuit réclamé',
            'description' => 'Envoyer une notification à l\'administrateur quand un crédit gratuit est réclamé',
            'subject' => '{{ account_name }} a réclamé un crédit gratuit sur {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Paiement reçu',
            'description' => 'Envoyer une notification à l\'administrateur quand quelqu\'un achète des crédits',
            'subject' => 'Paiement reçu de {{ account_name }} sur {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Détail du paiement de facture',
            'description' => 'Envoyer une notification au client qui effectue le paiement de publication d\'emploi',
            'subject' => 'Paiement reçu de {{ account_name }} sur {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Nouvel emploi publié',
            'description' => 'Envoyer un email au demandeur d\'emploi quand un nouvel emploi est publié',
            'subject' => 'Recrutement {{ job_name }} chez {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Emploi approuvé',
            'description' => 'Envoyer un email à l\'auteur quand son emploi est approuvé',
            'subject' => 'Votre emploi "{{ job_name }}" a été approuvé',
        ],
        'company-approved' => [
            'title' => 'Entreprise approuvée',
            'description' => 'Envoyer un email à l\'auteur quand son entreprise est approuvée',
            'subject' => 'Votre entreprise "{{ company_name }}" a été approuvée',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Confirmation de candidature',
            'description' => 'Envoyer un email au demandeur d\'emploi quand il postule pour un emploi',
            'subject' => 'Confirmation de candidature pour {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Nom',
        'position' => 'Poste',
        'email' => 'Email',
        'phone' => 'Téléphone',
        'summary' => 'Résumé',
        'resume' => 'CV',
        'cover_letter' => 'Lettre de motivation',
        'job_application' => 'Candidature',
        'job_name' => 'Nom de l\'emploi',
        'job_url' => 'URL de l\'emploi',
        'job_author' => 'Auteur de l\'emploi',
        'company_name' => 'Nom de l\'entreprise',
        'company_url' => 'URL de l\'entreprise',
        'employer_name' => 'Nom de l\'employeur',
        'job_list' => 'URL de la liste d\'emplois',
        'job_expired_after' => 'Emploi expire après x jours',
        'account_name' => 'Nom du compte',
        'account_email' => 'Email du compte',
        'package_name' => 'Nom du package',
        'package_price' => 'Prix',
        'package_percent_discount' => 'Pourcentage de remise',
        'package_number_of_listings' => 'Nombre d\'annonces',
        'package_price_per_credit' => 'Prix par crédit',
        'account_type' => 'Type de compte (employeur/demandeur d\'emploi)',
        'verify_link' => 'Lien de vérification',
        'reset_link' => 'Lien de réinitialisation',
        'invoice_code' => 'Code de facture',
        'invoice_link' => 'Lien de la facture',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Salut Admin !',
        'account_registered_new_account' => 'Un nouveau :account_type s\'est inscrit :',
        'account_registered_name' => 'Nom : <strong>:account_name</strong>',
        'account_registered_email' => 'Email : <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Bonjour, Nous avons reçu une nouvelle candidature depuis :site_title !',
        'admin_job_application_name' => 'Nom : :job_application_name',
        'admin_job_application_position' => 'Poste : :job_application_position',
        'admin_job_application_email' => 'Email : :job_application_email',
        'admin_job_application_phone' => 'Téléphone : :job_application_phone',
        'admin_job_application_summary' => 'Résumé : :job_application_summary',
        'admin_job_application_resume' => 'CV : :job_application_resume',
        'admin_job_application_cover_letter' => 'Lettre de motivation : :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Bonjour, Nous avons reçu une nouvelle candidature depuis :site_title !',
        'employer_job_application_name' => 'Nom : :job_application_name',
        'employer_job_application_position' => 'Poste : :job_application_position',
        'employer_job_application_email' => 'Email : :job_application_email',
        'employer_job_application_phone' => 'Téléphone : :job_application_phone',
        'employer_job_application_summary' => 'Résumé : :job_application_summary',
        'employer_job_application_resume' => 'CV : :job_application_resume',
        'employer_job_application_cover_letter' => 'Lettre de motivation : :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Entreprise approuvée',
        'company_approved_greeting' => 'Bonjour,',
        'company_approved_message' => 'Nous avons le plaisir de vous informer que votre entreprise a été approuvée et est maintenant en ligne sur notre plateforme.',
        'company_approved_info' => 'Informations sur l\'entreprise',
        'company_approved_name' => 'Nom : <strong>:company_name</strong>',
        'company_approved_view' => 'Voir',
        'company_approved_here' => 'ici',

        // Confirm email template
        'confirm_email_greeting' => 'Bonjour !',
        'confirm_email_message' => 'Veuillez vérifier votre adresse email pour accéder à ce site web. Cliquez sur le bouton ci-dessous pour vérifier votre email.',
        'confirm_email_button' => 'Vérifier maintenant',
        'confirm_email_regards' => 'Cordialement,',
        'confirm_email_trouble' => 'Si vous avez des difficultés à cliquer sur le bouton "Vérifier maintenant", copiez et collez l\'URL ci-dessous dans votre navigateur web : :verify_link',

        // Job approved email template
        'job_approved_title' => 'Emploi approuvé',
        'job_approved_greeting' => 'Bonjour :job_author,',
        'job_approved_message' => 'Nous avons le plaisir de vous informer que votre offre d\'emploi a été approuvée et est maintenant en ligne sur notre plateforme.',
        'job_approved_info' => 'Informations sur l\'emploi',
        'job_approved_job_title' => 'Titre de l\'emploi : <strong>:job_name</strong>',
        'job_approved_view' => 'Voir',
        'job_approved_here' => 'ici',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Bonjour :job_author !',
        'job_expired_soon_message' => 'Votre emploi <a href=":job_url">:job_name</a> va expirer dans :job_expired_after jours.',
        'job_expired_soon_renew' => 'Veuillez <a href=":job_list">cliquer ici</a> pour renouveler votre emploi.',

        // Job renewed email template
        'job_renewed_greeting' => 'Bonjour :job_author !',
        'job_renewed_message' => 'Votre emploi <a href=":job_url">:job_name</a> a été renouvelé automatiquement.',

        // New job posted email template
        'new_job_posted_title' => 'Nouvel emploi publié',
        'new_job_posted_admin_greeting' => 'Bonjour Admin,',
        'new_job_posted_message' => 'Nous avons le plaisir de vous informer qu\'une nouvelle offre d\'emploi a été publiée par un employeur sur notre plateforme.',
        'new_job_posted_info' => 'Publication d\'emploi',
        'new_job_posted_employer' => 'Employeur : <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Titre de l\'emploi : <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Lien du panneau d\'administration',
        'new_job_posted_here' => 'ici',

        // New company profile created email template
        'new_company_profile_title' => 'Nouveau profil d\'entreprise créé',
        'new_company_profile_admin_greeting' => 'Bonjour Admin !',
        'new_company_profile_message' => 'Un nouveau profil d\'entreprise a été créé par :employer_name ":company_name"',
        'new_company_profile_info' => 'Informations sur l\'entreprise',
        'new_company_profile_employer' => 'Employeur : <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Nom de l\'entreprise : <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Lien du panneau d\'administration',
        'new_company_profile_here' => 'ici',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Bonjour :account_name !',
        'payment_receipt_message' => 'Reçu de paiement pour votre achat :',
        'payment_receipt_package' => 'Package : :package_name',
        'payment_receipt_price' => 'Prix : :package_price_per_credit/crédit',
        'payment_receipt_total' => 'Total : :package_price pour :package_number_of_listings crédits',
        'payment_receipt_save' => '(Économisez :package_percent_discount%)',
        'payment_receipt_thanks' => 'Merci pour votre paiement !',
        'payment_receipt_info' => 'Informations de paiement',
        'payment_receipt_amount' => 'Montant : :package_price',
        'payment_receipt_invoice' => 'Code de facture : :invoice_code',
        'payment_receipt_view_invoice' => 'Voir la facture',

        // Payment received email template
        'payment_received_admin_greeting' => 'Bonjour Admin !',
        'payment_received_message' => 'Paiement reçu de :account_name :',
        'payment_received_account' => 'Compte : :account_name (:account_email)',
        'payment_received_package' => 'Package : :package_name',
        'payment_received_price' => 'Prix : :package_price_per_credit/crédit',
        'payment_received_total' => 'Total : :package_price pour :package_number_of_listings crédits',
        'payment_received_save' => '(Économisez :package_percent_discount%)',
        'payment_received_info' => 'Informations de paiement',
        'payment_received_customer' => 'Client : :account_name',
        'payment_received_amount' => 'Montant : :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Bonjour :account_name,',
        'invoice_payment_from' => 'Vous recevez cet email de :site_title',
        'invoice_payment_attached' => 'La facture #:invoice_code est jointe à cet email.',
        'invoice_payment_view_online' => 'Voir en ligne',
        'invoice_payment_thanks' => 'Merci pour votre paiement !',
        'invoice_payment_info' => 'Informations de la facture',
        'invoice_payment_code' => 'Code de facture : :invoice_code',
        'invoice_payment_view' => 'Voir la facture',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Bonjour Admin,',
        'free_credit_claimed_message' => ':account_name a réclamé un crédit gratuit sur :site_title',
        'free_credit_claimed_info' => 'Informations du compte',
        'free_credit_claimed_name' => 'Nom : :account_name',
        'free_credit_claimed_email' => 'Email : :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Bonjour !',
        'password_reminder_message' => 'Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.',
        'password_reminder_button' => 'Réinitialiser le mot de passe',
        'password_reminder_no_action' => 'Si vous n\'avez pas demandé de réinitialisation de mot de passe, aucune action supplémentaire n\'est requise.',
        'password_reminder_regards' => 'Cordialement,',
        'password_reminder_trouble' => 'Si vous avez des difficultés à cliquer sur le bouton "Réinitialiser le mot de passe", copiez et collez l\'URL ci-dessous dans votre navigateur web : :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Bonjour :account_name !',
        'job_alert_hiring' => 'Recrutement :job_name chez :company_name',
        'job_alert_apply_forward' => '👇 Postuler ou transférer à un ami : :job_url',
        'job_alert_message' => 'De nouvelles opportunités d\'emploi correspondant à vos préférences ont été publiées !',
        'job_alert_job_info' => 'Emploi : :job_name',
        'job_alert_company_info' => 'Entreprise : :company_name',
        'job_alert_view_job' => 'Voir l\'emploi',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Confirmation de candidature',
        'job_application_confirmation_greeting' => 'Cher(ère) :job_application_name,',
        'job_application_confirmation_thanks' => 'Merci pour votre intérêt pour le poste :job_name chez :company_name. Nous avons le plaisir de confirmer que votre candidature a été soumise avec succès via notre système.',
        'job_application_confirmation_reviewing' => 'Notre équipe de recrutement examine vos qualifications et nous vous contacterons si vos compétences et votre expérience correspondent aux exigences de ce poste. Veuillez noter qu\'en raison du volume élevé de candidatures, ce processus peut prendre du temps.',
        'job_application_confirmation_thanks_again' => 'Merci encore d\'avoir postulé !',
        'job_application_confirmation_regards' => 'Meilleures salutations,',
        'job_application_confirmation_team' => 'Équipe :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Bonjour,',
        'new_job_application_received' => 'Vous avez reçu une nouvelle candidature.',
        'new_job_application_details' => 'Détails de la candidature :',
        'new_job_application_name' => 'Nom : :job_application_name',
        'new_job_application_position' => 'Poste : :job_application_position',
        'new_job_application_email' => 'Email : :job_application_email',
        'new_job_application_phone' => 'Téléphone : :job_application_phone',
    ],
];
