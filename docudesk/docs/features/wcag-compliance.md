---
id: wcag-compliance
title: WCAG Compliance
sidebar_label: WCAG Compliance
sidebar_position: 4
description: Ensure document accessibility with WCAG guidelines
keywords:
  - accessibility
  - WCAG
  - PDF/UA
  - compliance
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# ♿ WCAG Compliance

## Overview
Ensure your documents are accessible to everyone by automatically checking and enforcing WCAG guidelines. All processing happens locally while maintaining document integrity.

## Features

### Compliance Checks
- WCAG 2.1 Level AAA compliance
- PDF/UA validation
- Automated fixes
- Detailed reporting
- Custom compliance profiles

## Quick Start

<Tabs>
<TabItem value="check" label="Check Compliance" default>

```php
// Check document accessibility
$report = $wcagService->checkCompliance(
    documentId: 123,
    standard: 'WCAG2.1',
    level: 'AAA'
);
```

</TabItem>
<TabItem value="fix" label="Auto-Fix Issues">

```php
// Automatically fix common issues
$fixed = $wcagService->autoFix(
    documentId: 123,
    options: [
        'addAltText' => true,
        'improveContrast' => true,
        'fixHeadings' => true
    ]
);
```

</TabItem>
</Tabs>

:::tip Automated Improvements
Our system can automatically fix common accessibility issues while preserving document layout and content.
:::

:::info Standards Support
Supports latest WCAG guidelines and PDF/UA requirements for maximum accessibility compliance.
:::

## Use Cases
- Government document compliance
- Educational material preparation
- Public sector documentation
- Corporate communications
- Website content preparation 