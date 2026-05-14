<?php

return [
    'name' => 'Candidatures d\'emploi',
    'edit' => 'Voir la candidature',
    'tables' => [
        'email' => 'Email',
        'phone' => 'Téléphone',
        'name' => 'Nom',
        'first_name' => 'Prénom',
        'last_name' => 'Nom de famille',
        'time' => 'Heure',
        'message' => 'Résumé',
        'resume' => 'CV',
        'cover_letter' => 'Lettre de motivation',
        'position' => 'Poste',
        'download_resume' => 'Télécharger le CV',
    ],
    'information' => 'Information',
    'email' => [
        'header' => 'Email',
        'title' => 'Nous avons reçu une nouvelle candidature depuis le site web !',
        'success' => 'Candidature envoyée avec succès !',
        'external_redirect' => 'Redirection vers le site d\'emploi...',
        'failed' => 'Impossible de postuler pour le moment, veuillez réessayer plus tard !',
    ],
    'sender' => 'Expéditeur',
    'sender_email' => 'Email',
    'statuses' => [
        'pending' => 'En attente',
        'checked' => 'Vérifiée',
    ],
    'notifications' => [
        'title' => 'Nouvelle candidature',
        'description' => 'Vous avez une nouvelle candidature de :name',
        'view' => 'Voir',
    ],
];
