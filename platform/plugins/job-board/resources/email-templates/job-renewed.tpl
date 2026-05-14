{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.job_renewed_greeting' | trans({'job_author': job_author}) }}</p>
<p>{{ 'plugins/job-board::email.email_templates.job_renewed_message' | trans({'job_url': job_url, 'job_name': job_name}) | raw }}</p>

{{ footer }}
