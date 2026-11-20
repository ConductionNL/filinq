---
id: waarmerk
title: Waarmerk (organisation seal)
sidebar_label: Waarmerk
description: Seal published documents with the organisation certificate, certify anonymisation runs and signing transactions, and let anyone verify them without an account
keywords:
  - waarmerk
  - organisation seal
  - eIDAS
  - verification
  - processing certificate
  - evidence document
---

# Waarmerk (organisation seal)

A **waarmerk** is a seal that your organisation puts on a document with its own
organisation certificate. It proves two things: the document comes from your
organisation, and it has not changed since it was sealed.

> **An organisation seal, not a qualified seal.** The waarmerk follows the eIDAS
> *electronic seal* concept (a seal by a legal person, Regulation 910/2014
> Art. 3(25)), at the advanced level at most. It is **not** a qualified
> electronic seal: that needs a qualified trust service provider and a qualified
> seal creation device, which Filinq does not use.

## What a seal looks like

Sealing makes a **copy** of the PDF next to the original, with one extra page at
the end. Existing pages are never changed or overlaid. The waarmerk page shows:

- the waarmerk mark and the organisation name;
- the moment of sealing;
- the **verification code**;
- a **QR code** that opens the public verification page.

The QR code is generated inside Filinq; no outside service is called.

Behind the page, Filinq stores a record of the seal: the SHA-256 of the sealed
file (waarmerk page included), a detached CMS signature over that hash made with
the organisation certificate, the certificate's fingerprint and subject, the
verification code, who sealed it and when. These evidence fields can never be
changed afterwards.

## Sealing a document

- **My Documents**: on a PDF, choose **Add waarmerk** in the row menu. Files that
  carry an active seal show a **Waarmerk** badge.
- **Anonymisation result**: after anonymising to PDF, choose **Add waarmerk** to
  seal the anonymised copy.

If the organisation key cannot be reached, sealing is refused and nothing is
written. An expired certificate is refused too.

## Processing certificate (anonymisation)

On a finished anonymisation, **Anonymisation certificate** creates a sealed PDF
that states what was recorded about the run: source and output file, output
format, number of replacements and runs, the replacements **per type of data**
(for example BSN: 2, PERSON: 5), the detection method, when and by whom.

It contains **types and counts only, never the data that was found** and no text
from the document (AVG Art. 5(1)(c) data minimisation). It attests the recorded
run; nothing is re-read from the document.

## Evidence document (signing)

On a signing request that has finished (completed, declined, cancelled or
expired), the evidence card offers **Download evidence document (pdf)** and
**Copy to dossier**. The evidence document lists every signer with their method
and assurance level, every step with its moment, and the document's checksums.
It carries no document content beyond the title and the checksums, and it is
sealed with the waarmerk. A request that is still open has no evidence document
yet.

## Verifying a waarmerk

Anyone can verify, without a Nextcloud account:

1. Scan the QR code, or open `/apps/filinq/verify/<code>`.
2. The page shows whether the waarmerk exists, the organisation, the moment of
   sealing and the document name. It never shows or offers the document itself.
3. Choose your copy of the PDF. Its SHA-256 is computed **in your browser**; only
   the code and the hash are sent, never the file.

The answer is exactly one of:

| Result | Meaning |
| --- | --- |
| Valid | Your copy is exactly the sealed document and the signature checks out. |
| Not the sealed document | Your copy differs from what was sealed. |
| Revoked | The organisation revoked this waarmerk (with the date and reason). |
| Could not be verified | No such code, or the seal cannot be confirmed. |

Verification fails closed: anything that cannot be proven is never reported as
valid. A revoked waarmerk is never valid, even for an unchanged file. If the
organisation certificate expired after sealing, a valid seal says so as an
advisory. Repeated failed checks are slowed down.

## Revoking

The sealer or an administrator can revoke a waarmerk with a reason through
`POST /api/waarmerk/{id}/revoke`. Revocation cannot be undone and changes nothing
but the status and the revocation fields.

## Administration

**Administration settings → Filinq → Waarmerk (organisation seal)**:

- paste the organisation **certificate** (PEM, public material only); the panel
  shows its subject, SHA-256 fingerprint and expiry;
- choose where the **private key** is held:
  - **Credential broker (OpenRegister)**, the default: enter the credential
    reference; Filinq stores the reference, never the key (ADR-064);
  - **Local custody (interim)**: the key is stored encrypted in this Nextcloud
    and the panel labels it as interim;
- **Test seal** seals and verifies a throwaway document without storing it.

The private key is never stored in a register, never shown again after saving,
and never written to a log.
