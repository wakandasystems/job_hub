{{ header }}

<div class="bb-main-content">
    <table class="bb-box" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
            <td class="bb-content bb-pb-0" align="center">
                <table class="bb-icon bb-icon-lg bb-bg-blue" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                        <td valign="middle" align="center">
                            <img src="{{ 'check' | icon_url }}" class="bb-va-middle" width="40" height="40" alt="Icon">
                        </td>
                    </tr>
                    </tbody>
                </table>

                <h1 class="bb-text-center bb-m-0 bb-mt-md">{{ 'plugins/job-board::email.email_templates.job_approved_title' | trans }}</h1>
            </td>
        </tr>
        <tr>
            <td class="bb-content bb-pb-0">
                <p>{{ 'plugins/job-board::email.email_templates.job_approved_greeting' | trans({'job_author': job_author}) }}</p>
                <p>{{ 'plugins/job-board::email.email_templates.job_approved_message' | trans }}</p>
            </td>
        </tr>

        <tr>
            <td class="bb-content bb-pt-0">
                <table class="bb-row bb-mb-md" cellpadding="0" cellspacing="0">
                    <tbody>
                    <tr>
                        <td class="bb-bb-col">
                            <h4>{{ 'plugins/job-board::email.email_templates.job_approved_info' | trans }}</h4>
                            <div>{{ 'plugins/job-board::email.email_templates.job_approved_job_title' | trans({'job_name': job_name}) | raw }}</div>
                            <div>{{ 'plugins/job-board::email.email_templates.job_approved_view' | trans }}: <a href="{{ job_url }}">{{ 'plugins/job-board::email.email_templates.job_approved_here' | trans }}</a></div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
</div>

{{ footer }}
