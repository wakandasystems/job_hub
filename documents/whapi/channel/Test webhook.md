Test webhook

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Test webhook

Sends a test webhook callback to the specified URL.

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
    "/settings/webhook_test": {
      "post": {
        "tags": [
          "Channel"
        ],
        "requestBody": {
          "$ref": "#/components/requestBodies/WebhookTest"
        },
        "responses": {
          "200": {
            "$ref": "#/components/responses/Success"
          },
          "400": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "Wrong parameters"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "webhookTest",
        "summary": "Test webhook",
        "description": "Sends a test webhook callback to the specified URL."
      }
    }
  },
  "components": {
    "requestBodies": {
      "WebhookTest": {
        "description": "Options for webhook test",
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/WebhookTestRequest"
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
      "Success": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/ResponseSuccess"
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
      "WebhookTestRequest": {
        "title": "Webhook unsubscribe request",
        "type": "object",
        "allOf": [
          {
            "$ref": "#/components/schemas/WebhookEventType"
          },
          {
            "$ref": "#/components/schemas/WebhookParameters"
          }
        ],
        "required": [
          "url",
          "type",
          "mode"
        ]
      },
      "ResponseSuccess": {
        "title": "ResponseSuccess",
        "type": "object",
        "properties": {
          "success": {
            "type": "boolean"
          }
        },
        "required": [
          "result"
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