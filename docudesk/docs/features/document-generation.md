---
id: document-generation
title: Document Generation
sidebar_label: Document Generation
sidebar_position: 1
description: Generate documents in multiple formats while keeping processing local
keywords:
  - documents
  - templates
  - pdf
  - word
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# 📄 Document Generation

## Overview
Transform your document workflow with powerful local template processing. Generate professional documents in multiple formats while keeping your data secure within your Nextcloud instance.

## Features

### Core Capabilities
- Template-based document generation
- Multiple output formats support
- Batch processing capabilities
- Version control
- Local processing guarantee

## Quick Start

<Tabs>
<TabItem value="template" label="Create Template" default>

```twig
Dear {{ customer.name }},

Thank you for your order #{{ order.id }}.
Total amount: €{{ order.total }}

Best regards,
{{ company.name }}
```

</TabItem>
<TabItem value="api" label="API Usage">

```php
// Generate document using template
$document = $templateService->renderTemplate(
    templateId: 1,
    data: [
        'customer' => ['name' => 'John Doe'],
        'order' => ['id' => '12345', 'total' => 99.99],
        'company' => ['name' => 'ACME Corp']
    ],
    format: 'pdf'
);
```

</TabItem>
</Tabs>

## Use Cases

### Business Documents
- Contract generation
- Report creation
- Invoices and quotes
- Business correspondence

### Certificates & Forms
- Certificate issuance
- Bulk document creation
- Dynamic form generation

:::tip Local Processing Advantage
All document generation happens within your Nextcloud instance, ensuring your sensitive data never leaves your control while still maintaining full template flexibility.
:::

:::info Performance
Templates are compiled and cached for optimal performance, enabling rapid document generation even in high-volume scenarios.
::: 