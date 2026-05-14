{{ header }}

<table width="100%">
    <tbody>
        <tr>
            <td class="wrapper" width="700" align="center">
                <table class="section" cellpadding="0" cellspacing="0" width="700" bgcolor="#f8f8f8">
                    <tr>
                        <td class="column" align="left">
                            <table>
                                <tbody>
                                <tr>
                                    <td align="left" style="padding: 20px 50px;">
                                        <p><strong>{{ 'plugins/job-board::email.email_templates.admin_job_application_greeting' | trans({'site_title': site_title}) }}</strong></p>

                                        {% if job_application_name %}
                                        <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/person.png"
                                                alt="From" width="20" style="margin-right: 10px;" /> {{ 'plugins/job-board::email.email_templates.admin_job_application_name' | trans({'job_application_name': job_application_name}) }}</p>
                                        {% endif %}

                                        {% if job_application_position %}
                                        <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/edit.png"
                                                alt="Subject" width="20" style="margin-right: 10px;" />{{ 'plugins/job-board::email.email_templates.admin_job_application_position' | trans({'job_application_position': job_application_position}) }}</p>

                                        {% endif %}

                                        {% if job_application_email %}
                                            <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/email.png"
                                                    alt="Email" width="20" style="margin-right: 10px;" /> {{ 'plugins/job-board::email.email_templates.admin_job_application_email' | trans({'job_application_email': job_application_email}) }}</p>
                                        {% endif %}

                                        {% if job_application_phone %}
                                            <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/edit.png"
                                                    alt="Phone" width="20" style="margin-right: 10px;" />{{ 'plugins/job-board::email.email_templates.admin_job_application_phone' | trans({'job_application_phone': job_application_phone}) }}</p>
                                        {% endif %}

                                        {% if job_application_summary %}
                                            <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/edit.png"
                                                    alt="Summary" width="20" style="margin-right: 10px;" />{{ 'plugins/job-board::email.email_templates.admin_job_application_summary' | trans({'job_application_summary': job_application_summary}) }}</p>
                                        {% endif %}

                                        {% if job_application_resume %}
                                            <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/edit.png"
                                                    alt="Resume" width="20" style="margin-right: 10px;" />{{ 'plugins/job-board::email.email_templates.admin_job_application_resume' | trans({'job_application_resume': job_application_resume}) }}</p>
                                        {% endif %}

                                        {% if job_application_cover_letter %}
                                            <p><img src="{{ site_url }}/vendor/core/core/base/images/emails/edit.png"
                                                    alt="Cover Letter" width="20" style="margin-right: 10px;" />{{ 'plugins/job-board::email.email_templates.admin_job_application_cover_letter' | trans({'job_application_cover_letter': job_application_cover_letter}) }}</p>
                                        {% endif %}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
{{ footer }}
