{{ header }}

<p>Hi {{ subscriber_name }},</p>

<p>Great news! A new job has just been posted on <strong>Wakanda Jobs</strong> that you might be interested in:</p>

<p style="font-size: 18px;"><strong>{{ job_name }}</strong>{% if company_name %} at <i>{{ company_name }}</i>{% endif %}</p>

{% if job_location %}
<p>📍 {{ job_location }}</p>
{% endif %}

{% if job_deadline %}
<p>⏳ Apply before <strong>{{ job_deadline }}</strong></p>
{% endif %}

<p style="margin-top: 16px;">
    <a href="{{ job_url }}" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">View Job & Apply</a>
</p>

<hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">

<p style="padding: 16px; background: #f0fdf4; border-left: 4px solid #22c55e; border-radius: 4px;">
    <strong>Want more benefits?</strong><br>
    Create a free account on Wakanda Jobs to unlock personalised job alerts, save favourite jobs, track your applications, and much more!
    <br><br>
    <a href="{{ sign_up_url }}" style="display: inline-block; padding: 10px 20px; background-color: #22c55e; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">Sign Up Now</a>
</p>

<p style="margin-top: 20px; font-size: 12px; color: #6b7280;">You are receiving this email because you subscribed to the Wakanda Jobs newsletter.</p>

{{ footer }}
