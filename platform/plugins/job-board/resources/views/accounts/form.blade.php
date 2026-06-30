@extends('core/base::forms.form')

@section('form_end')
    @if ($form->getModel()->id)
        <x-core::modal
            id="add-credit-modal"
            :title="trans('plugins/job-board::account.add_credit_to_account')"
            :button-label="trans('plugins/job-board::account.action_table.add')"
            button-id="confirm-add-credit-button"
        >
            @include('plugins/job-board::accounts.credit-form', ['account' => $form->getModel()])
        </x-core::modal>

        <x-core::modal
            id="edit-account-entity-modal"
            :title="trans('plugins/job-board::account.edit_education')"
        >
            <x-core::loading />
            <x-slot:footer>
                <x-core::button
                    data-bs-dismiss="modal"
                >
                    {{ trans('core/base::base.close') }}
                </x-core::button>

                <x-core::button
                    class="ms-auto"
                    color="primary"
                    data-bb-toggle="confirm-edit-entity-button"
                >
                    {{ trans('plugins/job-board::account.action_table.edit') }}
                </x-core::button>
            </x-slot:footer>
        </x-core::modal>

        <div class="modal fade" id="accountResumePreviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content" style="height:85vh;">
                    <div class="modal-header">
                        <h5 class="modal-title">Candidate CV Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe id="accountResumePreviewFrame" src="" style="width:100%;height:100%;border:0;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {!! Form::modalAction(
        'add-education-modal',
        trans('plugins/job-board::account.add_education'),
        'info',
        FormBuilder::create(\Botble\JobBoard\Forms\AccountEducationForm::class, [
            'data' => [
                'account' => $form->getModel(),
            ],
        ])->renderForm(),
        'confirm-add-entity-button',
        trans('plugins/job-board::account.action_table.add'),
        'modal-md',
    ) !!}

    {!! Form::modalAction(
        'add-experience-modal',
        trans('plugins/job-board::account.add_experience'),
        'info',
        FormBuilder::create(\Botble\JobBoard\Forms\AccountExperienceForm::class, [
            'data' => [
                'account' => $form->getModel(),
            ],
        ])->renderForm(),
        'confirm-add-entity-button',
        trans('plugins/job-board::account.action_table.add'),
        'modal-md',
    ) !!}

    {!! Form::modalAction(
        'add-language-modal',
        trans('plugins/job-board::account.add_language'),
        'info',
        FormBuilder::create(\Botble\JobBoard\Forms\AccountLanguageForm::class)
            ->add(
                'account_id',
                'hidden',
                ['value' => $form->getModel()->id]
            )
            ->renderForm(),
        'confirm-add-entity-button',
        trans('plugins/job-board::account.action_table.add'),
        'modal-md',
    ) !!}

    <x-core::modal.action
        type="danger"
        id="modal-confirm-delete"
        :title="trans('core/base::tables.confirm_delete')"
        :description="trans('core/base::tables.confirm_delete_msg')"
        :submit-button-label="trans('core/base::tables.delete')"
        :submit-button-attrs="['data-bb-toggle' => 'confirm-delete']"
    />
@stop
