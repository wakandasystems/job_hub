<?php

namespace Botble\JobBoard\Supports;

class ProfileContactGuard
{
    public static function containsContactInfo(?string $value): bool
    {
        return self::matchContactInfo($value) !== null;
    }

    public static function obscure(?string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        foreach (self::patterns() as $replacement => $pattern) {
            $value = preg_replace($pattern, $replacement, $value) ?? $value;
        }

        return $value;
    }

    public static function violationMessage(string $fieldLabel): string
    {
        return __(
            ':field cannot include phone numbers, email addresses, WhatsApp/Telegram handles, social links, or other direct contact details. Add contact details only in the official contact fields. Profiles that bypass this rule may be suspended or banned.',
            ['field' => $fieldLabel]
        );
    }

    protected static function matchContactInfo(?string $value): ?array
    {
        $value = trim(strip_tags((string) $value));

        if ($value === '') {
            return null;
        }

        foreach (self::patterns() as $pattern) {
            if (preg_match($pattern, $value, $matches)) {
                return $matches;
            }
        }

        return null;
    }

    protected static function patterns(): array
    {
        return [
            '[email hidden]' => '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            '[link hidden]' => '~(?:https?://|www\.)[^\s<]+|(?:linkedin\.com|facebook\.com|instagram\.com|wa\.me|t\.me|twitter\.com|x\.com)[^\s<]*~i',
            '[phone hidden]' => '/(?<!\w)(?=(?:\D*\d){9,})(?:\+?\d[\d\s().-]{7,}\d)(?!\w)/',
            '[contact hidden]' => '/\b(?:whatsapp|telegram|call me|text me|email me|gmail|yahoo|hotmail|outlook)\b/i',
        ];
    }
}
