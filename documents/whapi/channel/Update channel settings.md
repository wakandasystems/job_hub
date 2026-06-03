Update channel settings

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Update channel settings

If a field is not present in the request, no change is made to that setting. For example, if 'proxy' is not sent with the request, the existing configuration for 'proxy' is unchanged.

# OpenAPI definition

```json
{
  "openapi": "3.0.0",
  "info": {
    "contact": {
      "name": "Whapi Support",
      "email": "care@whapi.cloud",
      "url": "https://whapi.cloud/support"
    },
    "description": "Sending and receiving messages using HTTP requests. Fixed price with no hidden fees, without limits and restrictions. You will be able to send and receive text/media/files/locations/goods/orders/polls messages via WhatsApp in private or group chats. Guides and SDK can be found on our website.",
    "title": "WhatsApp API",
    "version": "1.8.7",
    "termsOfService": "https://whapi.cloud/terms"
  },
  "servers": [
    {
      "url": "https://gate.whapi.cloud"
    },
    {
      "url": "http://localhost:8000"
    }
  ],
  "security": [
    {
      "bearerAuth": []
    },
    {
      "tokenAuth": []
    }
  ],
  "tags": [
    {
      "name": "Channel",
      "description": "The channel is the main entity of the API. It is the entity that represents the user's WhatsApp session"
    }
  ],
  "paths": {
    "/settings": {
      "patch": {
        "tags": [
          "Channel"
        ],
        "requestBody": {
          "$ref": "#/components/requestBodies/UpdateSettings"
        },
        "responses": {
          "200": {
            "$ref": "#/components/responses/UpdateSettings"
          },
          "400": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "Wrong settings format"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "updateChannelSettings",
        "summary": "Update channel settings",
        "description": "If a field is not present in the request, no change is made to that setting. For example, if 'proxy' is not sent with the request, the existing configuration for 'proxy' is unchanged.",
        "callbacks": {
          "incomingWebhook": {
            "{$request.body#/webhooks[0].url}": {
              "post": {
                "requestBody": {
                  "content": {
                    "application/json": {
                      "schema": {
                        "$ref": "#/components/schemas/WebhookPayload"
                      }
                    }
                  }
                },
                "responses": {
                  "200": {
                    "description": "OK"
                  }
                }
              }
            }
          }
        }
      }
    }
  },
  "components": {
    "requestBodies": {
      "UpdateSettings": {
        "description": "New settings",
        "required": false,
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/Settings"
            },
            "example": {
              "callback_backoff_delay_ms": 3000,
              "max_callback_backoff_delay_ms": 900000,
              "callback_persist": true,
              "media": {
                "auto_download": [
                  "image",
                  "document",
                  "audio"
                ],
                "init_avatars": true
              },
              "webhooks": [
                {
                  "url": "<Webhook URL, http or https>",
                  "events": [
                    {
                      "type": "ack",
                      "method": "put"
                    },
                    {
                      "type": "chat",
                      "method": "put"
                    }
                  ],
                  "mode": "method"
                }
              ],
              "on_call_pager": "<WA_ID of valid WhatsApp contact>",
              "pass_through": false,
              "sent_status": false,
              "proxy": "socks5://login:password@167.160.89.124:10030"
            }
          }
        }
      }
    },
    "responses": {
      "Error": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/ResponseError"
            }
          }
        },
        "description": "Internal Error"
      },
      "UpdateSettings": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/UpdateSettings"
            }
          }
        },
        "description": "OK"
      }
    },
    "schemas": {
      "ResponseError": {
        "title": "ResponseError",
        "type": "object",
        "properties": {
          "error": {
            "$ref": "#/components/schemas/Error"
          }
        },
        "required": [
          "error"
        ]
      },
      "WebhookPayload": {
        "title": "Webhook payload",
        "description": "",
        "type": "object",
        "properties": {
          "contacts": {
            "type": "array",
            "description": "The contacts array contains all contacts that were sent to the webhook URL. Sent on event \"contacts.post\"",
            "items": {
              "$ref": "#/components/schemas/Contact"
            }
          },
          "messages": {
            "type": "array",
            "description": "The messages array contains all messages that were sent to the webhook URL. Sent on event \"messages.post\" or \"messages.put\"",
            "items": {
              "$ref": "#/components/schemas/Message"
            }
          },
          "messages_updates": {
            "type": "array",
            "description": "The messages updates array contains all messages updates that were sent to the webhook URL. Sent on event \"messages.patch\"",
            "items": {
              "$ref": "#/components/schemas/MessageUpdate"
            }
          },
          "messages_removed": {
            "type": "array",
            "description": "The messages removed array contains all messages removed that were sent to the webhook URL. Sent on event \"messages.delete\"",
            "items": {
              "$ref": "#/components/schemas/MessageID"
            }
          },
          "messages_removed_all": {
            "type": "string",
            "description": "The messages removed all contains the chat ID of the chat that was cleared. Sent on event \"messages.delete\"",
            "example": "1234567890@s.whatsapp.net"
          },
          "statuses": {
            "type": "array",
            "description": "The messages statuses array contains all statuses that were sent to the webhook URL. Sent on event \"statuses.post\" or \"statuses.put\"",
            "items": {
              "$ref": "#/components/schemas/Status"
            }
          },
          "chats": {
            "type": "array",
            "description": "The chats array contains all chats that were sent to the webhook URL. Sent on event \"chats.post\" or \"chats.put\"",
            "items": {
              "$ref": "#/components/schemas/Chat"
            }
          },
          "chats_updates": {
            "type": "array",
            "description": "The chats updates array contains all chats updates that were sent to the webhook URL. Sent on event \"chats.patch\"",
            "items": {
              "$ref": "#/components/schemas/ChatUpdate"
            }
          },
          "chats_removed": {
            "type": "array",
            "description": "The chats removed array contains all chats removed that were sent to the webhook URL. Sent on event \"chats.delete\"",
            "items": {
              "$ref": "#/components/schemas/ChatID"
            }
          },
          "contacts_updates": {
            "type": "array",
            "description": "The contacts updates array contains all contacts updates that were sent to the webhook URL. Sent on event \"contacts.patch\"",
            "items": {
              "$ref": "#/components/schemas/ContactUpdate"
            }
          },
          "groups": {
            "type": "array",
            "description": "The groups array contains all groups that were sent to the webhook URL. Sent on event \"groups.post\"",
            "items": {
              "$ref": "#/components/schemas/Group"
            }
          },
          "groups_participants": {
            "type": "array",
            "description": "The groups participants event array contains all groups participants event that were sent to the webhook URL. Sent on event \"groups.put\"",
            "items": {
              "$ref": "#/components/schemas/ParticipantEvent"
            }
          },
          "groups_updates": {
            "type": "array",
            "description": "The groups updates array contains all groups updates that were sent to the webhook URL. Sent on event \"groups.patch\"",
            "items": {
              "$ref": "#/components/schemas/GroupUpdate"
            }
          },
          "presences": {
            "type": "array",
            "description": "The presences array contains all presences that were sent to the webhook URL. Sent on event \"presences.post\"",
            "items": {
              "$ref": "#/components/schemas/Presence"
            }
          },
          "labels": {
            "type": "array",
            "description": "The labels array contains all labels that were sent to the webhook URL. Sent on event \"labels.post\"",
            "items": {
              "$ref": "#/components/schemas/Label"
            }
          },
          "labels_removed": {
            "type": "array",
            "description": "The labels removed array contains all labels removed that were sent to the webhook URL. Sent on event \"labels.delete\"",
            "items": {
              "$ref": "#/components/schemas/LabelID"
            }
          },
          "calls": {
            "type": "array",
            "description": "The calls array contains all calls that were sent to the webhook URL. Sent on event \"calls.post\"",
            "items": {
              "$ref": "#/components/schemas/CallEvent"
            }
          },
          "qr": {
            "$ref": "#/components/schemas/QR"
          },
          "health": {
            "$ref": "#/components/schemas/Health"
          },
          "user": {
            "$ref": "#/components/schemas/Contact"
          },
          "errors": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/Error"
            }
          },
          "event": {
            "$ref": "#/components/schemas/Event"
          },
          "channel_id": {
            "type": "string",
            "description": "The channel ID",
            "example": "SUPERMAN-f75"
          }
        }
      },
      "ChatID": {
        "type": "string",
        "description": "Chat ID",
        "pattern": "^[\\d-]{10,31}@[\\w\\.]{1,}$"
      },
      "ContactID": {
        "type": "string",
        "description": "Contact ID",
        "pattern": "^([\\d]{7,15})?$"
      },
      "GroupID": {
        "type": "string",
        "description": "Group ID",
        "pattern": "^[\\d-]{10,31}@g\\.us$"
      },
      "InviteCode": {
        "type": "string",
        "description": "Invite code",
        "pattern": "^[A-Za-z0-9]{14,22}$"
      },
      "LabelID": {
        "type": "string",
        "description": "Label ID",
        "pattern": "^([\\d]{1,2})?$"
      },
      "MediaID": {
        "type": "string",
        "description": "Media ID",
        "pattern": "^[a-zA-Z0-9]+-[0-9a-fA-F-]+$"
      },
      "MediaMessageType": {
        "type": "string",
        "description": "Media message type",
        "enum": [
          "image",
          "video",
          "gif",
          "audio",
          "voice",
          "document",
          "sticker"
        ]
      },
      "MessageID": {
        "type": "string",
        "description": "Message ID",
        "pattern": "^[A-Za-z0-9._]{4,30}-[A-Za-z0-9._]{4,14}(-[A-Za-z0-9._]{4,10})?(-[A-Za-z0-9._]{2,10})?$"
      },
      "NewsletterID": {
        "type": "string",
        "description": "Newsletter ID",
        "pattern": "^[\\d]{10,18}@newsletter$"
      },
      "Settings": {
        "title": "Channel Settings",
        "type": "object",
        "properties": {
          "pdo_sync": {
            "description": "It is recommended to disable it for iOS.",
            "type": "boolean",
            "default": true
          },
          "callback_backoff_delay_ms": {
            "description": "Backoff delay for a failed callback in milliseconds This setting is used to configure the amount of time the backoff delays before retrying a failed callback. The backoff delay increases linearly by this value each time a callback fails to get a HTTPS 200 OK response. The backoff delay is capped by the max_callback_backoff_delay_ms setting.",
            "type": "number",
            "minimum": 3000,
            "maximum": 15000
          },
          "max_callback_backoff_delay_ms": {
            "description": "Maximum delay for a failed callback in milliseconds",
            "type": "number",
            "minimum": 600000,
            "maximum": 3600000
          },
          "callback_persist": {
            "description": "Stores callbacks on disk until they are successfully acknowledged by the Webhook or not. Restart required.",
            "type": "boolean"
          },
          "media": {
            "$ref": "#/components/schemas/MediaSettings"
          },
          "webhooks": {
            "$ref": "#/components/schemas/Webhooks"
          },
          "proxy": {
            "description": "Use your Socks5 proxy if your account activity arouses suspicion from WhatsApp. This can help maintain anonymity and ensure smooth operation.",
            "type": "string",
            "pattern": "^socks5h?:\\/\\/(\\S+):(\\S+)@(((?:[0-9]{1,3}\\.){3}[0-9]{1,3})|[a-zA-Z0-9.-]+):([0-9]{1,5})$|^$"
          },
          "mobile_proxy": {
            "description": "Service proxy for mobile authorization. Beta-only parameter; ignored in the production version.",
            "type": "string"
          },
          "offline_mode": {
            "description": "When true, API will not send online status to the server on connection. This will allow you to receive push notifications to devices connected to the number. Working after reconnect.",
            "default": false,
            "type": "boolean"
          },
          "full_history": {
            "description": "When true, all messages will be cached after the connection. If false, old messages will selectively not be cached, allowing large accounts to run faster. Working after reconnect.",
            "default": false,
            "type": "boolean"
          },
          "ignored_presences": {
            "description": "List of presences to ignore.",
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/ChatID"
            }
          }
        }
      },
      "Chat": {
        "title": "Chat",
        "type": "object",
        "required": [
          "id",
          "type"
        ],
        "properties": {
          "id": {
            "$ref": "#/components/schemas/ChatID"
          },
          "name": {
            "type": "string",
            "description": "Chat name",
            "example": "Chat name"
          },
          "type": {
            "type": "string",
            "description": "Chat type",
            "example": "group",
            "enum": [
              "group",
              "contact",
              "broadcast",
              "newsletter",
              "unknown"
            ]
          },
          "timestamp": {
            "type": "integer",
            "description": "Chat timestamp",
            "example": 1675964377
          },
          "chat_pic": {
            "type": "string",
            "description": "Chat picture URL",
            "example": "https://example.com/photo.jpg"
          },
          "chat_pic_full": {
            "type": "string",
            "description": "Chat full picture URL",
            "example": "https://example.com/photo.jpg"
          },
          "pin": {
            "type": "boolean",
            "description": "Is chat pinned",
            "example": true
          },
          "mute": {
            "type": "boolean",
            "description": "Is chat muted",
            "example": true
          },
          "mute_until": {
            "type": "integer",
            "description": "Chat mute until",
            "example": 0
          },
          "archive": {
            "type": "boolean",
            "description": "Is chat archived",
            "example": true
          },
          "unread": {
            "type": "integer",
            "description": "Unread messages count",
            "example": 0
          },
          "unread_mention": {
            "type": "boolean",
            "description": "Is chat unread mention",
            "example": false
          },
          "read_only": {
            "type": "boolean",
            "description": "Is chat read only",
            "example": false
          },
          "not_spam": {
            "type": "boolean",
            "description": "Is chat not spam",
            "example": true
          },
          "last_message": {
            "$ref": "#/components/schemas/Message"
          },
          "labels": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/Label"
            },
            "description": "Labels associated with chat"
          }
        }
      },
      "Group": {
        "title": "Group",
        "type": "object",
        "required": [
          "id",
          "type",
          "name",
          "participants",
          "participants_count"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/Chat"
          },
          {
            "properties": {
              "name": {
                "type": "string",
                "description": "Group name",
                "example": "Group name"
              },
              "name_owner": {
                "type": "string",
                "description": "Group name owner",
                "example": "Group name owner"
              },
              "name_at": {
                "type": "integer",
                "description": "Group name change timestamp",
                "example": 1675964377
              },
              "description": {
                "type": "string",
                "description": "Group description",
                "example": "Group description"
              },
              "description_owner": {
                "type": "string",
                "description": "Group description owner",
                "example": "Group description owner"
              },
              "description_id": {
                "type": "string",
                "description": "Group description ID",
                "example": "Group description ID"
              },
              "participants_count": {
                "type": "integer",
                "description": "Number of participants in the group",
                "example": 2
              },
              "participants": {
                "type": "array",
                "description": "Group participants",
                "items": {
                  "$ref": "#/components/schemas/Participant"
                }
              },
              "created_at": {
                "type": "integer",
                "description": "Group creation timestamp",
                "example": 1675964377
              },
              "created_by": {
                "$ref": "#/components/schemas/ContactID"
              },
              "suspended": {
                "type": "boolean",
                "description": "Is group suspended",
                "example": false
              },
              "terminated": {
                "type": "boolean",
                "description": "Is group terminated",
                "example": false
              },
              "is_parent": {
                "type": "boolean",
                "description": "Is group parent",
                "example": false
              },
              "is_default_subgroup": {
                "type": "boolean",
                "description": "Is group default subgroup",
                "example": false
              },
              "restricted": {
                "type": "boolean",
                "description": "If only admins can change group settings",
                "example": false
              },
              "announcements": {
                "type": "boolean",
                "description": "If only admins can send messages",
                "example": false
              },
              "adminAddMemberMode": {
                "type": "boolean",
                "description": "If only admins can add members"
              },
              "ephemeral": {
                "type": "number",
                "description": "Group ephemeral timer",
                "example": 0
              },
              "performed_by": {
                "$ref": "#/components/schemas/ChatID"
              },
              "invite_code": {
                "type": "string",
                "description": "Group invite code",
                "example": "Group invite code"
              },
              "isCommunityAnnounce": {
                "type": "boolean",
                "description": "If group is community announce",
                "example": false
              },
              "linkedParent": {
                "$ref": "#/components/schemas/ChatID"
              }
            }
          }
        ]
      },
      "Contact": {
        "title": "WA Contact",
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "description": "WA ID"
          },
          "name": {
            "type": "string",
            "description": "Contact title from contact book"
          },
          "pushname": {
            "type": "string",
            "description": "Account name from WA or WA Business name"
          },
          "is_business": {
            "type": "boolean",
            "description": "Is a business account"
          },
          "profile_pic": {
            "type": "string",
            "description": "Profile picture URL"
          },
          "profile_pic_full": {
            "type": "string",
            "description": "Profile full picture URL"
          },
          "status": {
            "type": "string",
            "description": "Contact status"
          },
          "saved": {
            "type": "boolean",
            "description": "If true - the contact is saved in the contact list"
          }
        },
        "required": [
          "id",
          "name"
        ]
      },
      "Health": {
        "title": "Health response",
        "type": "object",
        "description": "The health object contains information about the uptime of the channel and the status of the channel. Sent on event \"channels.post\"",
        "properties": {
          "channel_id": {
            "type": "string",
            "description": "Active channel ID"
          },
          "start_at": {
            "type": "number",
            "description": "Date timestamp when channel started on the server."
          },
          "uptime": {
            "type": "number",
            "description": "Seconds have passed since the start of the instance."
          },
          "version": {
            "type": "string",
            "description": "Channel version"
          },
          "core_version": {
            "type": "string",
            "description": "Core version"
          },
          "api_version": {
            "type": "string",
            "description": "Api version"
          },
          "device_id": {
            "type": "number",
            "description": "Current device ID"
          },
          "ip": {
            "type": "string",
            "format": "ipv4",
            "description": "Current channel ip-address"
          },
          "status": {
            "$ref": "#/components/schemas/ChannelStatus"
          },
          "user": {
            "$ref": "#/components/schemas/Contact"
          }
        },
        "required": [
          "start_at",
          "uptime",
          "status"
        ]
      },
      "Message": {
        "title": "Message",
        "required": [
          "id",
          "type",
          "chat_id",
          "from_me",
          "timestamp"
        ],
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "description": "Message ID"
          },
          "type": {
            "$ref": "#/components/schemas/MessageType"
          },
          "subtype": {
            "type": "string",
            "description": "Message subtype"
          },
          "chat_id": {
            "type": "string",
            "description": "Chat ID"
          },
          "chat_name": {
            "type": "string",
            "description": "Chat name"
          },
          "from": {
            "description": "WhatsApp ID of the sender",
            "type": "string"
          },
          "from_me": {
            "type": "boolean",
            "description": "Is message from me"
          },
          "from_name": {
            "type": "string",
            "description": "Pushname of the sender"
          },
          "source": {
            "$ref": "#/components/schemas/MessageSource"
          },
          "timestamp": {
            "type": "number",
            "description": "Message timestamp"
          },
          "device_id": {
            "type": "number",
            "description": "Device ID, if the message was not sent through the app"
          },
          "status": {
            "$ref": "#/components/schemas/StatusEnum"
          },
          "text": {
            "$ref": "#/components/schemas/MessageContentText"
          },
          "image": {
            "$ref": "#/components/schemas/MessageContentImage"
          },
          "video": {
            "$ref": "#/components/schemas/MessageContentVideo"
          },
          "short": {
            "$ref": "#/components/schemas/MessageContentVideo"
          },
          "gif": {
            "$ref": "#/components/schemas/MessageContentVideo"
          },
          "audio": {
            "$ref": "#/components/schemas/MessageContentAudio"
          },
          "voice": {
            "$ref": "#/components/schemas/MessageContentAudio"
          },
          "document": {
            "$ref": "#/components/schemas/MessageContentDocument"
          },
          "link_preview": {
            "$ref": "#/components/schemas/MessageContentLinkPreview"
          },
          "sticker": {
            "$ref": "#/components/schemas/MessageContentSticker"
          },
          "location": {
            "$ref": "#/components/schemas/MessageContentLocation"
          },
          "live_location": {
            "$ref": "#/components/schemas/MessageContentLiveLocation"
          },
          "contact": {
            "$ref": "#/components/schemas/MessageContentContact"
          },
          "contact_list": {
            "$ref": "#/components/schemas/MessageContentContacts"
          },
          "interactive": {
            "$ref": "#/components/schemas/MessageContentInteractive"
          },
          "poll": {
            "$ref": "#/components/schemas/MessageContentPoll"
          },
          "hsm": {
            "$ref": "#/components/schemas/MessageContentHSM"
          },
          "system": {
            "$ref": "#/components/schemas/MessageContentSystem"
          },
          "order": {
            "$ref": "#/components/schemas/MessageContentOrder"
          },
          "group_invite": {
            "$ref": "#/components/schemas/MessageContentLinkPreview"
          },
          "newsletter_invite": {
            "$ref": "#/components/schemas/MessageContentLinkPreview"
          },
          "admin_invite": {
            "$ref": "#/components/schemas/MessageContentNewsletterAdminInvite"
          },
          "product": {
            "$ref": "#/components/schemas/MessageContentProduct"
          },
          "catalog": {
            "$ref": "#/components/schemas/MessageContentLinkPreview"
          },
          "product_items": {
            "$ref": "#/components/schemas/MessageContentProductItems"
          },
          "action": {
            "$ref": "#/components/schemas/MessageAction"
          },
          "context": {
            "$ref": "#/components/schemas/MessageContext"
          },
          "event": {
            "$ref": "#/components/schemas/MessageContentEvent"
          },
          "list": {
            "$ref": "#/components/schemas/MessageContentList"
          },
          "buttons": {
            "$ref": "#/components/schemas/MessageContentButtons"
          },
          "reactions": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/MessageReaction"
            },
            "description": "Reactions for message"
          },
          "labels": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/Label"
            },
            "description": "Labels associated with message"
          }
        }
      },
      "Presence": {
        "title": "Presence",
        "required": [
          "contact_id"
        ],
        "type": "object",
        "properties": {
          "contact_id": {
            "$ref": "#/components/schemas/ContactID"
          },
          "last_seen": {
            "description": "Last seen timestamp",
            "type": "number"
          },
          "status": {
            "description": "Presence status",
            "type": "string",
            "enum": [
              "online",
              "offline",
              "typing",
              "recording",
              "pending"
            ]
          }
        }
      },
      "UpdateSettings": {
        "title": "Update settings",
        "type": "object",
        "required": [
          "before_update",
          "after_update",
          "changes"
        ],
        "properties": {
          "before_update": {
            "$ref": "#/components/schemas/Settings"
          },
          "after_update": {
            "$ref": "#/components/schemas/Settings"
          },
          "changes": {
            "type": "array",
            "items": {
              "type": "string",
              "enum": [
                "callback_backoff_delay_ms",
                "max_callback_backoff_delay_ms",
                "callback_persist",
                "media",
                "webhooks",
                "on_call_pager",
                "pass_through",
                "sent_status",
                "proxy",
                "mobile_proxy",
                "ignored_presences"
              ]
            }
          }
        }
      },
      "QR": {
        "title": "QR code",
        "type": "object",
        "description": "The QR code object contains the QR code image and the status of the QR code. The QR code is only valid for a limited time. Sent on event \"channels.patch\"",
        "properties": {
          "status": {
            "type": "string",
            "description": "Status of the QR code",
            "enum": [
              "OK",
              "TIMEOUT",
              "WAITING",
              "ERROR"
            ]
          },
          "base64": {
            "type": "string",
            "description": "Base64 encoded QR code"
          },
          "rowdata": {
            "type": "string",
            "description": "Rowdata for generating the QR code"
          },
          "expire": {
            "type": "number",
            "description": "Seconds until the QR code expires"
          }
        },
        "required": [
          "status"
        ]
      },
      "Label": {
        "title": "Label",
        "type": "object",
        "properties": {
          "id": {
            "$ref": "#/components/schemas/LabelID"
          },
          "name": {
            "type": "string",
            "description": "Label name"
          },
          "color": {
            "type": "string",
            "description": "Label color",
            "enum": [
              "salmon",
              "lightskyblue",
              "gold",
              "plum",
              "silver",
              "mediumturquoise",
              "violet",
              "goldenrod",
              "cornflowerblue",
              "greenyellow",
              "cyan",
              "lightpink",
              "mediumaquamarine",
              "orangered",
              "deepskyblue",
              "limegreen",
              "darkorange",
              "lightsteelblue",
              "mediumpurple",
              "rebeccapurple"
            ]
          },
          "count": {
            "type": "integer",
            "description": "Number of objects associated with this label",
            "format": "int32",
            "minimum": 0,
            "example": 1
          }
        },
        "required": [
          "id",
          "name",
          "color"
        ]
      },
      "ViewOnce": {
        "title": "View once",
        "type": "object",
        "properties": {
          "view_once": {
            "type": "boolean",
            "description": "Is view once"
          }
        }
      },
      "Event": {
        "title": "Event",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/WebhookEventType"
          },
          {
            "properties": {
              "method": {
                "type": "string",
                "enum": [
                  "post",
                  "put",
                  "delete",
                  "patch"
                ]
              }
            }
          }
        ]
      },
      "Participant": {
        "title": "Participant",
        "type": "object",
        "required": [
          "id",
          "rank"
        ],
        "properties": {
          "id": {
            "$ref": "#/components/schemas/ContactID"
          },
          "rank": {
            "type": "string",
            "description": "Participant rank",
            "example": "admin",
            "enum": [
              "admin",
              "member",
              "creator"
            ]
          }
        }
      },
      "ChannelStatus": {
        "title": "Channel status",
        "type": "object",
        "properties": {
          "code": {
            "type": "number",
            "description": "Status code"
          },
          "text": {
            "type": "string",
            "description": "Status text",
            "enum": [
              "NOT_INIT",
              "INIT",
              "LAUNCH",
              "QR",
              "AUTH",
              "ERROR",
              "SYNC_ERROR"
            ],
            "x-enum-descriptions": [
              "Not initialized",
              "Initialized",
              "Launched",
              "Scan QR code",
              "Authorize",
              "Error",
              "Synchronization error"
            ]
          }
        },
        "required": [
          "code",
          "text"
        ]
      },
      "MessageType": {
        "default": "text",
        "type": "string",
        "description": "Message type",
        "enum": [
          "text",
          "image",
          "video",
          "gif",
          "audio",
          "voice",
          "short",
          "document",
          "documentWithCaption",
          "link_preview",
          "location",
          "live_location",
          "contact",
          "contact_list",
          "sticker",
          "system",
          "call",
          "unknown",
          "action",
          "group_invite",
          "newsletter_invite",
          "admin_invite",
          "product",
          "catalog",
          "interactive",
          "reply",
          "poll",
          "hsm",
          "order",
          "story",
          "event",
          "buttons",
          "list",
          "pin",
          "carousel",
          "album"
        ]
      },
      "MessageSource": {
        "default": "text",
        "type": "string",
        "description": "Message source",
        "enum": [
          "web",
          "mobile",
          "api",
          "system",
          "business_api",
          "unknown"
        ]
      },
      "StatusEnum": {
        "type": "string",
        "description": "Message ack status",
        "enum": [
          "failed",
          "pending",
          "sent",
          "delivered",
          "read",
          "played",
          "deleted"
        ],
        "x-enum-descriptions": [
          "Message failed to send (Red error triangle in WhatsApp Mobile)",
          "Message pending to send (One clock in WhatsApp Mobile)",
          "Message received by WhatsApp server (One checkmark in WhatsApp Mobile)",
          "Message delivered to recipient (Two checkmarks in WhatsApp Mobile)",
          "Message read by recipient (Two blue checkmarks in WhatsApp Mobile)",
          "Voice-message played by recipient (Two blue checkmarks in WhatsApp Mobile)",
          "Message deleted by the user"
        ]
      },
      "MessageContentText": {
        "title": "Content text message",
        "description": "",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/MessagePropsText"
          },
          {
            "$ref": "#/components/schemas/ActionButtons"
          },
          {
            "$ref": "#/components/schemas/ActionList"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentImage": {
        "title": "Content image message",
        "type": "object",
        "required": [
          "id"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/MediaFile"
          },
          {
            "$ref": "#/components/schemas/MessagePropsImageOrVideo"
          },
          {
            "$ref": "#/components/schemas/ActionButtons"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentVideo": {
        "title": "Content video message",
        "type": "object",
        "required": [
          "id"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/MediaFile"
          },
          {
            "$ref": "#/components/schemas/MessagePropsImageOrVideo"
          },
          {
            "properties": {
              "seconds": {
                "description": "Optional. For video files, this field indicates the duration of the video file in seconds.",
                "type": "integer",
                "format": "int32"
              },
              "autoplay": {
                "description": "Optional. If the media is a GIF, this field indicates whether the GIF should be played automatically when the message is received.",
                "type": "boolean"
              }
            }
          },
          {
            "$ref": "#/components/schemas/ActionButtons"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentAudio": {
        "title": "Content audio message",
        "type": "object",
        "required": [
          "id"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/MediaFile"
          },
          {
            "$ref": "#/components/schemas/MessagePropsVoice"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentDocument": {
        "title": "Content document message",
        "type": "object",
        "required": [
          "id"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/MediaFile"
          },
          {
            "$ref": "#/components/schemas/MessagePropsDocument"
          },
          {
            "properties": {
              "page_count": {
                "description": "Optional. Number of pages",
                "type": "integer"
              },
              "preview": {
                "description": "Optional. Base64 encoded preview of the media. In JPEG format.",
                "type": "string"
              }
            }
          },
          {
            "$ref": "#/components/schemas/ActionButtons"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentLinkPreview": {
        "title": "Content link preview message",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/MessagePropsText"
          },
          {
            "properties": {
              "url": {
                "type": "string",
                "description": "URL of the link"
              },
              "id": {
                "$ref": "#/components/schemas/MediaID"
              },
              "link": {
                "description": "Optional. Link to media",
                "type": "string"
              },
              "sha256": {
                "description": "Checksum",
                "type": "string"
              },
              "catalog_id": {
                "$ref": "#/components/schemas/ContactID"
              },
              "newsletter_id": {
                "$ref": "#/components/schemas/NewsletterID"
              },
              "invite_code": {
                "$ref": "#/components/schemas/InviteCode"
              }
            }
          },
          {
            "$ref": "#/components/schemas/MessagePropsLinkPreview"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentSticker": {
        "title": "Content sticker message",
        "type": "object",
        "required": [
          "id"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/MediaFile"
          },
          {
            "$ref": "#/components/schemas/MessagePropsSticker"
          },
          {
            "properties": {
              "preview": {
                "description": "Optional. Base64 encoded preview of the media. In PNG format.",
                "type": "string"
              }
            }
          },
          {
            "$ref": "#/components/schemas/Size"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentLocation": {
        "title": "Content location message",
        "type": "object",
        "required": [
          "latitude",
          "longitude"
        ],
        "allOf": [
          {
            "properties": {
              "latitude": {
                "format": "double",
                "description": "Latitude of location being sent",
                "type": "number"
              },
              "longitude": {
                "format": "double",
                "description": "Longitude of location being sent",
                "type": "number"
              },
              "address": {
                "description": "Address of the location",
                "type": "string"
              },
              "name": {
                "description": "Name of the location",
                "type": "string"
              },
              "url": {
                "description": "URL for the website where the user downloaded the location information",
                "type": "string"
              },
              "preview": {
                "description": "Optional. Base64 encoded preview of the media. In JPEG format.",
                "type": "string"
              },
              "accuracy": {
                "description": "Accuracy of the location in meters",
                "type": "integer",
                "format": "int32"
              },
              "speed": {
                "description": "Speed of the location in meters per second",
                "type": "integer",
                "format": "int32"
              },
              "degrees": {
                "description": "Degrees clockwise from true north",
                "type": "integer",
                "format": "int32"
              },
              "comment": {
                "description": "Optional. Comment for the location",
                "type": "string"
              }
            }
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentLiveLocation": {
        "title": "Content live location message",
        "type": "object",
        "required": [
          "latitude",
          "longitude"
        ],
        "allOf": [
          {
            "properties": {
              "latitude": {
                "format": "double",
                "description": "Latitude of live location being sent",
                "type": "number"
              },
              "longitude": {
                "format": "double",
                "description": "Longitude of live location being sent",
                "type": "number"
              },
              "accuracy": {
                "description": "Accuracy of the live location in meters",
                "type": "integer",
                "format": "int32"
              },
              "speed": {
                "description": "Speed of the live location in meters per second",
                "type": "integer",
                "format": "int32"
              },
              "degrees": {
                "description": "Degrees clockwise from true north",
                "type": "integer",
                "format": "int32"
              },
              "caption": {
                "description": "Optional. Text caption under the live location",
                "type": "string"
              },
              "sequence_number": {
                "description": "Optional. Sequence number of the live location for event tracking",
                "type": "integer",
                "format": "int64"
              },
              "time_offset": {
                "description": "Optional. Time offset of the live location",
                "type": "number"
              },
              "preview": {
                "description": "Optional. Base64 encoded preview of the media. In JPEG format.",
                "type": "string"
              }
            }
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentContact": {
        "title": "Content contact message",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/VCard"
          }
        ]
      },
      "MessageContentContacts": {
        "title": "Content contacts message",
        "type": "object",
        "allOf": [
          {
            "properties": {
              "list": {
                "type": "array",
                "items": {
                  "$ref": "#/components/schemas/VCard"
                }
              }
            }
          }
        ]
      },
      "MessageContentInteractive": {
        "title": "Message content interactive",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/MessagePropsInteractive"
          },
          {
            "$ref": "#/components/schemas/MediaFile"
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentPoll": {
        "title": "Message content poll",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/MessagePollResults"
          },
          {
            "properties": {
              "results": {
                "type": "array",
                "items": {
                  "$ref": "#/components/schemas/PollResults"
                }
              }
            }
          },
          {
            "$ref": "#/components/schemas/ViewOnce"
          }
        ]
      },
      "MessageContentHSM": {
        "title": "Message content HSM",
        "type": "object",
        "properties": {
          "header": {
            "type": "object",
            "description": "Header",
            "properties": {
              "type": {
                "type": "string",
                "description": "Header type",
                "enum": [
                  "text",
                  "image",
                  "video",
                  "document",
                  "location"
                ]
              },
              "text": {
                "$ref": "#/components/schemas/MessagePropsText"
              },
              "image": {
                "$ref": "#/components/schemas/MediaFile"
              },
              "video": {
                "$ref": "#/components/schemas/MediaFile"
              },
              "document": {
                "$ref": "#/components/schemas/MediaFile"
              },
              "location": {
                "$ref": "#/components/schemas/MessageContentLocation"
              }
            }
          },
          "body": {
            "description": "Message text",
            "type": "string"
          },
          "footer": {
            "description": "Message footer",
            "type": "string"
          },
          "buttons": {
            "type": "array",
            "description": "Buttons",
            "items": {
              "$ref": "#/components/schemas/HSMButton"
            }
          }
        }
      },
      "MessageContentSystem": {
        "title": "Message content interactive",
        "type": "object",
        "properties": {
          "body": {
            "type": "string",
            "description": "Message body"
          }
        }
      },
      "MessageContentOrder": {
        "title": "Message content order",
        "type": "object",
        "properties": {
          "order_id": {
            "description": "Order ID",
            "type": "string"
          },
          "seller": {
            "type": "string",
            "description": "Seller ID (Contact ID)"
          },
          "title": {
            "type": "string",
            "description": "Order title"
          },
          "text": {
            "type": "string",
            "description": "Order message text"
          },
          "token": {
            "description": "Base64 secret token",
            "type": "string"
          },
          "item_count": {
            "description": "Total products count",
            "type": "number"
          },
          "currency": {
            "$ref": "#/components/schemas/Currency"
          },
          "total_price": {
            "description": "Total order price",
            "type": "number"
          },
          "status": {
            "description": "Order status",
            "type": "string",
            "enum": [
              "new",
              "accepted",
              "canceled"
            ]
          },
          "preview": {
            "description": "Order preview base64 image JPEG",
            "type": "string"
          }
        }
      },
      "MessageContentNewsletterAdminInvite": {
        "title": "Content newsletter admin invite message",
        "type": "object",
        "required": [
          "newsletter_id",
          "newsletter_name",
          "expiration"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/MessagePropsText"
          },
          {
            "properties": {
              "newsletter_id": {
                "$ref": "#/components/schemas/NewsletterID"
              },
              "newsletter_name": {
                "description": "Newsletter name",
                "type": "string"
              },
              "expiration": {
                "description": "Expiration timestamp of the invitation",
                "type": "number"
              },
              "preview": {
                "type": "string",
                "description": "Base64 encoded newsletter preview image. In JPEG format"
              }
            }
          }
        ]
      },
      "MessageContentProduct": {
        "title": "Message content product",
        "type": "object",
        "properties": {
          "catalog_id": {
            "description": "Catalog ID",
            "type": "string"
          },
          "product_id": {
            "description": "Product ID",
            "type": "string"
          }
        }
      },
      "MessageContentProductItems": {
        "title": "Message content product items",
        "type": "object",
        "properties": {
          "type": {
            "type": "string",
            "description": "Type of interactive",
            "enum": [
              "list",
              "buttons"
            ]
          }
        }
      },
      "MessageAction": {
        "title": "Message action",
        "type": "object",
        "required": [
          "type"
        ],
        "properties": {
          "target": {
            "description": "Target message ID or chat ID",
            "type": "string"
          },
          "type": {
            "description": "Type of action",
            "type": "string",
            "enum": [
              "edited",
              "edit",
              "delete",
              "reaction",
              "ephemeral",
              "vote",
              "comment",
              "event_response",
              "pin",
              "unpin",
              "label_change",
              "media_notify",
              "status_notify",
              "group_status_notify"
            ]
          },
          "emoji": {
            "description": "Action emoji for reaction",
            "type": "string"
          },
          "ephemeral": {
            "description": "Ephemeral message duration",
            "type": "integer"
          },
          "edited_type": {
            "$ref": "#/components/schemas/MessageType"
          },
          "edited_content": {
            "$ref": "#/components/schemas/MessageContent"
          },
          "votes": {
            "type": "array",
            "description": "List of poll options",
            "items": {
              "type": "string"
            }
          },
          "comment": {
            "type": "string",
            "description": "Comment"
          },
          "event_response": {
            "$ref": "#/components/schemas/EventResponse"
          }
        }
      },
      "MessageContext": {
        "title": "Message context",
        "description": "",
        "type": "object",
        "properties": {
          "forwarded": {
            "type": "boolean",
            "description": "Is forwarding message"
          },
          "forwarding_score": {
            "type": "integer",
            "format": "int32",
            "description": "Count fo forwarding message"
          },
          "mentions": {
            "type": "array",
            "items": {
              "type": "string"
            },
            "description": "The numbers of the mentioned users"
          },
          "ad": {
            "$ref": "#/components/schemas/MessageContextAD"
          },
          "conversion": {
            "$ref": "#/components/schemas/MessageContextConversion"
          },
          "quoted_id": {
            "type": "string",
            "description": "ID of quoted message"
          },
          "quoted_type": {
            "$ref": "#/components/schemas/MessageType"
          },
          "quoted_content": {
            "$ref": "#/components/schemas/MessageContent"
          },
          "quoted_author": {
            "type": "string",
            "description": "Whatsapp ID of quoted message author"
          },
          "ephemeral": {
            "type": "integer",
            "description": "Ephemeral message duration"
          }
        }
      },
      "MessageContentEvent": {
        "title": "Message content event",
        "type": "object",
        "properties": {
          "is_canceled": {
            "type": "boolean",
            "description": "True if event is canceled",
            "example": false
          },
          "name": {
            "type": "string",
            "description": "Event name",
            "example": "Some name"
          },
          "description": {
            "type": "string",
            "description": "Event description",
            "example": "Some description"
          },
          "join_link": {
            "type": "string",
            "description": "Join link",
            "example": "https://call.whatsapp.com/voice/K5IFl19olpzTLsh52A0G9R"
          },
          "start": {
            "type": "integer",
            "description": "Chat timestamp",
            "example": 1675964377
          },
          "responses": {
            "type": "array",
            "description": "Event responses",
            "items": {
              "$ref": "#/components/schemas/EventResponse"
            }
          }
        }
      },
      "MessageContentList": {
        "title": "Message content list",
        "type": "object",
        "properties": {
          "header": {
            "type": "string"
          },
          "body": {
            "type": "string"
          },
          "label": {
            "type": "string"
          },
          "footer": {
            "type": "string"
          },
          "sections": {
            "type": "array",
            "items": {
              "type": "object",
              "properties": {
                "title": {
                  "type": "string"
                },
                "rows": {
                  "type": "array",
                  "items": {
                    "type": "object",
                    "properties": {
                      "id": {
                        "type": "string"
                      },
                      "title": {
                        "type": "string"
                      },
                      "description": {
                        "type": "string"
                      }
                    }
                  }
                }
              }
            }
          }
        }
      },
      "MessageContentButtons": {
        "title": "Message content buttons",
        "type": "object",
        "properties": {
          "text": {
            "type": "string"
          },
          "footer": {
            "type": "string"
          },
          "buttons": {
            "type": "array",
            "items": {
              "type": "object",
              "properties": {
                "id": {
                  "type": "string"
                },
                "text": {
                  "type": "string"
                },
                "type": {
                  "type": "string",
                  "enum": [
                    "UNKNOWN",
                    "RESPONSE",
                    "NATIVE_FLOW"
                  ]
                }
              }
            }
          }
        }
      },
      "MessageReaction": {
        "title": "Message reactions",
        "type": "object",
        "required": [
          "emoji"
        ],
        "properties": {
          "id": {
            "type": "string",
            "description": "Reaction ID"
          },
          "emoji": {
            "$ref": "#/components/schemas/Emoji"
          },
          "group_key": {
            "type": "string",
            "description": "Reaction group key"
          },
          "t": {
            "type": "number",
            "description": "Reaction timestamp"
          },
          "unread": {
            "type": "boolean",
            "description": "Is reaction unread"
          },
          "count": {
            "type": "number",
            "description": "Reaction count"
          }
        }
      },
      "MessageContent": {
        "title": "Message content",
        "type": "object",
        "oneOf": [
          {
            "$ref": "#/components/schemas/MessageContentText"
          },
          {
            "$ref": "#/components/schemas/MessageContentImage"
          },
          {
            "$ref": "#/components/schemas/MessageContentVideo"
          },
          {
            "$ref": "#/components/schemas/MessageContentAudio"
          },
          {
            "$ref": "#/components/schemas/MessageContentDocument"
          },
          {
            "$ref": "#/components/schemas/MessageContentLinkPreview"
          },
          {
            "$ref": "#/components/schemas/MessageContentNewsletterAdminInvite"
          },
          {
            "$ref": "#/components/schemas/MessageContentProduct"
          },
          {
            "$ref": "#/components/schemas/MessageContentSticker"
          },
          {
            "$ref": "#/components/schemas/MessageContentLocation"
          },
          {
            "$ref": "#/components/schemas/MessageContentLiveLocation"
          },
          {
            "$ref": "#/components/schemas/MessageContentContact"
          },
          {
            "$ref": "#/components/schemas/MessageContentContacts"
          },
          {
            "$ref": "#/components/schemas/MessageContentInteractive"
          },
          {
            "$ref": "#/components/schemas/MessageContentPoll"
          },
          {
            "$ref": "#/components/schemas/MessageContentReply"
          }
        ]
      },
      "EventResponse": {
        "title": "Message content event",
        "type": "object",
        "properties": {
          "participant": {
            "$ref": "#/components/schemas/ContactID"
          },
          "response": {
            "type": "string",
            "description": "Response",
            "example": "JOING",
            "enum": [
              "UNKNOWN",
              "GOING",
              "NOT_GOING"
            ]
          },
          "timestamp": {
            "type": "integer",
            "description": "Response timestamp",
            "example": 1675964377
          }
        }
      },
      "MessageContentReply": {
        "title": "Message content reply",
        "type": "object",
        "properties": {
          "type": {
            "type": "string",
            "description": "Type of message content",
            "enum": [
              "list_reply",
              "buttons_reply"
            ]
          },
          "list_reply": {
            "$ref": "#/components/schemas/ListReply"
          },
          "buttons_reply": {
            "$ref": "#/components/schemas/ButtonsReply"
          }
        }
      },
      "MediaFile": {
        "title": "Media file",
        "type": "object",
        "required": [
          "id",
          "mime_type",
          "file_size",
          "time"
        ],
        "properties": {
          "id": {
            "$ref": "#/components/schemas/MediaID"
          },
          "link": {
            "description": "Optional. Link to media",
            "type": "string"
          },
          "mime_type": {
            "description": "Mime type of media",
            "type": "string"
          },
          "file_size": {
            "description": "File size in bytes",
            "type": "integer",
            "format": "int64"
          },
          "file_name": {
            "description": "Optional. File name",
            "type": "string"
          },
          "sha256": {
            "description": "Checksum",
            "type": "string"
          },
          "timestamp": {
            "description": "Created at",
            "type": "number"
          }
        }
      },
      "MessagePropsVoice": {
        "title": "Message voice unique parameters",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/MessagePropsAudio"
          },
          {
            "properties": {
              "recording_time": {
                "type": "number",
                "description": "Time in seconds to simulate recording voice",
                "default": 0,
                "minimum": 0
              },
              "waveform": {
                "type": "string",
                "description": "Voice message waveform"
              }
            }
          }
        ]
      },
      "VCard": {
        "title": "VCard",
        "type": "object",
        "properties": {
          "name": {
            "description": "Name of contact",
            "type": "string"
          },
          "vcard": {
            "description": "Vcard of contact",
            "type": "string"
          }
        }
      },
      "MessagePropsDocument": {
        "title": "Message document unique parameters",
        "type": "object",
        "properties": {
          "caption": {
            "description": "Optional. Text caption under the document.",
            "type": "string"
          },
          "filename": {
            "description": "Optional. File name",
            "type": "string"
          }
        }
      },
      "ActionButtons": {
        "title": "Buttons",
        "type": "object",
        "properties": {
          "buttons": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/Button"
            }
          }
        }
      },
      "MessagePropsText": {
        "title": "Message text unique parameters",
        "type": "object",
        "required": [
          "body"
        ],
        "properties": {
          "body": {
            "description": "Message text",
            "type": "string"
          }
        }
      },
      "HSMButton": {
        "title": "Button",
        "type": "object",
        "properties": {
          "id": {
            "description": "ID of the button",
            "type": "string"
          },
          "type": {
            "description": "Type of button",
            "type": "string",
            "enum": [
              "simple",
              "reply",
              "url",
              "phone_number",
              "copy_code"
            ]
          },
          "text": {
            "description": "Button text",
            "type": "string"
          },
          "url": {
            "description": "URL",
            "type": "string"
          },
          "phone_number": {
            "description": "Phone number",
            "type": "string"
          },
          "copy_code": {
            "description": "Code",
            "type": "string"
          }
        }
      },
      "MessagePropsImageOrVideo": {
        "title": "Message image or video unique parameters",
        "type": "object",
        "allOf": [
          {
            "properties": {
              "caption": {
                "description": "Optional. Text caption under the media.",
                "type": "string"
              },
              "preview": {
                "description": "Optional. Base64 encoded preview of the media. In JPEG format.",
                "type": "string"
              }
            }
          },
          {
            "$ref": "#/components/schemas/Size"
          }
        ]
      },
      "MessagePropsInteractive": {
        "title": "Message interactive unique parameters",
        "type": "object",
        "properties": {
          "header": {
            "type": "object",
            "description": "Header of interactive",
            "properties": {
              "text": {
                "type": "string"
              }
            }
          },
          "body": {
            "type": "object",
            "description": "Body of interactive",
            "properties": {
              "text": {
                "type": "string",
                "description": "Text of body"
              }
            }
          },
          "footer": {
            "type": "object",
            "description": "Footer of interactive",
            "properties": {
              "text": {
                "type": "string",
                "description": "Text of footer"
              }
            }
          },
          "action": {
            "$ref": "#/components/schemas/InteractiveAction"
          },
          "type": {
            "$ref": "#/components/schemas/InteractiveType"
          }
        },
        "required": [
          "action"
        ]
      },
      "MessagePropsLinkPreview": {
        "title": "Preview link send parameters",
        "description": "If 'body' contains a link, this props can be used to create custom preview link",
        "type": "object",
        "properties": {
          "title": {
            "type": "string",
            "description": "Title of the link"
          },
          "description": {
            "type": "string",
            "description": "Description of the link"
          },
          "canonical": {
            "type": "string",
            "description": "Canonical URL of the link (for example, if the link is shortened)"
          },
          "preview": {
            "type": "string",
            "description": "Base64 encoded image for mini version link preview. In JPEG format"
          }
        },
        "required": [
          "title"
        ]
      },
      "Currency": {
        "description": "Currency",
        "type": "string",
        "enum": [
          "USD",
          "EUR",
          "AED",
          "AFN",
          "ALL",
          "AMD",
          "ANG",
          "AOA",
          "ARS",
          "AUD",
          "AWG",
          "AZN",
          "BAM",
          "BBD",
          "BDT",
          "BGN",
          "BHD",
          "BIF",
          "BMD",
          "BND",
          "BOB",
          "BRL",
          "BSD",
          "BTN",
          "BWP",
          "BYN",
          "BZD",
          "CAD",
          "CDF",
          "CHF",
          "CLP",
          "CNY",
          "COP",
          "CRC",
          "CUP",
          "CVE",
          "CZK",
          "DJF",
          "DKK",
          "DOP",
          "DZD",
          "EGP",
          "ERN",
          "ETB",
          "FJD",
          "FKP",
          "GBP",
          "GEL",
          "GGP",
          "GHS",
          "GIP",
          "GMD",
          "GNF",
          "GTQ",
          "GYD",
          "HKD",
          "HNL",
          "HRK",
          "HTG",
          "HUF",
          "IDR",
          "ILS",
          "IMP",
          "INR",
          "IQD",
          "IRR",
          "ISK",
          "JEP",
          "JMD",
          "JOD",
          "JPY",
          "KES",
          "KGS",
          "KHR",
          "KMF",
          "KPW",
          "KRW",
          "KWD",
          "KYD",
          "KZT",
          "LAK",
          "LBP",
          "LKR",
          "LRD",
          "LSL",
          "LYD",
          "MAD",
          "MDL",
          "MGA",
          "MKD",
          "MMK",
          "MNT",
          "MOP",
          "MRU",
          "MUR",
          "MVR",
          "MWK",
          "MXN",
          "MYR",
          "MZN",
          "NAD",
          "NGN",
          "NIO",
          "NOK",
          "NPR",
          "NZD",
          "OMR",
          "PAB",
          "PEN",
          "PGK",
          "PHP",
          "PKR",
          "PLN",
          "PYG",
          "QAR",
          "RON",
          "RSD",
          "RUB",
          "RWF",
          "SAR",
          "SBD",
          "SCR",
          "SDG",
          "SEK",
          "SGD",
          "SHP",
          "SLL",
          "SOS",
          "SRD",
          "SSP",
          "STN",
          "SVC",
          "SYP",
          "SZL",
          "THB",
          "TJS",
          "TMT",
          "TND",
          "TOP",
          "TRY",
          "TTD",
          "TWD",
          "TZS",
          "UAH",
          "UGX",
          "UYU",
          "UZS",
          "VEF",
          "VND",
          "VUV",
          "WST",
          "XAF",
          "XCD",
          "XOF",
          "XPF",
          "YER",
          "ZAR",
          "ZMW",
          "ZWL"
        ]
      },
      "MessagePollResults": {
        "title": "Message poll unique parameters",
        "type": "object",
        "properties": {
          "title": {
            "type": "string",
            "description": "Title of poll"
          },
          "options": {
            "type": "array",
            "description": "Options of poll",
            "items": {
              "type": "string"
            },
            "minItems": 2,
            "maxItems": 12
          },
          "vote_limit": {
            "type": "integer",
            "description": "Number of selectable options in poll (1 - can choose only one option, 0 - any number of options)"
          },
          "total": {
            "type": "integer",
            "description": "Total count of selected options"
          }
        },
        "required": [
          "title",
          "options"
        ]
      },
      "PollResults": {
        "title": "Poll results",
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "description": "option ID"
          },
          "name": {
            "type": "string",
            "description": "option name"
          },
          "count": {
            "type": "integer",
            "description": "Number of votes for this option"
          },
          "voters": {
            "type": "array",
            "description": "List of users who voted for this option",
            "items": {
              "$ref": "#/components/schemas/ContactID"
            }
          }
        },
        "required": [
          "id",
          "name",
          "count",
          "voters"
        ]
      },
      "ListReply": {
        "title": "Buttons reply",
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "description": "Clicked list ID"
          },
          "title": {
            "type": "string",
            "description": "Clicked list title"
          },
          "description": {
            "type": "string",
            "description": "Clicked list description"
          }
        }
      },
      "ButtonsReply": {
        "title": "Buttons reply",
        "type": "object",
        "properties": {
          "id": {
            "type": "string",
            "description": "Clicked button ID"
          },
          "title": {
            "type": "string",
            "description": "Clicked button text"
          }
        }
      },
      "MessagePropsSticker": {
        "title": "Message sticker unique parameters",
        "type": "object",
        "allOf": [
          {
            "properties": {
              "animated": {
                "description": "Optional. For stickers, this field indicates whether the sticker is animated.",
                "type": "boolean"
              }
            }
          },
          {
            "$ref": "#/components/schemas/Size"
          }
        ]
      },
      "Size": {
        "title": "Size",
        "type": "object",
        "properties": {
          "width": {
            "description": "Width of the media in pixels",
            "type": "integer",
            "format": "int32"
          },
          "height": {
            "description": "Height of the media in pixels",
            "type": "integer",
            "format": "int32"
          }
        }
      },
      "ActionList": {
        "title": "List of messages",
        "type": "object",
        "properties": {
          "sections": {
            "description": "Section of the message",
            "type": "array",
            "items": {
              "type": "object",
              "properties": {
                "title": {
                  "description": "Title of the section",
                  "type": "string"
                },
                "rows": {
                  "description": "Rows of the section",
                  "type": "array",
                  "items": {
                    "type": "object",
                    "properties": {
                      "title": {
                        "description": "Title of the row",
                        "type": "string"
                      },
                      "description": {
                        "description": "Description of the row",
                        "type": "string"
                      },
                      "id": {
                        "description": "Row ID",
                        "type": "string"
                      }
                    }
                  }
                },
                "product_items": {
                  "description": "Product items of the section",
                  "type": "array",
                  "items": {
                    "type": "object",
                    "properties": {
                      "catalog_id": {
                        "description": "Catalog ID",
                        "type": "string"
                      },
                      "product_id": {
                        "description": "Product ID",
                        "type": "string"
                      }
                    }
                  }
                }
              }
            }
          },
          "button": {
            "description": "Button text for list of message",
            "type": "string"
          }
        }
      },
      "MessageContextAD": {
        "title": "Message advertisement",
        "description": "Advertisement message from META-business",
        "type": "object",
        "properties": {
          "advertiser_name": {
            "type": "string",
            "description": "Name of the advertiser"
          },
          "media_type": {
            "$ref": "#/components/schemas/MediaMessageType"
          },
          "preview": {
            "type": "string",
            "description": "Base64 encoded preview. In JPEG format."
          },
          "preview_url": {
            "type": "string",
            "description": "URL of the preview"
          },
          "title": {
            "type": "string",
            "description": "Title of the advertisement"
          },
          "body": {
            "type": "string",
            "description": "Body of the advertisement"
          },
          "media_url": {
            "type": "string",
            "description": "URL of the media"
          },
          "source": {
            "type": "object",
            "properties": {
              "id": {
                "type": "string",
                "description": "ID of the advertisement source"
              },
              "type": {
                "type": "string",
                "description": "Type of the advertisement source"
              },
              "url": {
                "type": "string",
                "description": "URL of the advertisement source"
              }
            }
          },
          "auto_reply": {
            "type": "boolean",
            "description": "True if the advertisement contains an auto-reply"
          },
          "attrib": {
            "type": "boolean",
            "description": "True if the advertisement shows the attributions"
          },
          "ctwa": {
            "type": "string",
            "description": "Call to action"
          },
          "ref": {
            "type": "string",
            "description": "Reference"
          }
        }
      },
      "MessageContextConversion": {
        "title": "Message conversion",
        "description": "Message conversion data",
        "type": "object",
        "properties": {
          "source": {
            "type": "string",
            "description": "Source of the conversion"
          },
          "data": {
            "type": "string",
            "description": "Conversion data in Base64"
          },
          "delay": {
            "type": "integer",
            "description": "Delay in seconds"
          }
        }
      },
      "InteractiveAction": {
        "title": "Interactive action",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/SectionList"
          },
          {
            "$ref": "#/components/schemas/ButtonList"
          },
          {
            "properties": {
              "product": {
                "type": "object",
                "$ref": "#/components/schemas/ActionProduct"
              }
            }
          }
        ]
      },
      "InteractiveType": {
        "default": "button",
        "type": "string",
        "description": "Interactive type",
        "enum": [
          "list",
          "button",
          "product"
        ]
      },
      "MessagePropsAudio": {
        "title": "Message audio unique parameters",
        "type": "object",
        "properties": {
          "seconds": {
            "description": "Optional. For audio files, this field indicates the duration of the audio file in seconds.",
            "type": "integer",
            "format": "int32"
          }
        }
      },
      "Emoji": {
        "type": "string",
        "description": "Reaction text"
      },
      "Error": {
        "title": "Error",
        "type": "object",
        "required": [
          "code",
          "message"
        ],
        "properties": {
          "code": {
            "format": "int32",
            "description": "See the https://whapi.cloud/docs/whatsapp/api/errors for more information.",
            "type": "integer"
          },
          "message": {
            "description": "error message",
            "type": "string"
          },
          "details": {
            "description": "error detail",
            "type": "string"
          },
          "href": {
            "description": "location for error detail",
            "type": "string"
          },
          "support": {
            "description": "support contact",
            "type": "string"
          }
        }
      },
      "MediaSettings": {
        "title": "Media",
        "type": "object",
        "properties": {
          "auto_download": {
            "description": "An array specifying which types of media to automatically download.",
            "type": "array",
            "items": {
              "enum": [
                "image",
                "audio",
                "voice",
                "video",
                "document",
                "sticker"
              ],
              "type": "string"
            }
          },
          "init_avatars": {
            "description": "Set to true if you need to get avatars after channel authorization",
            "type": "boolean"
          }
        }
      },
      "Webhooks": {
        "title": "Webhooks",
        "type": "array",
        "items": {
          "$ref": "#/components/schemas/Webhook"
        }
      },
      "MessageUpdate": {
        "title": "Message update",
        "type": "object",
        "required": [
          "id",
          "before_update",
          "after_update"
        ],
        "properties": {
          "id": {
            "$ref": "#/components/schemas/MessageID"
          },
          "trigger": {
            "$ref": "#/components/schemas/Message"
          },
          "before_update": {
            "$ref": "#/components/schemas/Message"
          },
          "after_update": {
            "$ref": "#/components/schemas/Message"
          },
          "changes": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      },
      "Status": {
        "title": "View status",
        "description": "",
        "type": "object",
        "required": [
          "id",
          "code",
          "status",
          "timestamp"
        ],
        "properties": {
          "errors": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/Error"
            }
          },
          "id": {
            "description": "Message ID from event",
            "type": "string"
          },
          "code": {
            "type": "number",
            "description": "Status code"
          },
          "status": {
            "$ref": "#/components/schemas/StatusEnum"
          },
          "recipient_id": {
            "$ref": "#/components/schemas/ChatID"
          },
          "viewer_id": {
            "$ref": "#/components/schemas/ContactID"
          },
          "timestamp": {
            "description": "Timestamp of the status message",
            "type": "string"
          }
        }
      },
      "ChatUpdate": {
        "title": "Chat update",
        "type": "object",
        "required": [
          "before_update",
          "after_update",
          "changes"
        ],
        "properties": {
          "before_update": {
            "$ref": "#/components/schemas/Chat"
          },
          "after_update": {
            "$ref": "#/components/schemas/Chat"
          },
          "changes": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      },
      "ContactUpdate": {
        "title": "Contact update",
        "type": "object",
        "required": [
          "before_update",
          "after_update",
          "changes"
        ],
        "properties": {
          "before_update": {
            "$ref": "#/components/schemas/Contact"
          },
          "after_update": {
            "$ref": "#/components/schemas/Contact"
          },
          "changes": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      },
      "ParticipantEvent": {
        "title": "Participant event",
        "type": "object",
        "required": [
          "group_id",
          "participants",
          "action"
        ],
        "properties": {
          "group_id": {
            "$ref": "#/components/schemas/GroupID"
          },
          "participants": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/ContactID"
            }
          },
          "action": {
            "type": "string",
            "description": "Participant action",
            "example": "promote",
            "enum": [
              null,
              "add",
              "remove",
              "promote",
              "demote",
              "request",
              "revoke_request",
              "rejected_request"
            ]
          }
        }
      },
      "GroupUpdate": {
        "title": "Group update",
        "type": "object",
        "required": [
          "before_update",
          "after_update",
          "changes"
        ],
        "properties": {
          "before_update": {
            "$ref": "#/components/schemas/Group"
          },
          "after_update": {
            "$ref": "#/components/schemas/Group"
          },
          "changes": {
            "type": "array",
            "items": {
              "type": "string"
            }
          }
        }
      },
      "CallEvent": {
        "title": "Call event",
        "type": "object",
        "properties": {
          "id": {
            "description": "The ID of the call.",
            "type": "string"
          },
          "chat_id": {
            "description": "The ID of the chat that the call is associated with.",
            "type": "string"
          },
          "status": {
            "description": "The status of the call.",
            "type": "string",
            "enum": [
              "initiated",
              "ringing",
              "missed",
              "canceled",
              "answered"
            ]
          },
          "from": {
            "description": "The ID of the contact that initiated the call.",
            "type": "string"
          },
          "timestamp": {
            "description": "The timestamp of the call.",
            "type": "integer",
            "format": "int64"
          },
          "group_call": {
            "description": "Whether the call is a group call.",
            "type": "boolean"
          },
          "video_call": {
            "description": "Whether the call is a video call.",
            "type": "boolean"
          },
          "offline_call": {
            "description": "Whether the call is an offline call.",
            "type": "boolean"
          },
          "latency": {
            "description": "The latency of the call in milliseconds.",
            "type": "integer",
            "format": "int64"
          }
        }
      },
      "Webhook": {
        "title": "Webhook",
        "type": "object",
        "required": [
          "url"
        ],
        "allOf": [
          {
            "$ref": "#/components/schemas/WebhookParameters"
          },
          {
            "properties": {
              "events": {
                "type": "array",
                "items": {
                  "$ref": "#/components/schemas/Event"
                },
                "default": [
                  {
                    "type": "message",
                    "method": "put"
                  }
                ],
                "description": "Tracked events. <br/>\"messages\" - got new message/got offline messages/edit message/delete message;<br/>\"statuses\" - got message status/got offline message status;<br/>\"chats\" - got chat/chat update/chat remove;<br/>\"contacts\" - contact update;<br/>\"presences\" - got presences<br/>\"groups\" - new group/participants update/group update;<br/>\"calls\" - got call events<br/>labels\" - new label/remove label<br/>\"users\" - login user/logout user<br/>\"channel\" - instance status changed/QR-code update<br/>\"service\" - special notifications<br/><br/>\"message\", \"ack\", \"chat\", \"status\" - is deprecated, use \"messages\", \"statuses\", \"chats\", \"channel\" instead."
              }
            }
          }
        ]
      },
      "WebhookEventType": {
        "title": "Webhook Event Type",
        "type": "object",
        "properties": {
          "type": {
            "type": "string",
            "enum": [
              "messages",
              "statuses",
              "chats",
              "contacts",
              "groups",
              "presences",
              "calls",
              "channel",
              "users",
              "labels",
              "service"
            ]
          }
        }
      },
      "WebhookParameters": {
        "title": "WebhookParameters",
        "type": "object",
        "properties": {
          "send_undecrypted_ad": {
            "description": "Send a webhook for an ad that could not be decrypted.",
            "type": "boolean",
            "default": false
          },
          "webhook_max_age_seconds": {
            "description": "Time in seconds. If the message is delayed more than this time, the hook will not be sent.",
            "type": "number",
            "maximum": 172800,
            "minimum": 15,
            "nullable": true
          },
          "headers": {
            "type": "object",
            "description": "Additional headers for webhook. Max 5 headers. <br/>Example: <br/>\"Authorization - Bearer token\" <br/>\"Content-Type - application/json\" <br/>\"X-Header - value\"",
            "additionalProperties": {
              "type": "string"
            }
          },
          "url": {
            "description": "Inbound and outbound notifications are routed to this URL.",
            "type": "string"
          },
          "mode": {
            "type": "string",
            "default": "body",
            "enum": [
              "body",
              "path",
              "method"
            ],
            "description": "Request method for sending hook."
          }
        }
      },
      "Button": {
        "title": "Button",
        "type": "object",
        "properties": {
          "type": {
            "description": "Type of button",
            "type": "string",
            "enum": [
              "quick_reply",
              "call",
              "copy",
              "url"
            ]
          },
          "title": {
            "description": "Button text",
            "type": "string"
          },
          "id": {
            "description": "Button ID",
            "type": "string"
          },
          "copy_code": {
            "description": "Button code for copy type",
            "type": "string"
          },
          "phone_number": {
            "description": "Button phone number for call type",
            "type": "string"
          },
          "url": {
            "description": "Button url for url type",
            "type": "string"
          },
          "merchant_url": {
            "description": "Button merchant_url for url type",
            "type": "string"
          }
        },
        "required": [
          "type",
          "title",
          "id"
        ]
      },
      "SectionList": {
        "title": "List",
        "type": "object",
        "properties": {
          "list": {
            "type": "object",
            "properties": {
              "sections": {
                "description": "Section of the message",
                "type": "array",
                "items": {
                  "type": "object",
                  "properties": {
                    "title": {
                      "description": "Title of the section",
                      "type": "string"
                    },
                    "rows": {
                      "description": "Rows of the section",
                      "type": "array",
                      "items": {
                        "type": "object",
                        "properties": {
                          "title": {
                            "description": "Title of the row",
                            "type": "string"
                          },
                          "description": {
                            "description": "Description of the row",
                            "type": "string"
                          },
                          "id": {
                            "description": "Row ID",
                            "type": "string"
                          }
                        }
                      }
                    }
                  }
                }
              },
              "label": {
                "description": "Text for list of message",
                "type": "string"
              }
            },
            "required": [
              "sections",
              "label"
            ]
          }
        }
      },
      "ButtonList": {
        "title": "List of buttons",
        "type": "object",
        "properties": {
          "buttons": {
            "description": "Buttons",
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/Button"
            }
          }
        }
      },
      "ActionProduct": {
        "title": "Product fields",
        "type": "object",
        "properties": {
          "catalog_id": {
            "description": "Catalog ID",
            "type": "string"
          },
          "product_id": {
            "description": "Product ID",
            "type": "string"
          }
        }
      }
    },
    "securitySchemes": {
      "bearerAuth": {
        "scheme": "bearer",
        "bearerFormat": "token",
        "type": "http"
      },
      "tokenAuth": {
        "in": "query",
        "name": "token",
        "type": "apiKey"
      }
    }
  },
  "x-readme": {
    "explorer-enabled": true,
    "proxy-enabled": true
  },
  "_id": {
    "buffer": {
      "0": 99,
      "1": 243,
      "2": 91,
      "3": 80,
      "4": 83,
      "5": 246,
      "6": 79,
      "7": 0,
      "8": 111,
      "9": 255,
      "10": 14,
      "11": 223
    }
  }
}
```