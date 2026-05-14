<?php

return [
    'name' => 'Sollicitaties',
    'edit' => 'Sollicitatie bekijken',
    'tables' => [
        'email' => 'E-mailadres',
        'phone' => 'Telefoonnummer',
        'name' => 'Naam',
        'first_name' => 'Voornaam',
        'last_name' => 'Achternaam',
        'time' => 'Tijd',
        'message' => 'Samenvatting',
        'resume' => 'CV',
        'cover_letter' => 'Motivatiebrief',
        'position' => 'Functie',
        'download_resume' => 'CV downloaden',
    ],
    'information' => 'Informatie',
    'email' => [
        'header' => 'E-mail',
        'title' => 'We hebben een nieuwe sollicitatie ontvangen van de website!',
        'success' => 'Succesvol gesolliciteerd!',
        'external_redirect' => 'Doorverwijzen naar de vacaturesite...',
        'failed' => 'Kan op dit moment niet solliciteren, probeer het later opnieuw!',
    ],
    'sender' => 'Afzender',
    'sender_email' => 'E-mailadres',
    'statuses' => [
        'pending' => 'In behandeling',
        'checked' => 'Gecontroleerd',
    ],
    'notifications' => [
        'title' => 'Nieuwe sollicitatie',
        'description' => 'U heeft een nieuwe sollicitatie van :name',
        'view' => 'Bekijken',
    ],
];
