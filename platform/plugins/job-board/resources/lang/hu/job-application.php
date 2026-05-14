<?php

return [
    'name' => 'Állásjelentkezések',
    'edit' => 'View job application',
    'tables' => [
        'email' => 'E-mail',
        'phone' => 'Telefon',
        'name' => 'Name',
        'first_name' => 'Keresztnév',
        'last_name' => 'Vezetéknév',
        'time' => 'Time',
        'message' => 'Summary',
        'resume' => 'Önéletrajz',
        'cover_letter' => 'Cover Letter',
        'position' => 'Pozíció',
        'download_resume' => 'Download Resume',
    ],
    'information' => 'Information',
    'email' => [
        'header' => 'E-mail',
        'title' => 'We received a new job application from the website!',
        'success' => 'Applied successfully!',
        'external_redirect' => 'Redirecting to the job site...',
        'failed' => 'Can\'t apply on this time, please try again later!',
    ],
    'sender' => 'Sender',
    'sender_email' => 'E-mail',
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
