<?php

return [
    'name' => 'ใบสมัครงาน',
    'edit' => 'ดูใบสมัครงาน',
    'tables' => [
        'email' => 'อีเมล',
        'phone' => 'โทรศัพท์',
        'name' => 'ชื่อ',
        'first_name' => 'ชื่อ',
        'last_name' => 'นามสกุล',
        'time' => 'เวลา',
        'message' => 'สรุป',
        'resume' => 'ประวัติย่อ',
        'cover_letter' => 'จดหมายนำ',
        'position' => 'ตำแหน่ง',
        'download_resume' => 'ดาวน์โหลดประวัติย่อ',
    ],
    'information' => 'ข้อมูล',
    'email' => [
        'header' => 'อีเมล',
        'title' => 'เราได้รับใบสมัครงานใหม่จากเว็บไซต์!',
        'success' => 'สมัครสำเร็จ!',
        'external_redirect' => 'กำลังเปลี่ยนเส้นทางไปยังเว็บไซต์งาน...',
        'failed' => 'ไม่สามารถสมัครในขณะนี้ กรุณาลองใหม่อีกครั้งในภายหลัง!',
    ],
    'sender' => 'ผู้ส่ง',
    'sender_email' => 'อีเมล',
    'statuses' => [
        'pending' => 'รอดำเนินการ',
        'checked' => 'ตรวจสอบแล้ว',
    ],
    'notifications' => [
        'title' => 'ใบสมัครงานใหม่',
        'description' => 'คุณมีใบสมัครงานใหม่จาก :name',
        'view' => 'ดู',
    ],
];
