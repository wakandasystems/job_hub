<x-core::form-group>
    <x-core::form.checkbox
        name="update_existing_jobs"
        :label="trans('plugins/job-board::import.update_existing_jobs')"
        :helper-text="trans('plugins/job-board::import.update_existing_jobs_description')"
        value="1"
    />
</x-core::form-group>
