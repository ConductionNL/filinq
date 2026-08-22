---
sidebar_position: 1
title: Open Filinq for the first time
description: Open Filinq, find your way around the navigation, and confirm the OpenRegister back end is connected.
---

# Open Filinq for the first time

A first look at Filinq — where the app lives in Nextcloud, what the navigation gives you, and how to tell it is wired up to OpenRegister.

## Goal

By the end you will have opened the Filinq app, recognised the dashboard and the left-hand navigation, and confirmed that the OpenRegister-backed lists (Documents, Templates, Consents, …) load.

## Prerequisites

- A Nextcloud account on an instance where the **Filinq** app is installed and enabled.
- The **OpenRegister** app installed and enabled — Filinq stores documents, templates, consents and signing requests in OpenRegister, so it is a hard dependency.
- For the anonymisation and signing flows, the configured external services (Presidio for anonymisation, the signing provider for digital signing) need to be reachable from the Nextcloud instance.

## Steps

1. Open the Nextcloud app menu in the top bar and pick **Filinq**. You land on the dashboard.

   ![Filinq dashboard](/screenshots/tutorials/user/01-first-launch-01.png)

2. Read the dashboard tiles — *Documents anonymised this month*, *Open consent requests*, *Pending signatures*. On a fresh install they read `0`; they fill in as work moves through the app.

   ![Dashboard stat tiles](/screenshots/tutorials/user/01-first-launch-02.png)

3. Open the left-hand navigation. The entries map onto Filinq's concept model: **Documents**, **Templates**, **Anonymisation**, **Consents**, **Signing**, **Settings**.

   ![Filinq navigation](/screenshots/tutorials/user/01-first-launch-03.png)

4. Click **Documents**. The list view opens with a *Cards / Table* toggle, an **Upload** button, and a search sidebar. An empty install shows *No documents found* — expected until someone uploads or generates the first document.

   ![Documents list, empty state](/screenshots/tutorials/user/01-first-launch-04.png)

## Verification

You are set up correctly when: the Filinq dashboard renders without an error banner, the left navigation lists the entries above, and clicking through to **Documents** (or any other list) shows either rows or a clean *No items found* state — not a load error.

## Common issues

| Symptom | Fix |
|---|---|
| "OpenRegister is not installed or enabled" banner | Install and enable the OpenRegister app, then reload Filinq. |
| Lists load but **Upload** opens a dialog with no form fields | The Filinq register import is incomplete — an admin re-runs **Settings → Registers → Re-import configuration**. |
| Filinq is missing from the app menu | The app is not enabled for your account — ask an administrator to enable it (and check it is not restricted to a group you are not in). |

## Reference

- [Document register feature](../../features/document-register.md) — how Filinq stores documents.
- [Manage Filinq settings](../admin/03-admin-settings.md) — register import, anonymisation rules, signing provider configuration.
