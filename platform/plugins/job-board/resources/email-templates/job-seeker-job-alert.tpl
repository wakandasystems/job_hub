{{ header }}

<p>{{ 'plugins/job-board::email.email_templates.job_alert_greeting' | trans({'account_name': account_name}) }}</p>

<p><strong>{{ 'plugins/job-board::email.email_templates.job_alert_source_label' | trans }}</strong> {{ job_alert_source_message }}</p>

<p style="font-size: 18px;">{{ 'plugins/job-board::email.email_templates.job_alert_hiring' | trans({'job_name': '<strong>' ~ job_name ~ '</strong>', 'company_name': '<i>' ~ company_name ~ '</i>'}) | raw }}</p>

{% if job_location %}
<p>📍 {{ job_location }}{% if job_country %}, {{ job_country }}{% endif %}</p>
{% elseif job_country %}
<p>📍 {{ job_country }}</p>
{% endif %}

{% if job_deadline %}
<p>⏳ {{ 'plugins/job-board::email.email_templates.job_alert_deadline' | trans({'job_deadline': '<strong>' ~ job_deadline ~ '</strong>'}) | raw }}</p>
{% endif %}

{% if job_description %}
<p style="margin-top: 12px; padding: 14px 16px; background: #f8fafc; border-left: 4px solid #6366f1; border-radius: 4px; color: #374151; font-size: 14px; line-height: 1.6;">{{ job_description }}</p>
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
