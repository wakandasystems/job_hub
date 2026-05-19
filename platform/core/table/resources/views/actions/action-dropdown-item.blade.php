@php
    /** @var Botble\Table\Actions\Action $action */
    $existingClass = $action->getAttribute('class');
    if (!$existingClass || is_array($existingClass)) {
        $action->addAttribute('class', trim('dropdown-item ' . str_replace('btn-', 'text-', $action->getColor())));
    }
@endphp

<li>
    <a
        @include('core/table::actions.includes.action-attributes')
    >
        @include('core/table::actions.includes.action-icon')

        <span class="ms-1">{{ $action->getLabel() }}</span>
    </a>
</li>
