<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jb_companies')
            ->whereIn('logo', [
                'companies/gozambia-logo-qnedhcp7iduquyak.webp',
                'companies/jszm-logo-6a243a96aa208.webp',
                'companies/gozambia-logo-8sloxvaic2sinf3w.webp',
                'companies/jszm-logo-6a243aa1dde34.webp',
                'companies/jszm-logo-6a243a9eb6675.webp',
                'companies/jszm-logo-6a243a8fe32f6.webp',
                'companies/jszm-logo-6a243a99cdf07.webp',
                'companies/jszm-logo-6a243a963ee7c.webp',
                'companies/jszm-logo-6a243a94a25fe.webp',
                'companies/jszm-logo-6a243a9379c27.webp',
                'companies/gozambia-logo-nu371hwsfxdvm2eh.webp',
                'companies/gozambia-logo-klh35s3xl7bzx6a6.webp',
            ])
            ->update(['logo' => 'chatgpt-image-may-14-2026-03-00-04-pm.png']);
    }

    public function down(): void
    {
        // Crawler-site branding must not be restored.
    }
};
