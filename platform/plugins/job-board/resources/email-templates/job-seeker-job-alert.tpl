{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.job_alert_greeting' | trans({'account_name': account_name}) }}</p>

<p><strong>{{ 'plugins/job-board::email.email_templates.job_alert_source_label' | trans }}</strong> {{ job_alert_source_message }}</p>

<p>{{ 'plugins/job-board::email.email_templates.job_alert_hiring' | trans({'job_name': '<strong>' ~ job_name ~ '</strong>', 'company_name': '<i>' ~ company_name ~ '</i>'}) | raw }}</p>

{% if job_location %}
<p>📍 {{ job_location }}</p>
{% endif %}

{% if job_deadline %}
<p>⏳ {{ 'plugins/job-board::email.email_templates.job_alert_deadline' | trans({'job_deadline': '<strong>' ~ job_deadline ~ '</strong>'}) | raw }}</p>
{% endif %}

{{ 'plugins/job-board::email.email_templates.job_alert_apply_forward' | trans({'job_url': job_url}) | replace({'Apply': '<strong>Apply</strong>', 'Forward': '<strong>Forward</strong>'}) | raw }}

{% if job_alert_quota_message %}
    <p style="margin-top: 18px; padding: 12px 14px; background: #fff7e6; border-left: 4px solid #f59e0b;">
        {{ job_alert_quota_message }}
        <br>
        <a href="{{ job_alert_packages_url }}">{{ 'plugins/job-board::email.email_templates.job_alert_buy_more' | trans }}</a>
    </p>
{% endif %}

{{ footer }}
