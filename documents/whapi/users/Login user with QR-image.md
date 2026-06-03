Login user with QR-image

> ## Documentation Index
> Fetch the complete documentation index at: https://whapi.readme.io/llms.txt
> Use this file to discover all available pages before exploring further.

# Login user with QR-image

This method returns an image. Just like on WhatsApp Web you will need to read a QR code to connect to Whapi.Cloud. There are two ways that you can do the reading of the QR code. Connect through our dashboard panel or Make this experience available within your own application.

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
    "/users/login/image": {
      "get": {
        "tags": [
          "Users"
        ],
        "parameters": [
          {
            "$ref": "#/components/parameters/wakeup"
          },
          {
            "$ref": "#/components/parameters/Size"
          },
          {
            "$ref": "#/components/parameters/Width"
          },
          {
            "$ref": "#/components/parameters/Height"
          },
          {
            "$ref": "#/components/parameters/ColorLight"
          },
          {
            "$ref": "#/components/parameters/ColorDark"
          }
        ],
        "responses": {
          "200": {
            "$ref": "#/components/responses/Image"
          },
          "500": {
            "$ref": "#/components/responses/Error"
          }
        },
        "deprecated": false,
        "operationId": "loginUserImage",
        "summary": "Login user with QR-image",
        "description": "This method returns an image. Just like on WhatsApp Web you will need to read a QR code to connect to Whapi.Cloud. There are two ways that you can do the reading of the QR code. Connect through our dashboard panel or Make this experience available within your own application."
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
      "Size": {
        "name": "size",
        "description": "Size of QR-code",
        "schema": {
          "type": "number"
        },
        "in": "query"
      },
      "Width": {
        "name": "width",
        "description": "Width of result image",
        "schema": {
          "type": "number"
        },
        "in": "query"
      },
      "Height": {
        "name": "height",
        "description": "Height of result image",
        "schema": {
          "type": "number"
        },
        "in": "query"
      },
      "ColorLight": {
        "name": "color_light",
        "description": "Background color",
        "example": null,
        "schema": {
          "type": "string"
        },
        "in": "query"
      },
      "ColorDark": {
        "name": "color_dark",
        "description": "Color of code",
        "example": null,
        "schema": {
          "type": "string"
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
      "Image": {
        "content": {
          "image/png": {
            "schema": {
              "type": "string",
              "format": "binary"
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