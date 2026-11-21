---
id: signing-envelopes
title: Send several documents to be signed together
sidebar_label: Signing envelopes
description: Put a contract and its annexes in one envelope. The signers get one notification and sign every document in one go; each document keeps its own signed file and audit trail.
keywords:
  - signing
  - envelope
  - several documents
---

# Send several documents to be signed together

An employment contract comes with a confidentiality agreement and the staff rules. The new employee should not get three notifications and sign three times. Put the three documents in one envelope: the signers get one notification and sign everything in one go.

Each document is still its own signing request. It gets its own signed file, its own audit trail and its own checks, so a document that is declined does not undo the ones already signed.

## Send an envelope

1. Open **Signing requests** and choose **New signing request**.
2. Fill in the signature level, the signing mode and the signers. Every document in the envelope uses these.
3. Choose **Send several documents together**. The document on the form is the first row; add the others by their file id.
4. Give the envelope a name and choose **Send N documents**.

An envelope holds 2 to 25 documents. When one of them cannot become a signing request (a file you cannot reach, a level the provider does not offer), nothing is sent: the requests already made are cancelled and the message names the document.

**Envelopes** in the menu lists the envelopes you sent. An admin sees every envelope.

## Sign an envelope

The notification says how many documents are waiting. Open any of them: the request page shows the envelope with every document and its status. Choose **Sign all documents**. Each document is signed on its own, with every check a single signature has. When one of them needs something first, for example a stronger identity check, the others are signed and the page lists the one that was not, with the reason.

You can still open and sign each document on its own.

## What the envelope status means

| Status | Meaning |
|---|---|
| Waiting for signatures | Nobody has signed yet |
| Being signed | At least one signature is in, documents are still open |
| Signed | Every document is signed |
| Partly declined | A signer declined at least one document; what was signed stays signed |
| Ended incomplete | Every document has ended, but not all signed: one expired or was cancelled on its own |
| Cancelled | The sender cancelled the envelope |

The status is worked out from the documents each time the envelope is opened.

## Cancel an envelope

The sender or an admin chooses **Cancel the envelope** on any of its documents. Every document that is still open is cancelled. Documents that were already signed or declined stay as they are.

## Who sees what

The sender, the signers the envelope names and admins see an envelope. Only a named signer can sign through it; only the sender or an admin can cancel it. Anybody else gets "not found".

## Limits

- Field placement is for single requests; an envelope sends its documents without placed fields.
- Each document keeps its own deadline and its own reminders.
- The envelope notification reaches signers who are Nextcloud users. A signer named only by e-mail address gets no Nextcloud notification, for an envelope or for a single request.
