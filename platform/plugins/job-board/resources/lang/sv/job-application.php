<?php

return [
    'name' => 'Jobbansökningar',
    'edit' => 'Visa jobbansökan',
    'tables' => [
        'email' => 'E-post',
        'phone' => 'Telefon',
        'name' => 'Namn',
        'first_name' => 'Förnamn',
        'last_name' => 'Efternamn',
        'time' => 'Tid',
        'message' => 'Sammanfattning',
        'resume' => 'CV',
        'cover_letter' => 'Personligt brev',
        'position' => 'Position',
        'download_resume' => 'Ladda ner CV',
    ],
    'information' => 'Information',
    'email' => [
        'header' => 'E-post',
        'title' => 'Vi har tagit emot en ny jobbansökan från webbplatsen!',
        'success' => 'Ansökan skickad!',
        'external_redirect' => 'Omdirigerar till jobbwebbplatsen...',
        'failed' => 'Kan inte ansöka för tillfället, försök igen senare!',
    ],
    'sender' => 'Avsändare',
    'sender_email' => 'E-post',
    'statuses' => [
        'pending' => 'Väntande',
        'checked' => 'Kontrollerad',
    ],
    'notifications' => [
        'title' => 'Ny jobbansökan',
        'description' => 'Du har en ny jobbansökan från :name',
        'view' => 'Visa',
    ],
];
