{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.job_alert_greeting' | trans({'account_name': account_name}) }}</p>

<p>{{ 'plugins/job-board::email.email_templates.job_alert_hiring' | trans({'job_name': '<strong>' ~ job_name ~ '</strong>', 'company_name': '<i>' ~ company_name ~ '</i>'}) | raw }}</p>

{{ 'plugins/job-board::email.email_templates.job_alert_apply_forward' | trans({'job_url': job_url}) | replace({'Apply': '<strong>Apply</strong>', 'Forward': '<strong>Forward</strong>'}) | raw }}

{{ footer }}
