{{ header }}

<strong>{{ 'plugins/job-board::email.email_templates.password_reminder_greeting' | trans }}</strong> <br /><br />

{{ 'plugins/job-board::email.email_templates.password_reminder_message' | trans }} <br /><br />

<a href="{{ reset_link }}">{{ 'plugins/job-board::email.email_templates.password_reminder_button' | trans }}</a> <br /><br />

{{ 'plugins/job-board::email.email_templates.password_reminder_no_action' | trans }} <br /><br />

{{ 'plugins/job-board::email.email_templates.password_reminder_regards' | trans }} <br />

<strong>{{ site_title }}</strong>

<hr />

{{ 'plugins/job-board::email.email_templates.password_reminder_trouble' | trans({'reset_link': reset_link}) }}

{{ footer }}
