<?php

namespace Botble\Location\Fields;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\Html;
use Botble\Base\Forms\Form;
use Botble\Base\Forms\FormField;
use Botble\Location\Models\City;
use Botble\Location\Models\Country;
use Botble\Location\Models\State;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class SelectLocationField extends FormField
{
    protected array $locationKeys = [];

    public function __construct($name, $type, Form $parent, array $options = [])
    {
        parent::__construct($name, $type, $parent);

        $default = [
            'country' => 'country_id',
            'state' => 'state_id',
            'city' => 'city_id',
        ];

        $this->locationKeys = array_filter(array_merge($default, Arr::get($options, 'locationKeys', [])));

        $this->name = $name;
        $this->type = $type;
        $this->parent = $parent;
        $this->formHelper = $this->parent->getFormHelper();

        $this->setTemplate();
        $this->setDefaultOptions($options);
        $this->setupValue();
        $this->initFilters();

        Assets::addScriptsDirectly('vendor/core/plugins/location/js/location.js');
    }

    protected function getConfig($key = null, $default = null)
    {
        return $this->parent->getConfig($key, $default);
    }

    protected function setTemplate(): void
    {
        $this->template = $this->getConfig($this->getTemplate(), $this->getTemplate());
    }

    protected function setupValue(): void
    {
        $values = $this->getOption($this->valueProperty);
        foreach ($this->locationKeys as $k => $v) {
            $value = Arr::get($values, $k);
            if ($value === null) {
                $value = old($v, $this->getModelValueAttribute($this->parent->getModel(), $v));
            }

            $values[$k] = $value;
        }

        // On the frontend, fall back to the nav country switcher only when no country is saved
        if (function_exists('wakanda_selected_country') && is_array($values) && array_key_exists('country', $values) && ! $values['country']) {
            $navCountry = wakanda_selected_country();
            if ($navCountry) {
                $values['country'] = $navCountry->id;
            }
        }

        $this->setValue($values);
    }

    public function getCountryOptions(): array
    {
        $countryKey = Arr::get($this->locationKeys, 'country');
        $countries = Country::query()
            ->select('name', 'id', 'is_default')
            ->latest('is_default')
            ->oldest('order')
            ->oldest('name')
            ->latest()
            ->get();

        $value = Arr::get($this->getValue(), 'country');

        if (! $value && $countries->isNotEmpty()) {
            $firstCountry = $countries->first();
            if ($firstCountry->is_default) {
                $value = $firstCountry->getKey();
            }
        }

        $countries = $countries
            ->mapWithKeys(fn ($item) => [$item->getKey() => $item->name])
            ->all();

        $attr = array_merge($this->getOption('attr', []), [
            'id' => $countryKey,
            'class' => 'select-search-full',
            'data-type' => 'country',
        ]);

        return array_merge([
            'label' => trans('plugins/location::city.country'),
            'attr' => $attr,
            'choices' => ['' => trans('plugins/location::city.select_country')] + $countries,
            'selected' => $value,
            'empty_value' => null,
        ], $this->getOption('attrs.country', []));
    }

    public function getStateOptions(): array
    {
        $stateKey = Arr::get($this->locationKeys, 'state');
        $countryId = Arr::get($this->getValue(), 'country');
        $value = Arr::get($this->getValue(), 'state');

        // Only include the currently selected state; the rest load via AJAX
        $states = [];
        if ($value && (int) $value !== 0) {
            $state = State::query()->select('id', 'name')->find((int) $value);
            if ($state) {
                $states[$state->id] = $state->name;
            }
        }

        $attr = array_merge($this->getOption('attr', []), [
            'id' => $stateKey,
            'data-url' => route('ajax.states-by-country'),
            'data-ajax-search-url' => route('ajax.search-states'),
            'data-country-id' => (string) ($countryId ?? ''),
            'class' => 'select-search-full',
            'data-type' => 'state',
        ]);

        return array_merge([
            'label' => trans('plugins/location::city.state'),
            'attr' => $attr,
            'choices' => ['' => trans('plugins/location::city.select_state')] + $states,
            'selected' => $value,
            'empty_value' => null,
        ], $this->getOption('attrs.state', []));
    }

    public function getCityOptions(): array
    {
        $cityKey = Arr::get($this->locationKeys, 'city');
        $stateId = Arr::get($this->getValue(), 'state');
        $countryId = Arr::get($this->getValue(), 'country');
        $value = Arr::get($this->getValue(), 'city');

        // Only include the currently selected city; the rest load via AJAX
        $cities = [];
        if ($value && (int) $value !== 0) {
            $city = City::query()->select('id', 'name')->find((int) $value);
            if ($city) {
                $cities[$city->id] = $city->name;
            }
        }

        $attr = array_merge($this->getOption('attr', []), [
            'id' => $cityKey,
            'data-url' => route('ajax.cities-by-state'),
            'data-ajax-search-url' => route('ajax.search-cities'),
            'data-country-id' => (string) ($countryId ?? ''),
            'data-state-id' => (string) ($stateId ?? ''),
            'class' => 'select-search-full',
            'data-type' => 'city',
        ]);

        return array_merge([
            'label' => trans('plugins/location::city.city'),
            'attr' => $attr,
            'choices' => ['' => trans('plugins/location::city.select_city')] + $cities,
            'selected' => $value,
            'empty_value' => null,
        ], $this->getOption('attrs.city', []));
    }

    public function render(
        array $options = [],
        $showLabel = true,
        $showField = true,
        $showError = true
    ): HtmlString|string {
        $html = '';

        $this->prepareOptions($options);

        if ($showField) {
            $this->rendered = true;
        }

        if (! $this->needsLabel()) {
            $showLabel = false;
        }

        if ($showError) {
            $showError = $this->parent->haveErrorsEnabled();
        }

        $data = $this->getRenderData();

        foreach ($this->locationKeys as $k => $v) {
            $options = [];
            switch ($k) {
                case 'country':
                    $options = $this->getCountryOptions();

                    break;
                case 'state':
                    $options = $this->getStateOptions();

                    break;
                case 'city':
                    $options = $this->getCityOptions();

                    break;
            }

            $options = array_merge($this->options, $options);

            $html .= $this->formHelper->getView()->make(
                $this->getViewTemplate(),
                $data + [
                    'name' => $v,
                    'nameKey' => $v,
                    'type' => $this->type,
                    'options' => $options,
                    'showLabel' => $showLabel,
                    'showField' => $showField,
                    'showError' => $showError,
                    'errorBag' => $this->parent->getErrorBag(),
                    'translationTemplate' => $this->parent->getTranslationTemplate(),
                ]
            )->render();

            if (request()->ajax()) {
                $html .= Html::script('vendor/core/plugins/location/js/location.js');
            }
        }

        return Html::tag(
            'div',
            $html,
            ['class' => ($this->getOption('wrapperClassName') ?: 'mb-3 row') . ' select-location-fields']
        );
    }

    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.custom-select';
    }
}
