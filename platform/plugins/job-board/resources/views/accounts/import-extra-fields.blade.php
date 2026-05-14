<x-core::form-group>
    <x-core::form.checkbox
        name="update_existing_accounts"
        :label="trans('plugins/job-board::import.update_existing_accounts')"
        :helper-text="trans('plugins/job-board::import.update_existing_accounts_description')"
        value="1"
    />
</x-core::form-group>
