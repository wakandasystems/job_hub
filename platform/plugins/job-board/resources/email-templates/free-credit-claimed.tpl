{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.free_credit_claimed_admin_greeting' | trans }}</p>
<p>{{ 'plugins/job-board::email.email_templates.free_credit_claimed_message' | trans({'account_name': account_name ~ ' (' ~ account_email ~ ')', 'site_title': site_title}) }}</p>

{{ footer }}
