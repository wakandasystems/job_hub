<?php

return [
    'name' => 'Työhakemukset',
    'edit' => 'Katso työhakemus',
    'tables' => [
        'email' => 'Sähköposti',
        'phone' => 'Puhelin',
        'name' => 'Nimi',
        'first_name' => 'Etunimi',
        'last_name' => 'Sukunimi',
        'time' => 'Aika',
        'message' => 'Yhteenveto',
        'resume' => 'Ansioluettelo',
        'cover_letter' => 'Saatekirje',
        'position' => 'Asema',
        'download_resume' => 'Lataa ansioluettelo',
    ],
    'information' => 'Tiedot',
    'email' => [
        'header' => 'Sähköposti',
        'title' => 'Saimme uuden työhakemuksen verkkosivustolta!',
        'success' => 'Hakemus lähetetty onnistuneesti!',
        'external_redirect' => 'Ohjataan työpaikkapaikkaan...',
        'failed' => 'Ei voida hakea tällä hetkellä, yritä myöhemmin uudelleen!',
    ],
    'sender' => 'Lähettäjä',
    'sender_email' => 'Sähköposti',
    'statuses' => [
        'pending' => 'Odottaa',
        'checked' => 'Tarkistettu',
    ],
    'notifications' => [
        'title' => 'Uusi työhakemus',
        'description' => 'Sinulle on uusi työhakemus käyttäjältä :name',
        'view' => 'Katso',
    ],
];
