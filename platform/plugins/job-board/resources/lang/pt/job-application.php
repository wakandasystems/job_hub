<?php

return [
    'name' => 'Candidaturas a emprego',
    'edit' => 'Ver candidatura a emprego',
    'tables' => [
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'name' => 'Nome',
        'first_name' => 'Primeiro nome',
        'last_name' => 'Apelido',
        'time' => 'Hora',
        'message' => 'Resumo',
        'resume' => 'Currículo',
        'cover_letter' => 'Carta de apresentação',
        'position' => 'Posição',
        'download_resume' => 'Transferir currículo',
    ],
    'information' => 'Informação',
    'email' => [
        'header' => 'E-mail',
        'title' => 'Recebemos uma nova candidatura a emprego do website!',
        'success' => 'Candidatura enviada com sucesso!',
        'external_redirect' => 'A redirecionar para o site do emprego...',
        'failed' => 'Não é possível candidatar-se neste momento, por favor tente novamente mais tarde!',
    ],
    'sender' => 'Remetente',
    'sender_email' => 'E-mail',
    'statuses' => [
        'pending' => 'Pendente',
        'checked' => 'Verificado',
    ],
    'notifications' => [
        'title' => 'Nova candidatura a emprego',
        'description' => 'Tem uma nova candidatura a emprego de :name',
        'view' => 'Ver',
    ],
];
