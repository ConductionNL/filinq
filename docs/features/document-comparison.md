---
id: document-comparison
title: Document Comparison
sidebar_label: Document Comparison
sidebar_position: 7
description: Compare different versions of documents and track changes
keywords:
  - comparison
  - diff
  - version control
  - track changes
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# 🔍 Document Comparison

## Overview
Compare different versions of documents to track changes, identify modifications, and ensure document integrity, all within your secure local environment.

## Features

### Comparison Capabilities
- Version-to-version comparison
- Multi-format support (PDF, Word, HTML)
- Visual diff highlighting
- Change tracking and annotation
- Metadata comparison
- Batch comparison support

## Quick Start

<Tabs>
<TabItem value="compare" label="Compare Documents" default>

```php
// Compare two versions of a document
$comparison = $comparisonService->compare(
    originalId: 123,
    revisedId: 124,
    options: [
        'highlightChanges' => true,
        'trackMetadata' => true
    ]
);
```

</TabItem>
<TabItem value="report" label="Generate Report">

```php
// Generate detailed comparison report
$report = $comparisonService->generateReport(
    comparisonId: $comparison->getId(),
    format: 'pdf',
    includeAnnotations: true
);
```

</TabItem>
</Tabs>

:::tip Local Processing
All comparison operations happen locally, ensuring sensitive content never leaves your secure environment.
:::

:::info AI-Powered
Uses advanced AI to identify even subtle changes while maintaining context.
:::

## Use Cases
- Contract revision tracking
- Document version control
- Compliance verification
- Legal document review
- Content validation 