---
sidebar_position: 5
title: Render a template
description: Fill a template's placeholder fields and produce a finished document, ready to send, sign or archive.
---

# Render a template

Once a template exists, rendering it turns the placeholders into real values and produces a finished document in the **Documents** register. The rendered document is linked back to its template, so you can later trace any output to the source it was generated from.

## Goal

By the end you will have rendered one of your templates, filled all required fields, and seen the resulting document appear in **Documents**.

## Prerequisites

- You have completed [Create a template](./04-create-template.md) and have at least one template with declared fields and an uploaded source.
- The template's source file passed the "no warnings" check — every placeholder in the source matches a declared field.

## Steps

1. Open the template's detail view and click **Render** in the action bar.

   ![Template detail with Render action](/screenshots/tutorials/user/05-render-template-01.png)

2. The render dialog shows every declared field as a form input — typed according to the field's declared type, with the default pre-filled where you set one. Fill each value.

   ![Render dialog with field inputs](/screenshots/tutorials/user/05-render-template-02.png)

3. Submit. DocuDesk renders the source DOCX/ODT into a finished document and writes it to the **Documents** register. The success banner links straight to the new document.

   ![Render success](/screenshots/tutorials/user/05-render-template-03.png)

4. Click through to the new document. The detail view's **Source** field links back to the template the document was rendered from; the **Metadata** tab carries the field values that were filled in.

   ![Rendered document detail](/screenshots/tutorials/user/05-render-template-04.png)

## Verification

You are done when: a new document appears in **Documents**, its detail view shows a *Generated from template …* badge, and downloading the file (PDF or DOCX, depending on your settings) shows the placeholders replaced with the values you entered.

## Common issues

| Symptom | Fix |
|---|---|
| Render dialog refuses to submit with "field required" on a field you set | The field type is `date` or `number` and your input doesn't parse — switch to the picker (calendar / number stepper) instead of typing freeform text. |
| Output document has `\{\{ fieldName \}\}` literal text where a value should be | The placeholder name in the source doesn't match a declared field — open the template's **Fields** tab and reconcile. |
| Render succeeds but the document doesn't open (corrupt) | The source DOCX is missing or stale — re-upload it on the template detail's **Source** tab. |

## Reference

- [Template management feature](../../features/advanced-template-management.md) — full reference.
- [Document generation feature](../../features/document-generation.md) — what the render pipeline does internally.
