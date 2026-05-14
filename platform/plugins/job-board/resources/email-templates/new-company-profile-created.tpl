{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.new_company_profile_admin_greeting' | trans }}</p>
<p>{{ 'plugins/job-board::email.email_templates.new_company_profile_message' | trans({'employer_name': '<strong>' ~ employer_name ~ '</strong>', 'company_name': '<a href="' ~ company_url ~ '">' ~ company_name ~ '</a>'}) | raw }}</p>

{{ footer }}
