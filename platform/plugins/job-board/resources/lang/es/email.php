<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nueva aplicación de empleo (para usuarios administradores)',
            'description' => 'Plantilla de email para enviar notificación a los administradores cuando el sistema recibe una nueva aplicación de empleo',
            'subject' => 'Nueva aplicación de empleo',
        ],
        'employer-new-job-application' => [
            'title' => 'Nueva aplicación de empleo (para empleador y colegas)',
            'description' => 'Plantilla de email para enviar notificación al empleador y colegas cuando el sistema recibe una nueva aplicación de empleo',
            'subject' => 'Nueva aplicación de empleo',
        ],
        'new-job-posted' => [
            'title' => 'Nuevo empleo publicado',
            'description' => 'Enviar email al administrador cuando se publique un nuevo empleo',
            'subject' => 'Nuevo empleo publicado en {{ site_title }} por {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Nuevo perfil de empresa creado',
            'description' => 'Enviar email al administrador cuando un empleador cree un nuevo perfil de empresa',
            'subject' => 'Nuevo perfil de empresa creado en {{ site_title }} por {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Empleo vence pronto',
            'description' => 'Enviar email al autor si su empleo vencerá en los próximos 3 días',
            'subject' => 'Su empleo "{{ job_name }}" vencerá en {{ job_expired_after }} días',
        ],
        'job-renewed' => [
            'title' => 'Empleo renovado',
            'description' => 'Enviar email al autor cuando su empleo sea renovado',
            'subject' => 'Su empleo "{{ job_name }}" ha sido renovado automáticamente',
        ],
        'payment-receipt' => [
            'title' => 'Recibo de pago',
            'description' => 'Enviar una notificación al usuario cuando compre créditos',
            'subject' => 'Recibo de pago para el paquete {{ package_name }} en {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Cuenta registrada',
            'description' => 'Enviar una notificación al administrador cuando un nuevo empleador/buscador de empleo se registre',
            'subject' => 'Nuevo {{ account_type }} registrado en {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Confirmar email',
            'description' => 'Enviar email al usuario cuando se registre para verificar su email',
            'subject' => 'Notificación de confirmación de email',
        ],
        'password-reminder' => [
            'title' => 'Restablecer contraseña',
            'description' => 'Enviar email al usuario cuando solicite restablecer la contraseña',
            'subject' => 'Restablecer contraseña',
        ],
        'free-credit-claimed' => [
            'title' => 'Crédito gratuito reclamado',
            'description' => 'Enviar una notificación al administrador cuando se reclame un crédito gratuito',
            'subject' => '{{ account_name }} ha reclamado crédito gratuito en {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Pago recibido',
            'description' => 'Enviar una notificación al administrador cuando alguien compre créditos',
            'subject' => 'Pago recibido de {{ account_name }} en {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Detalle de pago de factura',
            'description' => 'Enviar una notificación al cliente que realiza el pago de publicación de empleo',
            'subject' => 'Pago recibido de {{ account_name }} en {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Nuevo empleo publicado',
            'description' => 'Enviar email al buscador de empleo cuando se publique un nuevo empleo',
            'subject' => 'Contratando {{ job_name }} en {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Empleo aprobado',
            'description' => 'Enviar email al autor cuando su empleo sea aprobado',
            'subject' => 'Su empleo "{{ job_name }}" ha sido aprobado',
        ],
        'company-approved' => [
            'title' => 'Empresa aprobada',
            'description' => 'Enviar email al autor cuando su empresa sea aprobada',
            'subject' => 'Su empresa "{{ company_name }}" ha sido aprobada',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Confirmación de aplicación de empleo',
            'description' => 'Enviar email al buscador de empleo cuando aplique a un empleo',
            'subject' => 'Confirmación de aplicación para {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Nombre',
        'position' => 'Puesto',
        'email' => 'Email',
        'phone' => 'Teléfono',
        'summary' => 'Resumen',
        'resume' => 'Currículum',
        'cover_letter' => 'Carta de presentación',
        'job_application' => 'Aplicación de empleo',
        'job_name' => 'Nombre del empleo',
        'job_url' => 'URL del empleo',
        'job_author' => 'Autor del empleo',
        'company_name' => 'Nombre de la empresa',
        'company_url' => 'URL de la empresa',
        'employer_name' => 'Nombre del empleador',
        'job_list' => 'URL de lista de empleos',
        'job_expired_after' => 'Empleo vence después de x días',
        'account_name' => 'Nombre de la cuenta',
        'account_email' => 'Email de la cuenta',
        'package_name' => 'Nombre del paquete',
        'package_price' => 'Precio',
        'package_percent_discount' => 'Porcentaje de descuento',
        'package_number_of_listings' => 'Número de listados',
        'package_price_per_credit' => 'Precio por crédito',
        'account_type' => 'Tipo de cuenta (empleador/buscador de empleo)',
        'verify_link' => 'Enlace de verificación',
        'reset_link' => 'Enlace de restablecimiento',
        'invoice_code' => 'Código de factura',
        'invoice_link' => 'Enlace de factura',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => '¡Hola Administrador!',
        'account_registered_new_account' => 'Un nuevo :account_type se registró:',
        'account_registered_name' => 'Nombre: <strong>:account_name</strong>',
        'account_registered_email' => 'Email: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => '¡Hola! ¡Recibimos una nueva aplicación de empleo desde :site_title!',
        'admin_job_application_name' => 'Nombre: :job_application_name',
        'admin_job_application_position' => 'Puesto: :job_application_position',
        'admin_job_application_email' => 'Email: :job_application_email',
        'admin_job_application_phone' => 'Teléfono: :job_application_phone',
        'admin_job_application_summary' => 'Resumen: :job_application_summary',
        'admin_job_application_resume' => 'Currículum: :job_application_resume',
        'admin_job_application_cover_letter' => 'Carta de presentación: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => '¡Hola! ¡Recibimos una nueva aplicación de empleo desde :site_title!',
        'employer_job_application_name' => 'Nombre: :job_application_name',
        'employer_job_application_position' => 'Puesto: :job_application_position',
        'employer_job_application_email' => 'Email: :job_application_email',
        'employer_job_application_phone' => 'Teléfono: :job_application_phone',
        'employer_job_application_summary' => 'Resumen: :job_application_summary',
        'employer_job_application_resume' => 'Currículum: :job_application_resume',
        'employer_job_application_cover_letter' => 'Carta de presentación: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Empresa aprobada',
        'company_approved_greeting' => 'Hola,',
        'company_approved_message' => 'Nos complace informarle que su empresa ha sido aprobada y ya está activa en nuestra plataforma.',
        'company_approved_info' => 'Información de la empresa',
        'company_approved_name' => 'Nombre: <strong>:company_name</strong>',
        'company_approved_view' => 'Ver',
        'company_approved_here' => 'aquí',

        // Confirm email template
        'confirm_email_greeting' => '¡Hola!',
        'confirm_email_message' => 'Por favor verifique su dirección de email para acceder a este sitio web. Haga clic en el botón de abajo para verificar su email.',
        'confirm_email_button' => 'Verificar ahora',
        'confirm_email_regards' => 'Saludos,',
        'confirm_email_trouble' => 'Si tiene problemas para hacer clic en el botón "Verificar ahora", copie y pegue la siguiente URL en su navegador web: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Empleo aprobado',
        'job_approved_greeting' => 'Hola :job_author,',
        'job_approved_message' => 'Nos complace informarle que su publicación de empleo ha sido aprobada y ya está activa en nuestra plataforma.',
        'job_approved_info' => 'Información del empleo',
        'job_approved_job_title' => 'Título del empleo: <strong>:job_name</strong>',
        'job_approved_view' => 'Ver',
        'job_approved_here' => 'aquí',

        // Job expired soon email template
        'job_expired_soon_greeting' => '¡Hola :job_author!',
        'job_expired_soon_message' => 'Su empleo <a href=":job_url">:job_name</a> vencerá en :job_expired_after días.',
        'job_expired_soon_renew' => 'Por favor <a href=":job_list">vaya aquí</a> para renovar su empleo.',

        // Job renewed email template
        'job_renewed_greeting' => '¡Hola :job_author!',
        'job_renewed_message' => 'Su empleo <a href=":job_url">:job_name</a> ha sido renovado automáticamente.',

        // New job posted email template
        'new_job_posted_title' => 'Nuevo empleo publicado',
        'new_job_posted_admin_greeting' => 'Hola Administrador,',
        'new_job_posted_message' => 'Nos complace informarle que un nuevo listado de empleo ha sido publicado por un empleador en nuestra plataforma.',
        'new_job_posted_info' => 'Publicación de empleo',
        'new_job_posted_employer' => 'Empleador: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Título del empleo: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Enlace del panel de administración',
        'new_job_posted_here' => 'aquí',

        // New company profile created email template
        'new_company_profile_title' => 'Nuevo perfil de empresa creado',
        'new_company_profile_admin_greeting' => '¡Hola Administrador!',
        'new_company_profile_message' => 'Un nuevo perfil de empresa fue creado por :employer_name ":company_name"',
        'new_company_profile_info' => 'Información de la empresa',
        'new_company_profile_employer' => 'Empleador: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Nombre de la empresa: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Enlace del panel de administración',
        'new_company_profile_here' => 'aquí',

        // Payment receipt email template
        'payment_receipt_greeting' => '¡Hola :account_name!',
        'payment_receipt_message' => 'Recibo de pago para su compra:',
        'payment_receipt_package' => 'Paquete: :package_name',
        'payment_receipt_price' => 'Precio: :package_price_per_credit/crédito',
        'payment_receipt_total' => 'Total: :package_price por :package_number_of_listings créditos',
        'payment_receipt_save' => '(Ahorra :package_percent_discount%)',
        'payment_receipt_thanks' => '¡Gracias por su pago!',
        'payment_receipt_info' => 'Información del pago',
        'payment_receipt_amount' => 'Monto: :package_price',
        'payment_receipt_invoice' => 'Código de factura: :invoice_code',
        'payment_receipt_view_invoice' => 'Ver factura',

        // Payment received email template
        'payment_received_admin_greeting' => '¡Hola Administrador!',
        'payment_received_message' => 'Pago recibido de :account_name:',
        'payment_received_account' => 'Cuenta: :account_name (:account_email)',
        'payment_received_package' => 'Paquete: :package_name',
        'payment_received_price' => 'Precio: :package_price_per_credit/crédito',
        'payment_received_total' => 'Total: :package_price por :package_number_of_listings créditos',
        'payment_received_save' => '(Ahorra :package_percent_discount%)',
        'payment_received_info' => 'Información del pago',
        'payment_received_customer' => 'Cliente: :account_name',
        'payment_received_amount' => 'Monto: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Hola :account_name,',
        'invoice_payment_from' => 'Está recibiendo este email de :site_title',
        'invoice_payment_attached' => 'La factura #:invoice_code está adjunta a este email.',
        'invoice_payment_view_online' => 'Ver en línea',
        'invoice_payment_thanks' => '¡Gracias por su pago!',
        'invoice_payment_info' => 'Información de la factura',
        'invoice_payment_code' => 'Código de factura: :invoice_code',
        'invoice_payment_view' => 'Ver factura',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Hola Administrador,',
        'free_credit_claimed_message' => ':account_name ha reclamado crédito gratuito en :site_title',
        'free_credit_claimed_info' => 'Información de la cuenta',
        'free_credit_claimed_name' => 'Nombre: :account_name',
        'free_credit_claimed_email' => 'Email: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => '¡Hola!',
        'password_reminder_message' => 'Está recibiendo este email porque recibimos una solicitud de restablecimiento de contraseña para su cuenta.',
        'password_reminder_button' => 'Restablecer contraseña',
        'password_reminder_no_action' => 'Si no solicitó un restablecimiento de contraseña, no se requiere ninguna acción adicional.',
        'password_reminder_regards' => 'Saludos,',
        'password_reminder_trouble' => 'Si tiene problemas para hacer clic en el botón "Restablecer contraseña", copie y pegue la siguiente URL en su navegador web: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => '¡Hola :account_name!',
        'job_alert_hiring' => 'Contratando :job_name en :company_name',
        'job_alert_apply_forward' => '👇 Aplique o reenvíe a un amigo: :job_url',
        'job_alert_message' => '¡Nuevas oportunidades laborales que coinciden con sus preferencias han sido publicadas!',
        'job_alert_job_info' => 'Empleo: :job_name',
        'job_alert_company_info' => 'Empresa: :company_name',
        'job_alert_view_job' => 'Ver empleo',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Confirmación de aplicación de empleo',
        'job_application_confirmation_greeting' => 'Estimado/a :job_application_name,',
        'job_application_confirmation_thanks' => 'Gracias por su interés en el puesto de :job_name en :company_name. Nos complace confirmar que su aplicación ha sido enviada exitosamente a través de nuestro sistema.',
        'job_application_confirmation_reviewing' => 'Nuestro equipo de reclutamiento está revisando sus calificaciones, y nos pondremos en contacto con usted si sus habilidades y experiencia coinciden con los requisitos para este puesto. Tenga en cuenta que debido al alto volumen de aplicaciones, este proceso puede tomar algún tiempo.',
        'job_application_confirmation_thanks_again' => '¡Gracias nuevamente por aplicar!',
        'job_application_confirmation_regards' => 'Saludos cordiales,',
        'job_application_confirmation_team' => 'Equipo de :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Hola,',
        'new_job_application_received' => 'Ha recibido una nueva aplicación de empleo.',
        'new_job_application_details' => 'Detalles de la aplicación:',
        'new_job_application_name' => 'Nombre: :job_application_name',
        'new_job_application_position' => 'Puesto: :job_application_position',
        'new_job_application_email' => 'Email: :job_application_email',
        'new_job_application_phone' => 'Teléfono: :job_application_phone',
    ],
];
