Get channel settings

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Get channel settings

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
      "get": {
        "tags": [
          "Channel"
        ],
        "requestBody": {
          "required": false,
          "content": {
            "application/json": {
              "schema": {
                "type": "object"
              }
            }
          },
          "description": "OK"
        },
        "responses": {
          "200": {
            "$ref": "#/components/responses/GetSettings"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "getChannelSettings",
        "summary": "Get channel settings"
      }
    }
  },
  "components": {
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
      "GetSettings": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/Settings"
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
      "ChatID": {
        "type": "string",
        "description": "Chat ID",
        "pattern": "^[\\d-]{10,31}@[\\w\\.]{1,}$"
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