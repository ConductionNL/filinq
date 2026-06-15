# Template Management

## Overview

DocuDesk provides CRUD operations for reusable Twig/HTML templates stored as OpenRegister objects. Templates are scoped per-app via a namespace field, enabling multiple Nextcloud apps to maintain their own template collections through a shared service.

## API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/templates` | List templates (supports `namespace`, `_search`, `_limit`, `_offset`) |
| POST | `/api/templates` | Create template (requires `name`, `content`, `namespace`) |
| GET | `/api/templates/{id}` | Get single template |
| PUT | `/api/templates/{id}` | Update template (namespace immutable) |
| DELETE | `/api/templates/{id}` | Delete template |

## Template Properties

| Property | Required | Description |
|----------|----------|-------------|
| name | Yes | Template display name |
| content | Yes | HTML/Twig template content |
| namespace | Yes | App identifier (e.g., `larpingapp`, `opencatalogi`) |
| description | No | Template description |
| format | No | Page size: A4, A3, Letter, Legal (default: A4) |
| orientation | No | P (portrait) or L (landscape) (default: P) |

## Usage via DI

Other Nextcloud apps can use the TemplateService directly:

```php
$templateService = $container->get(\OCA\DocuDesk\Service\TemplateService::class);
$templates = $templateService->getTemplatesByNamespace('myapp');
```
