Get limits

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Get limits

Sandbox as well as Trials have some limitations. This endpoint allows you to get information about the remaining and used limits on your channel

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
    "/limits": {
      "get": {
        "tags": [
          "Channel"
        ],
        "responses": {
          "200": {
            "$ref": "#/components/responses/Limits"
          },
          "204": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "No limits"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "getLimits",
        "summary": "Get limits",
        "description": "Sandbox as well as Trials have some limitations. This endpoint allows you to get information about the remaining and used limits on your channel"
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
      "Limits": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/Limits"
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
      "Limits": {
        "title": "Trial limits",
        "type": "object",
        "properties": {
          "messages": {
            "type": "integer",
            "description": "Maximum number of messages that can be sent",
            "example": 100
          },
          "chats": {
            "type": "array",
            "items": {
              "$ref": "#/components/schemas/ChatID"
            },
            "description": "List of chat IDs that can be used"
          },
          "checks": {
            "type": "integer",
            "description": "Maximum number of check phone numbers",
            "example": 100
          },
          "requests": {
            "type": "integer",
            "description": "Maximum number of channel requests",
            "example": 1000
          }
        },
        "required": [
          "messages",
          "chats",
          "checks",
          "requests"
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