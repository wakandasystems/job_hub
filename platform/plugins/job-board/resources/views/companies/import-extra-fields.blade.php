<x-core::form-group>
    <x-core::form.checkbox
        name="update_existing_companies"
        :label="trans('plugins/job-board::import.update_existing_companies')"
        :helper-text="trans('plugins/job-board::import.update_existing_companies_description')"
        value="1"
    />
</x-core::form-group>
