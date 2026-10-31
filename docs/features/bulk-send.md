---
id: bulk-send
title: Send one document to many people for signature
sidebar_label: Bulk send
description: Upload a CSV or Excel list and filinq makes one ordinary signing request per row, after you have seen a report of the rows it leaves out.
keywords:
  - signing
  - bulk send
  - csv
  - xlsx
---

# Send one document to many people for signature

A municipality sends the same declaration to forty residents. Instead of forty signing requests typed one by one, you upload the list once. Filinq checks it, shows you which rows it cannot use and why, and sends nothing until you confirm.

## Start a bulk send

1. Open **Signing requests** and choose **New signing request**.
2. Fill in the document, the signature level and the signing mode. Every request in the batch uses these.
3. Choose **Send to many from a list**, give the send a name and pick the list.
4. Choose **Check the list**. The report says how many rows can be sent and lists every row it leaves out, with the reason.
5. Choose **Send to N recipients**. Filinq creates the requests in the background and shows the progress. You can close the dialog; the send carries on.

**Bulk sends** in the menu lists your sends with their status and progress. An admin sees every send.

## The list

A `.csv` file (comma or semicolon separated) or an `.xlsx` file, first sheet, with a header row:

| Column | Meaning |
|---|---|
| `email` | The recipient's e-mail address |
| `userId` | The recipient's Nextcloud user id |
| `name` | Optional: the name shown on the request |

Each row needs an `email` or a `userId`. At most 1,000 rows and 2 MB. A cell is read as text: a formula is never run.

## Why a row is left out

| Reason | What it means |
|---|---|
| No e-mail address or user | The row names nobody. |
| Not a valid e-mail address | The address cannot receive mail. |
| No user on this Nextcloud | The `userId` does not exist. |
| Same recipient as row N | The person is already on an earlier row. |
| The request could not be made | The row passed the check, but creating its request failed, for example because the user was deleted in the meantime. The other rows are still sent. |

The report is visible to you and to admins only.

## What a bulk send cannot bypass

Every row becomes an ordinary signing request, created the same way as one you make by hand. A level the provider cannot deliver (QES with the native provider, for example) is refused for the whole batch before anything is stored. Identity checks, deadlines and the audit trail work per request, exactly as for a single request.

## Cancel

**Cancel this send** stops the batch and cancels every request in it that nobody has signed or declined yet. Requests that are already signed stay signed.

## Not yet

A send from a template (one generated document per row) and a send to people outside Nextcloud without an e-mail address are not part of this feature yet.

## API

| Call | What it does |
|---|---|
| `POST /apps/filinq/api/signing/batches` | Multipart: `file` plus `documentFileId`, `documentName`, `signatureLevel`, `signingMode`, `provider`, `title`. Answers the batch with its report, status `ready`. |
| `GET /apps/filinq/api/signing/batches` | Your sends; every send for an admin. |
| `GET /apps/filinq/api/signing/batches/{id}` | One send: report and progress. |
| `POST /apps/filinq/api/signing/batches/{id}/confirm` | Start sending. 409 when it is not `ready` or has nobody to send to. |
| `POST /apps/filinq/api/signing/batches/{id}/cancel` | Cancel the send and its open requests. |

Somebody else's send answers 404.
