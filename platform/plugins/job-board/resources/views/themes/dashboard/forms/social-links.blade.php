<div class="row">
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/job-board::dashboard.facebook_url')"
            type="url"
            name="facebook"
            :value="$company->facebook"
            placeholder="https://facebook.com/company-name"
        />
    </div>
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/job-board::dashboard.twitter_url')"
            type="url"
            name="twitter"
            :value="$company->twitter"
            placeholder="https://x.com/company-name"
        />
    </div>
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/job-board::dashboard.linkedin_url')"
            type="url"
            name="linkedin"
            :value="$company->linkedin"
            placeholder="https://linkedin.com/company/company-name"
        />
    </div>
    <div class="col-md-6">
        <x-core::form.text-input
            :label="trans('plugins/job-board::dashboard.instagram_url')"
            type="url"
            name="instagram"
            :value="$company->instagram"
            placeholder="https://instagram.com/company-name"
        />
    </div>
</div>
