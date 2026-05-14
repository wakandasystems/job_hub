{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.payment_received_admin_greeting' | trans }}</p>
<p>{{ 'plugins/job-board::email.email_templates.payment_received_message' | trans({'account_name': account_name}) }}</p>
<p>{{ 'plugins/job-board::email.email_templates.payment_received_account' | trans({'account_name': account_name, 'account_email': account_email}) }}</p>
<p>{{ 'plugins/job-board::email.email_templates.payment_received_package' | trans({'package_name': '<strong>' ~ package_name ~ '</strong>'}) | raw }}</p>
<p>{{ 'plugins/job-board::email.email_templates.payment_received_price' | trans({'package_price_per_credit': '<strong>' ~ (package_price_per_credit | price_format) ~ '</strong>'}) | raw }}</p>
<p>{{ 'plugins/job-board::email.email_templates.payment_received_total' | trans({'package_price': '<strong>' ~ (package_price | price_format) ~ '</strong>', 'package_number_of_listings': package_number_of_listings}) | raw }} {% if package_percent_discount > 0 %} {{ 'plugins/job-board::email.email_templates.payment_received_save' | trans({'package_percent_discount': package_percent_discount}) }} {% endif %}</p>

{{ footer }}
