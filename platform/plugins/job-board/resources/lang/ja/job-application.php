<?php

return [
    'name' => '応募',
    'edit' => 'View job application',
    'tables' => [
        'email' => 'メール',
        'phone' => '電話',
        'name' => 'Name',
        'first_name' => '名',
        'last_name' => '姓',
        'time' => 'Time',
        'message' => 'Summary',
        'resume' => '履歴書',
        'cover_letter' => 'Cover Letter',
        'position' => '職位',
        'download_resume' => 'Download Resume',
    ],
    'information' => 'Information',
    'email' => [
        'header' => 'メール',
        'title' => 'We received a new job application from the website!',
        'success' => 'Applied successfully!',
        'external_redirect' => 'Redirecting to the job site...',
        'failed' => 'Can\'t apply on this time, please try again later!',
    ],
    'sender' => 'Sender',
    'sender_email' => 'メール',
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
