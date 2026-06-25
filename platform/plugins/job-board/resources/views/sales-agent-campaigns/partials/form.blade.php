<div class="row">
    <div class="col-md-8">
        <x-core::card>
            <x-core::card.body>
                <x-core::form.text-input
                    label="Campaign Name"
                    name="name"
                    placeholder="e.g. Christmas 2026 Promo"
                    :value="old('name', $campaign->name)"
                />

                <x-core::form.textarea
                    label="Image Prompt Template"
                    name="prompt_template"
                    rows="20"
                    :value="old('prompt_template', $campaign->prompt_template)"
                    :helper-text="'Use @{{agent_name}}, @{{agent_phone}}, @{{agent_code}}, @{{promo_price}}, @{{promo_original_price}}, @{{promo_end_date}} as placeholders — they get filled in automatically per agent when generating a poster.'"
                />
            </x-core::card.body>
        </x-core::card>
    </div>

    <div class="col-md-4">
        <x-core::card class="mb-3">
            <x-core::card.body>
                <x-core::form.select
                    label="Format"
                    name="aspect_ratio"
                    :options="[
                        'portrait_4_5' => 'Portrait 4:5 (Facebook/Instagram feed)',
                        'square_1_1' => 'Square 1:1 (WhatsApp/general)',
                        'landscape_16_9' => 'Landscape 16:9 (banner/header)',
                    ]"
                    :value="old('aspect_ratio', $campaign->aspect_ratio ?: 'portrait_4_5')"
                />

                <x-core::form.text-input
                    label="Promo Price"
                    name="promo_price"
                    placeholder="e.g. K100"
                    :value="old('promo_price', $campaign->promo_price)"
                />

                <x-core::form.text-input
                    label="Original Price (crossed out)"
                    name="promo_original_price"
                    placeholder="e.g. K250"
                    :value="old('promo_original_price', $campaign->promo_original_price)"
                />

                <x-core::form-group>
                    <x-core::form.label label="Promo Ends" for="promo_end_date" />
                    {!! Form::datePicker('promo_end_date', old('promo_end_date', $campaign->promo_end_date?->format(BaseHelper::getDateFormat())), [
                        'id' => 'promo_end_date',
                    ]) !!}
                </x-core::form-group>

                <x-core::form.checkbox
                    label="Active"
                    name="is_active"
                    value="1"
                    :checked="old('is_active', $campaign->exists ? $campaign->is_active : true)"
                />
            </x-core::card.body>
        </x-core::card>

        <x-core::card>
            <x-core::card.body>
                <x-core::button type="submit" color="primary">Save</x-core::button>
            </x-core::card.body>
        </x-core::card>
    </div>
</div>
