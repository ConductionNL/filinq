---
sidebar_position: 8
title: Apply a retention policy
description: Attach a retention policy to a document so DocuDesk schedules its review-and-delete date and warns you before it expires.
---

# Apply a retention policy

A **retention policy** captures *how long* a document must be kept (e.g. *seven years for invoices*, *one year for application drafts*) and *what should happen* when that period expires (delete, archive, anonymise). Attaching a policy to a document hands the lifecycle to DocuDesk: it schedules the review date, surfaces upcoming expirations on the dashboard, and runs the configured action when the timer fires.

## Goal

By the end you will have picked a retention policy from the instance library, attached it to one document, and verified that DocuDesk recorded the review-and-delete date.

## Prerequisites

- You have completed [Upload a document](./02-upload-document.md).
- The instance has at least one retention policy defined (admins manage these in **Settings → DocuDesk → Retention policies**).

## Steps

1. Open the document detail view and click **Apply retention policy** in the action bar.

   ![Document detail with Apply retention action](/screenshots/tutorials/user/08-retention-policy-01.png)

2. The dialog shows every retention policy defined on this instance. Each row carries the duration, the expiration action (delete / anonymise / archive) and a description so you can pick the right one for this document.

   ![Retention policy picker](/screenshots/tutorials/user/08-retention-policy-02.png)

3. Submit. DocuDesk attaches the policy to the document, computes the **Review date** from the policy's duration plus today, and writes it to the document object.

   ![Retention applied](/screenshots/tutorials/user/08-retention-policy-03.png)

4. Open the dashboard's **Upcoming expirations** tile. Documents whose review date is within the next 30 days are listed here, sorted by date — making it easy to plan the review pass before the automatic action fires.

   ![Upcoming expirations on dashboard](/screenshots/tutorials/user/08-retention-policy-04.png)

## Verification

You are done when: the document detail view shows a *Retention* badge with the review date, the document object's `retentionPolicyId` and `reviewAt` fields are populated in OpenRegister, and the dashboard's *Upcoming expirations* tile (after 30 days) includes the document.

## Common issues

| Symptom | Fix |
|---|---|
| Policy picker is empty | No retention policies are defined on this instance — ask an admin to create one in **Settings → DocuDesk → Retention policies**. |
| Wrong policy attached, can it be changed? | Yes — re-run **Apply retention policy** and pick the right one; the new policy overwrites the previous attachment (and updates the review date). |
| Review date in the past after applying | The chosen policy's duration is shorter than the document's age (e.g. *one year* policy on a two-year-old document). DocuDesk surfaces this as a *Review now* badge; the expiration action will fire on the next scheduled run. |

## Reference

- [Document register feature](../../features/document-register.md) — where the retention fields live on the Document object.
- [Configuration management](../../features/admin-settings.md) — how admins define retention policies on the instance.
