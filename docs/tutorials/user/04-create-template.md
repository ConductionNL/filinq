---
sidebar_position: 4
title: Create a template
description: Define a reusable document template with placeholder fields that DocuDesk can render into a finished document later.
---

# Create a template

A **Template** is a reusable document skeleton with named placeholder fields. Once a template exists, you (or any other DocuDesk flow) can render it into a finished document by filling those placeholders — useful for letters, contracts, receipts, GDPR responses, anything you produce more than once.

## Goal

By the end you will have created a new template, added a few placeholder fields, uploaded the underlying document file (DOCX or ODT), and verified the placeholders are recognised.

## Prerequisites

- You have completed [Open DocuDesk for the first time](./01-first-launch.md).
- You have a DOCX or ODT file ready that uses DocuDesk's placeholder syntax (`\{\{ fieldName \}\}`).

## Steps

1. From the navigation, click **Templates** and then **Add Template**.

   ![Templates list with Add Template](/screenshots/tutorials/user/04-create-template-01.png)

2. Fill in the *Add Template* dialog: a clear **Name** (appears in the renderer dropdown when other flows pick a template), a **Description** (for your future self and your colleagues), and the **Category** (used to group templates in the library).

   ![Add Template dialog](/screenshots/tutorials/user/04-create-template-02.png)

3. Open the new template's detail view, switch to the **Fields** tab, and declare each placeholder field your DOCX/ODT references. For each field set the name (must match the placeholder), the type (`string`, `date`, `number`, `boolean`) and an optional default.

   ![Template field declarations](/screenshots/tutorials/user/04-create-template-03.png)

4. Upload the DOCX or ODT file in the **Source** tab. DocuDesk parses it, lists detected placeholders, and warns about any that aren't declared in the **Fields** tab (or declared fields that don't appear in the source).

   ![Template source with detected placeholders](/screenshots/tutorials/user/04-create-template-04.png)

## Verification

You are done when: the template appears in the **Templates** list, the **Fields** tab matches the placeholders in the source file (no warnings), and the **Render** action on the detail view is enabled.

## Common issues

| Symptom | Fix |
|---|---|
| Source upload says "no placeholders detected" | Your DOCX uses the wrong delimiters — DocuDesk expects `\{\{ name \}\}`, not `{name}` or `[name]`. Update the source. |
| A declared field never gets filled at render time | The placeholder name in the source has a typo (e.g. trailing space inside the braces) — open the DOCX in Word/LibreOffice and re-check. |
| Template detail won't open after creation | The OpenRegister write failed at create time (often a permissions issue). Check **Settings → DocuDesk → Logs** for the underlying error. |

## Reference

- [Template management feature](../../features/advanced-template-management.md) — full reference for the Template object.
- [Render a template](./05-render-template.md) — how to turn a template into a finished document.
