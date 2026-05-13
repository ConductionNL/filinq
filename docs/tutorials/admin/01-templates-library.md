---
sidebar_position: 1
title: Manage the templates library
description: Curate the shared library of document templates so every DocuDesk user works from the same approved set.
---

# Manage the templates library

The **Templates library** is the instance-wide catalogue of approved templates. Anything an admin promotes here becomes available to every DocuDesk user; anything an admin deprecates is hidden from new renders but kept for traceability of past documents.

## Goal

By the end you will know how to promote a user-created template into the shared library, categorise it, and deprecate an obsolete one.

## Prerequisites

- You are an administrator on the Nextcloud instance, or your user has been granted the DocuDesk *Library curator* role.
- At least one user has created a template that is ready to promote (see [Create a template](../user/04-create-template.md)).

## Steps

1. From the navigation, click **Templates** and switch to the **Library** view (the toggle next to the search box).

   ![Templates library view](/screenshots/tutorials/admin/01-templates-library-01.png)

2. The Library view filters to templates with the *Promoted* badge. Click **Promote a template** to bring up the picker that lists every user-created template not yet in the library.

   ![Promote a template dialog](/screenshots/tutorials/admin/01-templates-library-02.png)

3. Pick the template, assign a **Category** (used to group templates in the render-picker), set the **Audience** (everyone, or restricted to a group), and confirm.

   ![Promotion settings](/screenshots/tutorials/admin/01-templates-library-03.png)

4. To deprecate an obsolete template, open its detail view and click **Deprecate**. The template stays in the library marked *Deprecated* — past documents that reference it still resolve, but new renders can't pick it.

   ![Deprecating a template](/screenshots/tutorials/admin/01-templates-library-04.png)

## Verification

You are done when: the promoted template appears in the **Library** view with its assigned category and audience, regular DocuDesk users can pick it from the render-picker dropdown, and the deprecated template no longer appears in that dropdown (but is still listed in **Library** with the *Deprecated* badge).

## Common issues

| Symptom | Fix |
|---|---|
| Promote dialog says "no eligible templates" | All user templates are either already promoted or have unresolved field warnings — open each candidate and reconcile the **Fields** tab before retrying. |
| Audience setting doesn't restrict access | Group restriction relies on Nextcloud's group membership — confirm in **Users → Groups** that the intended group actually contains the users you expect. |
| Deprecated template still appears in the render-picker | The user's browser cached the template list — they hard-refresh (Shift+F5) or open a new session. |

## Reference

- [Template management feature](../../features/advanced-template-management.md) — full reference, including the data model behind promotion and deprecation.
