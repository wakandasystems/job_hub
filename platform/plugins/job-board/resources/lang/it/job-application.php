<?php

return [
    'name' => 'Candidature',
    'edit' => 'View job application',
    'tables' => [
        'email' => 'Email',
        'phone' => 'Telefono',
        'name' => 'Name',
        'first_name' => 'Nome',
        'last_name' => 'Cognome',
        'time' => 'Time',
        'message' => 'Summary',
        'resume' => 'Curriculum',
        'cover_letter' => 'Cover Letter',
        'position' => 'Posizione',
        'download_resume' => 'Download Resume',
    ],
    'information' => 'Information',
    'email' => [
        'header' => 'Email',
        'title' => 'We received a new job application from the website!',
        'success' => 'Applied successfully!',
        'external_redirect' => 'Redirecting to the job site...',
        'failed' => 'Can\'t apply on this time, please try again later!',
    ],
    'sender' => 'Sender',
    'sender_email' => 'Email',
    'statuses' => [
        'pending' => 'Pending',
        'checked' => 'Checked',
    ],
    'notifications' => [
        'title' => 'New job application',
        'description' => 'You have a new job application from :name',
        'view' => 'View',
    ],
];
