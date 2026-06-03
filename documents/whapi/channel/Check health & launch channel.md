Check health & launch channel

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Check health & launch channel

Allows you to track and get feedback on the operational status of the whapi channel (instance). An instance is a connection with a phone number that has a WhatsApp account, which will be responsible for sending and receiving messages

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
    "/health": {
      "get": {
        "tags": [
          "Channel"
        ],
        "parameters": [
          {
            "$ref": "#/components/parameters/wakeup"
          },
          {
            "$ref": "#/components/parameters/platform"
          },
          {
            "$ref": "#/components/parameters/channel_type"
          }
        ],
        "responses": {
          "200": {
            "$ref": "#/components/responses/Health"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "checkHealth",
        "summary": "Check health & launch channel",
        "description": "Allows you to track and get feedback on the operational status of the whapi channel (instance). An instance is a connection with a phone number that has a WhatsApp account, which will be responsible for sending and receiving messages"
      }
    }
  },
  "components": {
    "parameters": {
      "wakeup": {
        "name": "wakeup",
        "description": "If set to false, the channel will not launch",
        "schema": {
          "type": "boolean",
          "default": true
        },
        "in": "query"
      },
      "platform": {
        "name": "platform",
        "description": "Browser name, OS name, OS version separated by commas. Example: 'Safari,Windows,10.0.19044' or 'Desktop,Mac OS,11.6.3'",
        "schema": {
          "type": "string",
          "example": "Chrome,Whapi,1.6.0"
        },
        "in": "query"
      },
      "channel_type": {
        "name": "channel_type",
        "description": "Channel type. Web - for linking existing WA account via WA Web, Mobile - for creating new WA account",
        "schema": {
          "type": "string",
          "enum": [
            "web",
            "mobile"
          ],
          "default": "web"
        },
        "in": "query"
      }
    },
    "responses": {
      "Health": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/Health"
            }
          }
        },
        "description": "OK"
      },
      "Error": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/ResponseError"
            }
          }
        },
        "description": "Internal Error"
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