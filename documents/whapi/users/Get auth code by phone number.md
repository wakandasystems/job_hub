Get auth code by phone number

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Get auth code by phone number

This method returns a code that allows you to connect the phone number to the API without the need to scan a QR code, simply by entering the generated code.

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
    "/users/login/{PhoneNumber}": {
      "get": {
        "tags": [
          "Users"
        ],
        "parameters": [
          {
            "$ref": "#/components/parameters/PhoneNumber"
          }
        ],
        "responses": {
          "200": {
            "$ref": "#/components/responses/AuthCode"
          },
          "400": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "Invalid phone number"
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
          "422": {
            "content": {
              "application/json": {
                "schema": {
                  "$ref": "#/components/schemas/ResponseError"
                }
              }
            },
            "description": "Render QR failed"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "loginUserViaAuthCode",
        "summary": "Get auth code by phone number",
        "description": "This method returns a code that allows you to connect the phone number to the API without the need to scan a QR code, simply by entering the generated code."
      }
    }
  },
  "components": {
    "parameters": {
      "PhoneNumber": {
        "name": "PhoneNumber",
        "style": "simple",
        "explode": false,
        "description": "Phone number without + and spaces, only digits",
        "schema": {
          "$ref": "#/components/schemas/ContactID"
        },
        "in": "path",
        "required": true
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
      "AuthCode": {
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/AuthCode"
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
      "ContactID": {
        "type": "string",
        "description": "Contact ID",
        "pattern": "^([\\d]{7,15})?$"
      },
      "AuthCode": {
        "title": "Auth code",
        "description": "The auth code is the code that is sent to the user's phone app to authenticate the user.",
        "type": "object",
        "properties": {
          "code": {
            "type": "string",
            "description": "The auth code",
            "example": "123-456"
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