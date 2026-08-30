---
id: wcag-compliance
title: Accessibility
sidebar_label: Accessibility
sidebar_position: 4
description: Accessibility posture of the Filinq application UI
keywords:
  - accessibility
  - WCAG
---

# ♿ Accessibility

## Overview
Filinq's application UI is built on standard Nextcloud and `@conduction/nextcloud-vue` components, which target WCAG 2.1 AA at the component level, and supports the NL Design System theme for Dutch government branding.

Filinq does **not** implement its own document-content accessibility checker (no automated WCAG/PDF-UA scanning or auto-fix of generated document content). [Document Validation](document-validation.md) checks document format, integrity, encryption, text-layer presence, and metadata completeness — it does not assess accessibility of the rendered content itself.

## What's actually available
- Keyboard navigation and screen-reader support inherited from Nextcloud/nc-vue components
- NL Design System theming for Dutch government branding
- Full Dutch/English translation of the application UI

## Not implemented
- Automated WCAG/PDF-UA compliance scanning of generated documents
- Automated accessibility fixes to document content (alt text, contrast, heading structure)

If document-content accessibility validation is a hard requirement for your organisation, treat it as an open gap rather than a shipped feature. 