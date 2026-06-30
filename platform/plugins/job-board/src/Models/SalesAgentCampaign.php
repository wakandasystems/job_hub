<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class SalesAgentCampaign extends BaseModel
{
    protected $table = 'jb_sales_agent_campaigns';

    protected $fillable = [
        'name',
        'product_type',
        'product_label',
        'landing_headline',
        'landing_body',
        'landing_cta_text',
        'share_message_template',
        'prompt_template',
        'inspiration_images',
        'reconstruction_layout',
        'aspect_ratio',
        'promo_price',
        'promo_original_price',
        'promo_end_date',
        'is_active',
    ];

    protected $casts = [
        'inspiration_images' => 'array',
        'reconstruction_layout' => 'array',
        'promo_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function marketingImages(): HasMany
    {
        return $this->hasMany(SalesAgentMarketingImage::class, 'campaign_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(SalesAgentCampaignLead::class, 'campaign_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(SalesAgentCampaignClick::class, 'campaign_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SalesAgentCampaignVersion::class, 'campaign_id')->latest('id');
    }

    public function latestCompletedMarketingImage(): HasOne
    {
        return $this->hasOne(SalesAgentMarketingImage::class, 'campaign_id')
            ->where('status', 'completed')
            ->whereNotNull('image_path')
            ->latestOfMany();
    }

    public function latestMarketingImage(): HasOne
    {
        return $this->hasOne(SalesAgentMarketingImage::class, 'campaign_id')
            ->latestOfMany();
    }

    public static function productTypeOptions(): array
    {
        return [
            'auto_apply' => 'Auto Apply',
            'vip_alert' => 'VIP Alert',
            'job_alert' => 'Job Alert',
            'career_service' => 'Career Service',
        ];
    }

    public function resolvedProductLabel(): string
    {
        return trim((string) $this->product_label) !== ''
            ? (string) $this->product_label
            : static::productTypeOptions()[$this->product_type] ?? ucfirst(str_replace('_', ' ', (string) $this->product_type));
    }

    public function shareUrlForAgent(SalesAgent $agent): string
    {
        try {
            return route('public.sales-agent-campaigns.show', [$agent->code, $this->getKey()]);
        } catch (RouteNotFoundException) {
            return url(sprintf(
                'agent-offers/%s/%d',
                rawurlencode((string) $agent->code),
                $this->getKey()
            ));
        }
    }

    public function resolvedLandingHeadline(): string
    {
        return trim((string) $this->landing_headline) !== ''
            ? (string) $this->landing_headline
            : $this->name;
    }

    public function resolvedLandingBody(): string
    {
        if (trim((string) $this->landing_body) !== '') {
            return (string) $this->landing_body;
        }

        $body = 'Enter your details below and our team will contact you to activate ' . $this->resolvedProductLabel() . '.';

        if ($this->isPromoCampaign()) {
            $body .= ' Current offer: ' . $this->promo_price . ' instead of ' . $this->promo_original_price . '.';
        } elseif ($this->hasPrice()) {
            $body .= ' Price: ' . $this->promo_price . '.';
        }

        return $body;
    }

    public function resolvedLandingCtaText(): string
    {
        return trim((string) $this->landing_cta_text) !== ''
            ? (string) $this->landing_cta_text
            : 'Request Activation';
    }

    public function buildShareMessage(SalesAgent $agent): string
    {
        $link = $this->shareUrlForAgent($agent);
        $replacements = $this->singleBracePlaceholderValues($agent, $link);

        $template = trim((string) $this->share_message_template);

        if ($template === '') {
            $lines = [
                'Hi ' . $agent->name . ',',
                '',
                'Share this ' . $this->resolvedProductLabel() . ' offer link with interested customers:',
                $link,
            ];

            if ($this->isPromoCampaign()) {
                $promo = 'Offer: ' . $this->promo_price . ' instead of ' . $this->promo_original_price;
                $lines[] = '';
                $lines[] = $promo;
            } elseif ($this->hasPrice()) {
                $lines[] = '';
                $lines[] = 'Price: ' . $this->promo_price;
            }

            $lines[] = '';
            $lines[] = 'CTA: ' . $this->resolvedLandingCtaText();

            return implode("\n", $lines);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function hasPrice(): bool
    {
        return trim((string) $this->promo_price) !== '';
    }

    public function isPromoCampaign(): bool
    {
        return $this->hasPrice()
            && trim((string) $this->promo_original_price) !== ''
            && $this->promo_end_date !== null;
    }

    public function inspirationImages(): array
    {
        return array_slice(array_values(array_filter((array) $this->inspiration_images)), 0, 1);
    }

    public function snapshotData(): array
    {
        return Arr::only($this->attributesToArray(), $this->snapshotFieldNames());
    }

    public function applySnapshot(array $snapshot): void
    {
        $data = Arr::only($snapshot, $this->snapshotFieldNames());
        $data['inspiration_images'] = array_slice(array_values(array_filter((array) ($data['inspiration_images'] ?? []))), 0, 1);
        $data['reconstruction_layout'] = is_array($data['reconstruction_layout'] ?? null) ? $data['reconstruction_layout'] : null;
        $this->fill($data);
        $this->save();
    }

    public static function promptPlaceholderDescriptions(): array
    {
        return [
            'agent_name' => 'Sales agent name',
            'agent_phone' => 'Sales agent phone number',
            'agent_code' => 'Sales agent referral code',
            'campaign_name' => 'Campaign name',
            'product_label' => 'Resolved public product label',
            'landing_headline' => 'Resolved landing headline',
            'landing_body' => 'Resolved landing copy',
            'cta' => 'Resolved call-to-action text',
            'promo_price' => 'Displayed price if set',
            'promo_original_price' => 'Crossed-out original price only when this is a promo',
            'promo_end_date' => 'Promo end date only when this is a promo',
            'price_line' => 'Ready-to-use pricing line',
            'promo_badge' => '"PROMO" when promo is active, else blank',
            'promo_status' => '"promo" or "standard"',
            'promo_deadline_line' => 'Ready-to-use deadline line',
            'link' => 'Public landing link for the current agent',
        ];
    }

    public static function sharePlaceholderDescriptions(): array
    {
        return [
            'agent_name' => 'Sales agent name',
            'agent_code' => 'Sales agent referral code',
            'campaign_name' => 'Campaign name',
            'product_label' => 'Resolved public product label',
            'promo_price' => 'Displayed price if set',
            'promo_original_price' => 'Crossed-out original price only when this is a promo',
            'promo_end_date' => 'Promo end date only when this is a promo',
            'price_line' => 'Ready-to-use pricing line',
            'promo_badge' => '"PROMO" when promo is active, else blank',
            'promo_status' => '"promo" or "standard"',
            'promo_deadline_line' => 'Ready-to-use deadline line',
            'link' => 'Public landing link for the current agent',
            'headline_zone' => 'Recommended placement instruction for the main headline',
            'body_zone' => 'Recommended placement instruction for supporting copy',
            'price_zone' => 'Recommended placement instruction for the price callout',
            'cta_zone' => 'Recommended placement instruction for the CTA/button area',
            'logo_zone' => 'Recommended placement instruction for Wakanda Jobs branding',
            'text_layout_brief' => 'Ready-to-use overall text placement/layout instruction block',
            'cta' => 'Resolved call-to-action text',
        ];
    }

    public function promptPlaceholderValues(SalesAgent $agent, ?string $link = null): array
    {
        $link ??= $this->shareUrlForAgent($agent);

        return [
            'agent_name' => $agent->name,
            'agent_phone' => $agent->phone,
            'agent_code' => $agent->code,
            'campaign_name' => $this->name,
            'product_label' => $this->resolvedProductLabel(),
            'landing_headline' => $this->resolvedLandingHeadline(),
            'landing_body' => $this->resolvedLandingBody(),
            'cta' => $this->resolvedLandingCtaText(),
            'promo_price' => $this->hasPrice() ? (string) $this->promo_price : '',
            'promo_original_price' => $this->isPromoCampaign() ? (string) $this->promo_original_price : '',
            'promo_end_date' => $this->isPromoCampaign() ? ($this->promo_end_date?->format('d M Y') ?: '') : '',
            'price_line' => $this->priceLine(),
            'promo_badge' => $this->isPromoCampaign() ? 'PROMO' : '',
            'promo_status' => $this->isPromoCampaign() ? 'promo' : 'standard',
            'promo_deadline_line' => $this->promoDeadlineLine(),
            'link' => $link,
            'headline_zone' => $this->headlineZoneInstruction(),
            'body_zone' => $this->bodyZoneInstruction(),
            'price_zone' => $this->priceZoneInstruction(),
            'cta_zone' => $this->ctaZoneInstruction(),
            'logo_zone' => $this->logoZoneInstruction(),
            'text_layout_brief' => $this->textLayoutBrief(),
        ];
    }

    public function replacePromptPlaceholders(string $template, SalesAgent $agent, ?string $link = null): string
    {
        $values = $this->promptPlaceholderValues($agent, $link);
        $replacements = [];

        foreach ($values as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
            $replacements['{' . $key . '}'] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    protected function singleBracePlaceholderValues(SalesAgent $agent, ?string $link = null): array
    {
        $values = $this->promptPlaceholderValues($agent, $link);

        return Collection::make($values)
            ->mapWithKeys(fn (string $value, string $key): array => ['{' . $key . '}' => $value])
            ->all();
    }

    protected function snapshotFieldNames(): array
    {
        return [
            'name',
            'product_type',
            'product_label',
            'landing_headline',
            'landing_body',
            'landing_cta_text',
            'share_message_template',
            'prompt_template',
            'inspiration_images',
            'reconstruction_layout',
            'aspect_ratio',
            'promo_price',
            'promo_original_price',
            'promo_end_date',
            'is_active',
        ];
    }

    public function priceLine(): string
    {
        if ($this->isPromoCampaign()) {
            return 'Now ' . $this->promo_price . ' instead of ' . $this->promo_original_price;
        }

        if ($this->hasPrice()) {
            return 'Price: ' . $this->promo_price;
        }

        return '';
    }

    public function promoDeadlineLine(): string
    {
        if (! $this->isPromoCampaign()) {
            return '';
        }

        return 'Offer ends ' . $this->promo_end_date?->format('d M Y');
    }

    public function headlineZoneInstruction(): string
    {
        return 'Place the main headline in the strongest existing headline area from the inspiration poster, usually the upper-left or upper-center text block, keeping the same hierarchy, line breaks style, and dominant scale.';
    }

    public function bodyZoneInstruction(): string
    {
        return 'Place the supporting copy directly under or beside the headline in the secondary text block from the inspiration layout, preserving the same alignment, width, spacing rhythm, and text-box proportions.';
    }

    public function priceZoneInstruction(): string
    {
        if (! $this->hasPrice()) {
            return 'If the inspiration layout has a price badge or offer chip, leave that zone clean or repurpose it for a short value callout without inventing a new pricing element.';
        }

        return $this->isPromoCampaign()
            ? 'Use the inspiration poster’s strongest offer badge/price sticker area for the promo price, with the original price visually subordinate or crossed out in the same style treatment.'
            : 'Use the inspiration poster’s offer/price callout area for the current price, keeping the same badge shape, emphasis, and contrast style.';
    }

    public function ctaZoneInstruction(): string
    {
        return 'Place the CTA in the existing button/call-to-action zone from the inspiration poster, usually near the lower text block, keeping the same visual weight, shape language, and spacing.';
    }

    public function logoZoneInstruction(): string
    {
        return 'Place the Wakanda Jobs logo only where the inspiration design naturally supports a brand mark, such as the top corner or footer brand area, without introducing a new logo position.';
    }

    public function textLayoutBrief(): string
    {
        return trim(implode(' ', array_filter([
            $this->headlineZoneInstruction(),
            $this->bodyZoneInstruction(),
            $this->priceZoneInstruction(),
            $this->ctaZoneInstruction(),
            $this->logoZoneInstruction(),
        ])));
    }
}
