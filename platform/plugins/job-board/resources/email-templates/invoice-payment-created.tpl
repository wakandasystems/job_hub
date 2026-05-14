{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.invoice_payment_greeting' | trans({'account_name': account_name}) }}</p>
<p>{{ 'plugins/job-board::email.email_templates.invoice_payment_from' | trans({'site_title': '<strong>' ~ site_title ~ '</strong>'}) | raw }}</p>
<p>{{ 'plugins/job-board::email.email_templates.invoice_payment_attached' | trans({'invoice_code': '<a href="' ~ invoice_link ~ '">' ~ invoice_code ~ '</a>'}) | raw }}</p>
<a href="{{ invoice_link }}">{{ 'plugins/job-board::email.email_templates.invoice_payment_view_online' | trans }}</a>

{{ footer }}
