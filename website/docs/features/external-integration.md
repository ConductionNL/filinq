---
id: external-integration
title: External Integration
sidebar_label: External Integration
sidebar_position: 6
description: Seamlessly integrate with external systems while maintaining document sovereignty
keywords:
  - integration
  - SharePoint
  - Office 365
  - collaboration
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# 🤝 External Integration

## Overview
Connect seamlessly with external systems while maintaining complete control over your documents and processing.

## Features

### Integration Capabilities
- SharePoint connectivity
- Office 365 integration
- Case management systems
- Custom API support
- Secure metadata sync

## Quick Start

<Tabs>
<TabItem value="sharepoint" label="SharePoint" default>

```php
// Sync with SharePoint
$connector = $integrationService->connect('sharepoint', [
    'site' => 'https://company.sharepoint.com/sites/docs',
    'syncMode' => 'metadata_only'
]);

$connector->syncDocument($documentId);
```

</TabItem>
<TabItem value="office365" label="Office 365">

```php
// Office 365 integration
$result = $integrationService->shareDocument(
    documentId: 123,
    platform: 'office365',
    options: [
        'permissions' => 'read',
        'expiration' => '+7 days'
    ]
);
```

</TabItem>
</Tabs>

:::tip Data Sovereignty
Only metadata is synchronized with external systems - documents remain secure in your local environment.
:::

:::info Flexible Integration
Connect to any external system while maintaining complete control over document processing and storage.
:::

## Use Cases
- Hybrid cloud deployments
- Enterprise system integration
- Collaborative workflows
- Cross-system document management
- Secure external sharing 