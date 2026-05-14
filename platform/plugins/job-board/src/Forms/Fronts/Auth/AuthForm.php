<?php

namespace Botble\JobBoard\Forms\Fronts\Auth;

use Botble\JobBoard\Forms\Fronts\Auth\Concerns\HasSubmitButton;
use Botble\Theme\Facades\Theme;
use Botble\Theme\FormFront;

abstract class AuthForm extends FormFront
{
    use HasSubmitButton;

    public function setup(): void
    {
        Theme::asset()->add('auth-css', 'vendor/core/plugins/job-board/css/front-auth.css', version: '1.2.0');

        $this->contentOnly()
            ->setFormOption('class', 'auth-form');
    }
}
