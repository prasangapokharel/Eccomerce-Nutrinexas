# NutriNexus API v1 - Clean Structure

Production-ready API v1 with clean, minimal code structure.

## Directory Structure

```
Api/v1/
├── auth/
│   ├── staff/
│   │   └── index.php      # Staff authentication API
│   └── curior/
│       └── index.php      # Curior authentication API
├── staff/
│   └── index.php          # Staff statistics API
└── README.md              # This documentation
```

## API Endpoints

### Authentication API
- **POST** `/api/v1/auth/staff/login` - Staff login with bearer token
- **POST** `/api/v1/auth/curior/login` - Curior login with bearer token

### Staff API
- **GET** `/api/v1/staff/{id}/stats` - Get detailed staff statistics (requires Bearer token)

### Curior API  
- **GET** `/api/v1/curiors/{id}/stats` - Get curior statistics (requires Bearer token)

## Configuration

Uses `API_URL` from `App/Config/config.php`:
```php
define('API_URL', BASE_URL . '/api/v1');
```

## Features

- ✅ **Clean Structure** - Organized folder layout
- ✅ **Minimal Code** - No mock data, real database queries
- ✅ **Security Headers** - Proper security implementation
- ✅ **Error Handling** - Comprehensive error responses
- ✅ **Production Ready** - Clean, maintainable code

## Testing

Import `apipostman.json` into Postman to test all endpoints.

## Response Format

All responses follow this structure:
```json
{
  "success": boolean,
  "version": "1.0.0",
  "timestamp": "ISO 8601 string",
  "data": object,
  "error": string (on error)
}
```