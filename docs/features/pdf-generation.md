# PDF Generation

## Overview

DocuDesk provides a shared, reusable PDF rendering service that any co-installed Nextcloud app can use. The service accepts a Twig template string and data context, renders HTML, and converts to PDF via mPDF. The service is stateless and injectable via DI.

## Usage

### Via DI Container
```php
$pdfService = $container->get(\OCA\DocuDesk\Service\PdfService::class);
$pdfContent = $pdfService->renderPdf('<h1>{{ title }}</h1>', ['title' => 'Hello']);
```

### Via REST API
```
POST /apps/docudesk/api/pdf/render
```

#### Request Body
```json
{
  "template": "<h1>{{ title }}</h1><p>{{ body }}</p>",
  "data": { "title": "Report", "body": "Content here" },
  "options": { "format": "A4", "orientation": "P" },
  "filename": "report.pdf"
}
```

## Options

| Option | Values | Default |
|--------|--------|---------|
| format | A4, A3, Letter, Legal | A4 |
| orientation | P (portrait), L (landscape) | P |
| margin | { top, right, bottom, left } in mm | 15 |
| title | PDF metadata title | empty |
