<?php

return [
    'templates' => [
        'admin-new-job-application' => [
            'title' => 'Nova candidatura a emprego (para utilizadores administradores)',
            'description' => 'Modelo de e-mail para enviar aviso aos administradores quando o sistema receber nova candidatura a emprego',
            'subject' => 'Nova candidatura a emprego',
        ],
        'employer-new-job-application' => [
            'title' => 'Nova candidatura a emprego (para empregador e colegas)',
            'description' => 'Modelo de e-mail para enviar aviso ao empregador e colegas quando o sistema receber nova candidatura a emprego',
            'subject' => 'Nova candidatura a emprego',
        ],
        'new-job-posted' => [
            'title' => 'Novo emprego publicado',
            'description' => 'Enviar e-mail ao administrador quando um novo emprego for publicado',
            'subject' => 'Novo emprego publicado em {{ site_title }} por {{ job_author }}',
        ],
        'new-company-profile-created' => [
            'title' => 'Novo perfil de empresa criado',
            'description' => 'Enviar e-mail ao administrador quando um empregador criar um novo perfil de empresa',
            'subject' => 'Novo perfil de empresa criado em {{ site_title }} por {{ employer_name }}',
        ],
        'job-expired-soon' => [
            'title' => 'Emprego expira em breve',
            'description' => 'Enviar e-mail ao autor se o seu emprego expirar nos próximos 3 dias',
            'subject' => 'O seu emprego "{{ job_name }}" expirará em {{ job_expired_after }} dias',
        ],
        'job-renewed' => [
            'title' => 'Emprego renovado',
            'description' => 'Enviar e-mail ao autor quando o seu emprego for renovado',
            'subject' => 'O seu emprego "{{ job_name }}" foi renovado automaticamente',
        ],
        'payment-receipt' => [
            'title' => 'Recibo de pagamento',
            'description' => 'Enviar notificação ao utilizador quando comprar créditos',
            'subject' => 'Recibo de pagamento do pacote {{ package_name }} em {{ site_title }}',
        ],
        'account-registered' => [
            'title' => 'Conta registada',
            'description' => 'Enviar notificação ao administrador quando um novo empregador/candidato a emprego se registar',
            'subject' => 'Novo {{ account_type }} registado em {{ site_title }}',
        ],
        'confirm-email' => [
            'title' => 'Confirmar e-mail',
            'description' => 'Enviar e-mail ao utilizador quando registar uma conta para verificar o seu e-mail',
            'subject' => 'Notificação de confirmação de e-mail',
        ],
        'password-reminder' => [
            'title' => 'Redefinir senha',
            'description' => 'Enviar e-mail ao utilizador ao solicitar redefinição de senha',
            'subject' => 'Redefinir senha',
        ],
        'free-credit-claimed' => [
            'title' => 'Crédito gratuito reclamado',
            'description' => 'Enviar notificação ao administrador quando o crédito gratuito for reclamado',
            'subject' => '{{ account_name }} reclamou crédito gratuito em {{ site_title }}',
        ],
        'payment-received' => [
            'title' => 'Pagamento recebido',
            'description' => 'Enviar notificação ao administrador quando alguém comprar créditos',
            'subject' => 'Pagamento recebido de {{ account_name }} em {{ site_title }}',
        ],
        'invoice-payment-created' => [
            'title' => 'Detalhes do pagamento da fatura',
            'description' => 'Enviar notificação ao cliente que faz o pagamento da publicação do emprego',
            'subject' => 'Pagamento recebido de {{ account_name }} em {{ site_title }}',
        ],
        'job-seeker-job-alert' => [
            'title' => 'Novo emprego publicado',
            'description' => 'Enviar e-mail ao candidato a emprego quando um novo emprego for publicado',
            'subject' => 'A contratar {{ job_name }} em {{ company_name }}',
        ],
        'job-approved' => [
            'title' => 'Emprego aprovado',
            'description' => 'Enviar e-mail ao autor quando o seu emprego for aprovado',
            'subject' => 'O seu emprego "{{ job_name }}" foi aprovado',
        ],
        'company-approved' => [
            'title' => 'Empresa aprovada',
            'description' => 'Enviar e-mail ao autor quando a sua empresa for aprovada',
            'subject' => 'A sua empresa "{{ company_name }}" foi aprovada',
        ],
        'job-seeker-applied-job' => [
            'title' => 'Confirmação de candidatura a emprego',
            'description' => 'Enviar e-mail ao candidato a emprego quando se candidatar a um emprego',
            'subject' => 'Confirmação de candidatura para {{ job_name }}',
        ],
    ],
    'variables' => [
        'name' => 'Nome',
        'position' => 'Posição',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'summary' => 'Resumo',
        'resume' => 'Currículo',
        'cover_letter' => 'Carta de apresentação',
        'job_application' => 'Candidatura a emprego',
        'job_name' => 'Nome do emprego',
        'job_url' => 'URL do emprego',
        'job_author' => 'Autor do emprego',
        'company_name' => 'Nome da empresa',
        'company_url' => 'URL da empresa',
        'employer_name' => 'Nome do empregador',
        'job_list' => 'URL da lista de empregos',
        'job_expired_after' => 'Emprego expira após x dias',
        'account_name' => 'Nome da conta',
        'account_email' => 'E-mail da conta',
        'package_name' => 'Nome do pacote',
        'package_price' => 'Preço',
        'package_percent_discount' => 'Percentagem de desconto',
        'package_number_of_listings' => 'Número de anúncios',
        'package_price_per_credit' => 'Preço por crédito',
        'account_type' => 'Tipo de conta (empregador/candidato a emprego)',
        'verify_link' => 'Link de verificação',
        'reset_link' => 'Link de redefinição',
        'invoice_code' => 'Código da fatura',
        'invoice_link' => 'Link da fatura',
    ],
    'email_templates' => [
        // Account registered email template
        'account_registered_admin_greeting' => 'Olá Administrador!',
        'account_registered_new_account' => 'Um novo :account_type registou-se:',
        'account_registered_name' => 'Nome: <strong>:account_name</strong>',
        'account_registered_email' => 'E-mail: <strong>:account_email</strong>',

        // Admin new job application email template
        'admin_job_application_greeting' => 'Olá, recebemos uma nova candidatura a emprego de :site_title!',
        'admin_job_application_name' => 'Nome: :job_application_name',
        'admin_job_application_position' => 'Posição: :job_application_position',
        'admin_job_application_email' => 'E-mail: :job_application_email',
        'admin_job_application_phone' => 'Telefone: :job_application_phone',
        'admin_job_application_summary' => 'Resumo: :job_application_summary',
        'admin_job_application_resume' => 'Currículo: :job_application_resume',
        'admin_job_application_cover_letter' => 'Carta de apresentação: :job_application_cover_letter',

        // Employer new job application email template
        'employer_job_application_greeting' => 'Olá, recebemos uma nova candidatura a emprego de :site_title!',
        'employer_job_application_name' => 'Nome: :job_application_name',
        'employer_job_application_position' => 'Posição: :job_application_position',
        'employer_job_application_email' => 'E-mail: :job_application_email',
        'employer_job_application_phone' => 'Telefone: :job_application_phone',
        'employer_job_application_summary' => 'Resumo: :job_application_summary',
        'employer_job_application_resume' => 'Currículo: :job_application_resume',
        'employer_job_application_cover_letter' => 'Carta de apresentação: :job_application_cover_letter',

        // Company approved email template
        'company_approved_title' => 'Empresa aprovada',
        'company_approved_greeting' => 'Olá,',
        'company_approved_message' => 'Temos o prazer de informar que a sua empresa foi aprovada e está agora ativa na nossa plataforma.',
        'company_approved_info' => 'Informações da empresa',
        'company_approved_name' => 'Nome: <strong>:company_name</strong>',
        'company_approved_view' => 'Ver',
        'company_approved_here' => 'aqui',

        // Confirm email template
        'confirm_email_greeting' => 'Olá!',
        'confirm_email_message' => 'Por favor, verifique o seu endereço de e-mail para aceder a este website. Clique no botão abaixo para verificar o seu e-mail.',
        'confirm_email_button' => 'Verificar agora',
        'confirm_email_regards' => 'Cumprimentos,',
        'confirm_email_trouble' => 'Se tiver problemas em clicar no botão "Verificar agora", copie e cole o URL abaixo no seu navegador: :verify_link',

        // Job approved email template
        'job_approved_title' => 'Emprego aprovado',
        'job_approved_greeting' => 'Olá :job_author,',
        'job_approved_message' => 'Temos o prazer de informar que o seu anúncio de emprego foi aprovado e está agora ativo na nossa plataforma.',
        'job_approved_info' => 'Informações do emprego',
        'job_approved_job_title' => 'Título do emprego: <strong>:job_name</strong>',
        'job_approved_view' => 'Ver',
        'job_approved_here' => 'aqui',

        // Job expired soon email template
        'job_expired_soon_greeting' => 'Olá :job_author!',
        'job_expired_soon_message' => 'O seu emprego <a href=":job_url">:job_name</a> expirará em :job_expired_after dias.',
        'job_expired_soon_renew' => 'Por favor <a href=":job_list">vá aqui</a> para renovar o seu emprego.',

        // Job renewed email template
        'job_renewed_greeting' => 'Olá :job_author!',
        'job_renewed_message' => 'O seu emprego <a href=":job_url">:job_name</a> foi renovado automaticamente.',

        // New job posted email template
        'new_job_posted_title' => 'Novo emprego publicado',
        'new_job_posted_admin_greeting' => 'Olá Administrador,',
        'new_job_posted_message' => 'Temos o prazer de informar que um novo anúncio de emprego foi publicado por um empregador na nossa plataforma.',
        'new_job_posted_info' => 'Publicação de emprego',
        'new_job_posted_employer' => 'Empregador: <strong>:job_author</strong>',
        'new_job_posted_job_title' => 'Título do emprego: <strong>:job_name</strong>',
        'new_job_posted_admin_link' => 'Link do painel de administração',
        'new_job_posted_here' => 'aqui',

        // New company profile created email template
        'new_company_profile_title' => 'Novo perfil de empresa criado',
        'new_company_profile_admin_greeting' => 'Olá Administrador!',
        'new_company_profile_message' => 'Um novo perfil de empresa foi criado por :employer_name ":company_name"',
        'new_company_profile_info' => 'Informações da empresa',
        'new_company_profile_employer' => 'Empregador: <strong>:employer_name</strong>',
        'new_company_profile_name' => 'Nome da empresa: <strong>:company_name</strong>',
        'new_company_profile_admin_link' => 'Link do painel de administração',
        'new_company_profile_here' => 'aqui',

        // Payment receipt email template
        'payment_receipt_greeting' => 'Olá :account_name!',
        'payment_receipt_message' => 'Recibo de pagamento da sua compra:',
        'payment_receipt_package' => 'Pacote: :package_name',
        'payment_receipt_price' => 'Preço: :package_price_per_credit/crédito',
        'payment_receipt_total' => 'Total: :package_price por :package_number_of_listings créditos',
        'payment_receipt_save' => '(Poupa :package_percent_discount%)',
        'payment_receipt_thanks' => 'Obrigado pelo seu pagamento!',
        'payment_receipt_info' => 'Informações de pagamento',
        'payment_receipt_amount' => 'Montante: :package_price',
        'payment_receipt_invoice' => 'Código da fatura: :invoice_code',
        'payment_receipt_view_invoice' => 'Ver fatura',

        // Payment received email template
        'payment_received_admin_greeting' => 'Olá Administrador!',
        'payment_received_message' => 'Pagamento recebido de :account_name:',
        'payment_received_account' => 'Conta: :account_name (:account_email)',
        'payment_received_package' => 'Pacote: :package_name',
        'payment_received_price' => 'Preço: :package_price_per_credit/crédito',
        'payment_received_total' => 'Total: :package_price por :package_number_of_listings créditos',
        'payment_received_save' => '(Poupa :package_percent_discount%)',
        'payment_received_info' => 'Informações de pagamento',
        'payment_received_customer' => 'Cliente: :account_name',
        'payment_received_amount' => 'Montante: :package_price',

        // Invoice payment created email template
        'invoice_payment_greeting' => 'Olá :account_name,',
        'invoice_payment_from' => 'Está a receber e-mail de :site_title',
        'invoice_payment_attached' => 'A fatura #:invoice_code está anexada a este e-mail.',
        'invoice_payment_view_online' => 'Ver online',
        'invoice_payment_thanks' => 'Obrigado pelo seu pagamento!',
        'invoice_payment_info' => 'Informações da fatura',
        'invoice_payment_code' => 'Código da fatura: :invoice_code',
        'invoice_payment_view' => 'Ver fatura',

        // Free credit claimed email template
        'free_credit_claimed_admin_greeting' => 'Olá Administrador,',
        'free_credit_claimed_message' => ':account_name reclamou crédito gratuito em :site_title',
        'free_credit_claimed_info' => 'Informações da conta',
        'free_credit_claimed_name' => 'Nome: :account_name',
        'free_credit_claimed_email' => 'E-mail: :account_email',

        // Password reminder email template
        'password_reminder_greeting' => 'Olá!',
        'password_reminder_message' => 'Está a receber este e-mail porque recebemos um pedido de redefinição de senha para a sua conta.',
        'password_reminder_button' => 'Redefinir senha',
        'password_reminder_no_action' => 'Se não solicitou uma redefinição de senha, não é necessária nenhuma ação adicional.',
        'password_reminder_regards' => 'Cumprimentos,',
        'password_reminder_trouble' => 'Se tiver problemas em clicar no botão "Redefinir senha", copie e cole o URL abaixo no seu navegador: :reset_link',

        // Job seeker job alert email template
        'job_alert_greeting' => 'Olá :account_name!',
        'job_alert_hiring' => 'A contratar :job_name em :company_name',
        'job_alert_apply_forward' => '👇 Candidate-se ou encaminhe para um amigo: :job_url',
        'job_alert_message' => 'Novas oportunidades de emprego correspondentes às suas preferências foram publicadas!',
        'job_alert_job_info' => 'Emprego: :job_name',
        'job_alert_company_info' => 'Empresa: :company_name',
        'job_alert_view_job' => 'Ver emprego',

        // Job seeker applied job email template
        'job_application_confirmation_title' => 'Confirmação de candidatura a emprego',
        'job_application_confirmation_greeting' => 'Caro/a :job_application_name,',
        'job_application_confirmation_thanks' => 'Obrigado pelo seu interesse na posição :job_name em :company_name. Temos o prazer de confirmar que a sua candidatura foi submetida com sucesso através do nosso sistema.',
        'job_application_confirmation_reviewing' => 'A nossa equipa de recrutamento está a rever as suas qualificações e entraremos em contacto consigo se as suas competências e experiência corresponderem aos requisitos para esta função. Por favor, note que devido ao elevado volume de candidaturas, este processo pode demorar algum tempo.',
        'job_application_confirmation_thanks_again' => 'Obrigado novamente por se candidatar!',
        'job_application_confirmation_regards' => 'Cumprimentos,',
        'job_application_confirmation_team' => 'Equipa :company_name',

        // New job application (simplified) template
        'new_job_application_greeting' => 'Olá,',
        'new_job_application_received' => 'Recebeu uma nova candidatura a emprego.',
        'new_job_application_details' => 'Detalhes da candidatura:',
        'new_job_application_name' => 'Nome: :job_application_name',
        'new_job_application_position' => 'Posição: :job_application_position',
        'new_job_application_email' => 'E-mail: :job_application_email',
        'new_job_application_phone' => 'Telefone: :job_application_phone',
    ],
];
