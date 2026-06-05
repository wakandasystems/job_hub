User info

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# User info

The method allows you to get information about your WhatsApp profile

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
      "name": "Users",
      "description": "Manage the WhatsApp users related to the channel"
    }
  ],
  "paths": {
    "/users/profile": {
      "get": {
        "tags": [
          "Users"
        ],
        "responses": {
          "200": {
            "$ref": "#/components/responses/UserProfile"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "getUserProfile",
        "summary": "User info",
        "description": "The method allows you to get information about your WhatsApp profile"
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
      "UserProfile": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/UserProfile"
            },
            "example": {
              "name": "Some Cool Name",
              "about": "Some Cool About",
              "icon": "https://pps.whatsapp.net/v/..."
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
      "UserProfile": {
        "title": "UserProfile response",
        "type": "object",
        "properties": {
          "name": {
            "type": "string",
            "description": "User name."
          },
          "about": {
            "type": "string",
            "description": "User info in About section."
          },
          "icon": {
            "type": "string",
            "description": "Profile preview icon url."
          },
          "icon_full": {
            "type": "string",
            "description": "Profile avatar url."
          }
        }
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