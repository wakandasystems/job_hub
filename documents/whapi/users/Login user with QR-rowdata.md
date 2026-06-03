Login user with QR-rowdata

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Login user with QR-rowdata

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
    "/users/login/rowdata": {
      "get": {
        "tags": [
          "Users"
        ],
        "parameters": [
          {
            "$ref": "#/components/parameters/wakeup"
          }
        ],
        "responses": {
          "200": {
            "$ref": "#/components/responses/UserLogin"
          },
          "406": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "Not acceptable for mobile type channel"
          },
          "409": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "Channel already authenticated"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "loginUserRowData",
        "summary": "Login user with QR-rowdata"
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
      "UserLogin": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/QR"
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