{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.account_registered_admin_greeting' | trans }}</p>
<p>{{ 'plugins/job-board::email.email_templates.account_registered_new_account' | trans({'account_type': account_type}) }}</p>
<p>{{ 'plugins/job-board::email.email_templates.account_registered_name' | trans({'account_name': account_name}) | raw }}</p>
<p>{{ 'plugins/job-board::email.email_templates.account_registered_email' | trans({'account_email': account_email}) | raw }}</p>

{{ footer }}
