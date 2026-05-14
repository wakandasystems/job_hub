<?php

return [
    'name' => 'Job Applications',
    'edit' => 'View job application',
    'tables' => [
        'email' => 'E-pošta',
        'phone' => 'Telefon',
        'name' => 'Ime',
        'first_name' => 'Ime',
        'last_name' => 'Prezime',
        'time' => 'Time',
        'message' => 'Summary',
        'resume' => 'Životopis',
        'cover_letter' => 'Cover Letter',
        'position' => 'Pozicija',
        'download_resume' => 'Download Resume',
    ],
    'information' => 'Information',
    'email' => [
        'header' => 'E-pošta',
        'title' => 'We received a new job application from the website!',
        'success' => 'Applied successfully!',
        'external_redirect' => 'Redirecting to the job site...',
        'failed' => 'Can\'t apply on this time, please try again later!',
    ],
    'sender' => 'Sender',
    'sender_email' => 'E-pošta',
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
