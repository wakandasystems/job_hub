<table cellspacing="0" cellpadding="0">
    <tbody>
        <tr>
            <td class="bb-py-xl">
                <table class="bb-text-center bb-text-muted" cellspacing="0" cellpadding="0">
                    <tbody>
                    {% if social_links %}
                        <tr>
                            <td align="center" class="bb-pb-md">
                                <table class="bb-w-auto" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr>
                                            {% for social_link in site_social_links %}
                                                <td class="bb-px-sm">
                                                    <a title="{{ social_link.name }}" href="{{ social_link.url }}">
                                                        <img src="{{ social_link.image }}" class="bb-va-middle" width="24" height="24" alt="{{ social_link.name }}" />
                                                    </a>
                                                </td>
                                            {% endfor %}
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    {% endif %}

                    <tr>
                        <td class="bb-pb-md bb-pt-sm" align="center">
                            <a href="https://whatsapp.com/channel/0029Vb7umsx2ZjClLN546U3f" target="_blank" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#25D366;font-weight:600;font-size:14px;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="20" height="20" alt="WhatsApp" style="vertical-align:middle;" />
                                Follow our WhatsApp Channel for job updates
                            </a>
                        </td>
                    </tr>

                    {% if telegram_channel_url %}
                    <tr>
                        <td class="bb-pb-md" align="center">
                            <a href="{{ telegram_channel_url }}" target="_blank" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#229ED9;font-weight:600;font-size:14px;">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/8/83/Telegram_2019_Logo.svg/512px-Telegram_2019_Logo.svg.png" width="20" height="20" alt="Telegram" style="vertical-align:middle;" />
                                {{ telegram_channel_label }}
                            </a>
                        </td>
                    </tr>
                    {% endif %}

                    <tr>
                        <td class="bb-px-lg">
                            {{ site_copyright }}
                        </td>
                    </tr>

                    {% if site_email %}
                        <tr>
                            <td class="bb-pt-md">
                                {{ 'core/base::base.email_template.footer_contact_message' | trans({'site_email': site_email}) }}
                            </td>
                        </tr>
                    {% endif %}
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
</td>
</tr>
</tbody>
</table>
</td>
</tr>
</tbody>
</table>
</center>
</body>

</html>

