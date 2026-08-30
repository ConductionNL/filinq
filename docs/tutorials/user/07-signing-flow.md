---
sidebar_position: 7
title: Sign a document
description: Send a document through Filinq's digital-signing flow and track signatures until every signer has signed.
---

# Sign a document

Filinq's signing flow wraps a configured digital-signing provider (DigiD, EvidentSign, …) with a Nextcloud-native UI: you pick the document, declare the signers and the signing order, dispatch invitations and watch each signature land before exporting the signed PDF.

## Goal

By the end you will have opened a signing request on one document, declared two signers, dispatched invitations, and tracked the request through to completion.

## Prerequisites

- You have completed [Upload a document](./02-upload-document.md) (or rendered the document from a template).
- The instance has a digital-signing provider configured (an admin sets this in **Settings → Filinq → Signing**).
- You know the signers' name + email and they have access to the configured signing provider.

## Steps

1. Open the document detail view and click **Request signature** in the action bar.

   ![Document detail with Request signature action](/screenshots/tutorials/user/07-signing-flow-01.png)

2. The signing dialog asks for the signers: name + email each, plus the **signing order** (sequential or parallel). Add at least one signer.

   ![Signing dialog with signers](/screenshots/tutorials/user/07-signing-flow-02.png)

3. Submit. Filinq creates a SigningRequest object linked to the document and dispatches an invitation email to each signer through the configured provider.

   ![Signing request created](/screenshots/tutorials/user/07-signing-flow-03.png)

4. Track progress in the **Signing** list view. Each request shows the signers and their per-signer status — *Invited*, *Viewed*, *Signed*, *Rejected*. When every signer has signed, the request flips to *Completed* and the signed PDF is attached to the source document's **Outputs** tab.

   ![Signing request completion](/screenshots/tutorials/user/07-signing-flow-04.png)

## Verification

You are done when: the SigningRequest reaches *Completed* state, every signer's row shows *Signed* with a timestamp, and the source document's **Outputs** tab carries a *Signed PDF* entry that opens cleanly.

## Common issues

| Symptom | Fix |
|---|---|
| Invitation never arrives in signer's inbox | The signing provider couldn't dispatch — check **Settings → Filinq → Signing → Recent dispatches** for the failure reason (most often a misconfigured From address). |
| Signer reports the signing page returns 404 | The provider invalidates session URLs after a few hours of inactivity — open the request detail and **Resend** to issue a fresh URL. |
| Request stuck at *Awaiting signer N* even though signer reports they signed | The provider's callback didn't reach Nextcloud (firewall, wrong webhook URL). The admin re-fetches via **Settings → Filinq → Signing → Reconcile open requests**. |

## Reference

- [Digital signing feature](../../features/digital-signing.md) — full reference.
- [Document signing feature](../../features/document-signing.md) — UI overview.
