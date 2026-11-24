---
kind: code
---

# Proposal: letter-email-sending

## Summary

Filinq composes a letter from an approved template and can render it as an email body (`format: email`), but nothing sends it. The officer downloads the HTML and pastes it into a mail client. Ruben accepted on 30 October 2026 (DECISIONS row 268) that row sh-letter is partial and needs a plan to send the letter email.

## What changes

- A new endpoint `POST /api/correspondence/send` composes the letter with `format: email` and sends it to the recipient's email address through Nextcloud's mailer (`OCP\Mail\IMailer`), with the PDF of the same letter attached when the officer asks for it.
- The correspondence register entry gets `status: sent`, `sentAt`, `sentTo` and `sentBy`. A failed send records `status: failed` with the reason, as generation already does.
- When the recipient has no email address, or the instance has no mail server, the send is refused with a message that says which, and nothing is recorded as sent.
- The Correspondence page (FqCorrespondentie) gets a "Versturen per e-mail" button next to the download, with a preview of the recipient address and the subject.
- When OpenRegister's email integration leaf is installed, the sent message is linked to the correspondence object through it (ADR-019, ADR-022), as change `signer-consent-notifications-to-email-leaf` does for signer notifications.

## Why

Composing is half of the row "compose an outgoing letter from an approved template and send it". Without sending, every letter leaves filinq by hand, with no record of what was sent to whom.

## Impact

- `lib/Service/CorrespondenceService.php`, `lib/Controller/CorrespondenceController.php`, `appinfo/routes.php`, `lib/Settings/filinq_register.json` (correspondence schema: `sent` status and the three fields).
- `src/views/correspondence/CorrespondenceIndex.vue`, `src/store/modules/correspondence.js`.
- No new dependency: `IMailer` is part of Nextcloud. The email leaf is optional.
