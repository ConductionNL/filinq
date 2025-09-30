---
id: digital-signing
title: Digital Signing
sidebar_label: Digital Signing
sidebar_position: 2
description: Secure digital document signing and verification within your infrastructure
keywords:
  - signing
  - verification
  - eIDAS
  - digital signature
---

import Tabs from '@theme/Tabs';
import TabItem from '@theme/TabItem';

# ✍️ Digital Signing

## Overview
Transform your document signing workflow with secure, eIDAS-compliant digital signatures. All processing happens locally within your Nextcloud instance, ensuring maximum security and compliance.

## Features

### Signature Types
- Multiple signature types support
  - Qualified Electronic Signatures (QES)
  - Advanced Electronic Signatures (AES)
  - Basic Electronic Signatures
- Signature verification
- Audit trail
- Batch signing capabilities
- Integration with local identity providers

## Quick Start

<Tabs>
<TabItem value="sign" label="Sign Document" default>

```php
// Sign a document
$signedDocument = $signingService->signDocument(
    documentId: 123,
    signatureType: 'qualified',
    signerId: 'john.doe',
    certificate: $certificate
);
```

</TabItem>
<TabItem value="verify" label="Verify Signature">

```php
// Verify a signature
$verification = $signingService->verifySignature(
    documentId: 123,
    signatureId: 'abc-123'
);

if ($verification->isValid()) {
    echo "Signature is valid!";
    echo "Signed by: " . $verification->getSignerName();
    echo "Timestamp: " . $verification->getTimestamp();
}
```

</TabItem>
</Tabs>

## Use Cases

### Legal Documents
- Contract signing
- Document approval workflows
- Multi-party agreements

### Compliance
- Regulatory compliance
- Internal authorizations
- Audit trail maintenance

:::tip Security First
All signing operations occur within your secure environment, ensuring private keys and sensitive data never leave your control.
:::

:::info eIDAS Compliance
Our signing implementation follows eIDAS regulations, making it suitable for legal and regulatory requirements across the EU.
::: 