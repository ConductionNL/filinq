---
id: external-integration
title: External Integration
sidebar_label: External Integration
sidebar_position: 6
description: Integrate DocuDesk with workflow tools and Nextcloud's own office-app integrations
keywords:
  - integration
  - OpenConnector
  - Windmill
  - n8n
  - Collabora
  - OnlyOffice
---

# 🤝 External Integration

## Overview
DocuDesk does not ship a bespoke connector for external platforms such as SharePoint or Office 365. Integration happens through two real, implemented paths:

## Integration Capabilities

### Nextcloud office-app conversion
PDF conversion dispatches through Nextcloud's `IConversionManager` (NC 31+). When an office app — Collabora, OnlyOffice, or Euro Office — is installed and registered as a conversion provider, DocuDesk uses it for the highest-fidelity source-to-PDF conversion, falling through to LibreOffice headless, PhpWord, or mPDF when no provider is registered. This is a Nextcloud-internal integration, not a connection to Microsoft 365 or SharePoint.

### Workflow automation (OpenConnector, Windmill, n8n)
DocuDesk exposes its document generation, anonymisation, and signing operations through its REST API and OpenRegister objects. Workflow tools such as OpenConnector, Windmill, or n8n can call these endpoints as a step in a larger flow — for example, pulling fields from a register, calling DocuDesk to render a template, and routing the signed PDF onward. There is no dedicated no-code connector shipped in this app; integration is via the documented API.

## Use Cases
- Triggering document generation from a workflow tool (Windmill/n8n)
- Rendering documents with the office app already installed on the Nextcloud instance (Collabora/OnlyOffice/Euro Office)
- Cross-system document routing via OpenConnector