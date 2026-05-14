{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.job_expired_soon_greeting' | trans({'job_author': job_author}) }}</p>
<p>{{ 'plugins/job-board::email.email_templates.job_expired_soon_message' | trans({'job_url': job_url, 'job_name': job_name, 'job_expired_after': job_expired_after}) | raw }}</p>
<p>{{ 'plugins/job-board::email.email_templates.job_expired_soon_renew' | trans({'job_list': job_list}) | raw }}</p>

{{ footer }}
