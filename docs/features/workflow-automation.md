---
id: workflow-automation
title: Workflow Automation
sidebar_label: Workflow Automation
sidebar_position: 10
description: Event-driven processing inside Filinq, and external workflow automation via OpenConnector, Windmill, or n8n
keywords:
  - workflow
  - automation
  - OpenRegister
  - OpenConnector
  - Windmill
  - n8n
---

# ⚡ Workflow Automation

## Overview
Filinq does not ship a bespoke visual workflow designer or document-source monitor (no FTP/SharePoint/Office 365 watchers). Automation happens in two ways that are actually implemented:

## Event-driven processing (inside Filinq)
When a document is created or updated through Open Register, Filinq's event listeners automatically run:
- **Metadata enrichment** — language detection, keyword extraction, topic classification (see [Metadata Enrichment](metadata-enrichment.md))
- **Document validation** — format, integrity, encryption, text-layer, and metadata-completeness checks (see [Document Validation](document-validation.md))
- **Signing lifecycle events** — signer-chain completion and objection-deadline checks trigger notifications through Nextcloud

These run per-object as part of the Open Register save path; there is no separate monitoring or polling step to configure.

## External workflow automation (OpenConnector, Windmill, n8n)
For multi-step processes that span other apps or systems, call Filinq's REST API from a workflow tool — see [External Integration](external-integration.md) for the real integration surface. There is no dedicated no-code workflow designer inside Filinq itself.

## Use Cases
- Automatic metadata enrichment and validation as documents are created/updated
- Signing-deadline and consent-objection-deadline notifications
- Multi-step document flows orchestrated externally via OpenConnector/Windmill/n8n 