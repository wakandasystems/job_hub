{{ header }}

<strong>{{ 'plugins/job-board::email.email_templates.confirm_email_greeting' | trans }}</strong> <br /><br />

{{ 'plugins/job-board::email.email_templates.confirm_email_message' | trans }} <br /><br />

<a href="{{ verify_link }}">{{ 'plugins/job-board::email.email_templates.confirm_email_button' | trans }}</a> <br /><br />

{{ 'plugins/job-board::email.email_templates.confirm_email_regards' | trans }} <br />

<strong>{{ site_title }}</strong>

<hr />

{{ 'plugins/job-board::email.email_templates.confirm_email_trouble' | trans({'verify_link': verify_link}) }}

{{ footer }}
