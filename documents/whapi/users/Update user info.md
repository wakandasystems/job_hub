Update user info

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Update user info

This method is responsible for changing the details of your WhatsApp profile

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
      "patch": {
        "tags": [
          "Users"
        ],
        "requestBody": {
          "content": {
            "application/json": {
              "example": {
                "name": "Some Cool Name",
                "about": "Some Cool About",
                "icon": "https://pps.whatsapp.net/v/..."
              },
              "schema": {
                "$ref": "#/components/schemas/UserProfileUpdate"
              }
            },
            "multipart/form-data": {
              "schema": {
                "$ref": "#/components/schemas/UserProfileUpdate"
              }
            }
          },
          "description": "Change user profile",
          "required": true
        },
        "responses": {
          "200": {
            "$ref": "#/components/responses/Success"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "updateUserProfile",
        "summary": "Update user info",
        "description": "This method is responsible for changing the details of your WhatsApp profile"
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
      "UserProfileUpdate": {
        "title": "UserProfile request body",
        "type": "object",
        "properties": {
          "name": {
            "type": "string",
            "description": "Update user name. Not works on WhatsApp Business.",
            "minLength": 3,
            "maxLength": 25
          },
          "about": {
            "type": "string",
            "description": "Update user info in About section.",
            "minLength": 1,
            "maxLength": 139,
            "deprecated": true
          },
          "icon": {
            "type": "string",
            "description": "Update user icon in base64/url."
          }
        }
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