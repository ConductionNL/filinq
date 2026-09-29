# Signing with LibreSign

Filinq's built-in signature is a simple electronic signature (SES). For an
advanced signature with a certificate, Filinq can hand the signing to
[LibreSign](https://github.com/LibreSign/libresign), the signing app that
runs on the same Nextcloud.

## Set it up

1. Install and enable the LibreSign app, and set up its certificate in
   LibreSign's own settings.
2. Create a Nextcloud account for Filinq to call LibreSign with, and an app
   password for it. Give them to Filinq:

   ```bash
   occ config:app:set filinq libresign_service_uid --value=signbot
   occ config:app:set filinq libresign_service_app_password --value=<app password> --sensitive
   ```

3. In the admin settings under Digital signing, choose **LibreSign
   (certificate)** as the signing provider. The choice is only there while
   LibreSign is enabled.
4. Turn on **LibreSign certificate is qualified** only when LibreSign signs
   with a qualified certificate from a trust service provider.

## What happens to a request

1. A handler creates a signing request with provider LibreSign. Filinq sends
   LibreSign the document and the signers (their account, or their email
   address), and keeps LibreSign's request id on the request.
2. LibreSign notifies the signers and they sign in LibreSign.
3. Every ten minutes Filinq asks LibreSign about its open requests. Once
   LibreSign reports the document signed, the signed PDF becomes a new
   version of the document, the request is completed and the audit trail
   records it. A request withdrawn in LibreSign is cancelled in Filinq.

Withdrawing a request in Filinq withdraws it in LibreSign too.

## The level a request can ask for

| Level | With LibreSign |
|---|---|
| SES | always |
| AdES | always: LibreSign signs with an X.509 certificate |
| QES | only with **LibreSign certificate is qualified** on |

A request for a level LibreSign cannot sign at is refused when it is
created. Filinq never signs at a lower level and records the higher one.

## When LibreSign goes away

If LibreSign is the chosen provider and the LibreSign app is disabled, new
signing requests fail with a message saying so, and the settings page shows
the same warning. Filinq does not fall back to its built-in signature.

## Nothing is signed early

Filinq takes the file only once LibreSign reports it signed, and only if what
comes back is a PDF. Until then a request stays open; it never completes with
the unsigned original.
