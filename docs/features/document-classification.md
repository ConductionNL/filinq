---
id: document-classification
title: Document Classification
sidebar_label: Document Classification
sidebar_position: 8
description: Automatically classify and organize documents based on content
keywords:
  - classification
  - organization
  - AI
  - machine learning
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# 🏷️ Document Classification

## Overview
Automatically classify and organize documents using AI-powered content analysis, all processed securely within your local environment.

## Features

### Classification Capabilities
- AI-powered content analysis
- Multi-language support
- Custom classification rules
- Automated metadata tagging
- Confidence scoring
- Batch classification

## Quick Start

<Tabs>
<TabItem value="classify" label="Classify Document" default>

```php
// Classify a document
$classification = $classificationService->analyze(
    documentId: 123,
    options: [
        'languages' => ['en', 'nl'],
        'confidence' => 0.8
    ]
);
```

</TabItem>
<TabItem value="custom" label="Custom Rules">

```php
// Apply custom classification rules
$rules = [
    'contract' => ['keywords' => ['agreement', 'parties', 'terms']],
    'invoice' => ['patterns' => ['invoice_number', 'total_amount']]
];

$result = $classificationService->classifyWithRules(
    documentId: 123,
    rules: $rules
);
```

</TabItem>
</Tabs>

:::tip AI Privacy
All AI processing happens locally, ensuring document content remains secure.
:::

:::info Automatic Organization
Automatically organizes documents into logical categories based on content and context.
:::

## Use Cases
- Automated document routing
- Content organization
- Regulatory compliance
- Archive management
- Workflow automation 