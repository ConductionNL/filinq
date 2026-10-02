# Signing folder

A wethouder signing forty decisions on a Friday afternoon opens one folder, not forty cases.

The signing folder gathers every document still waiting for your signature, from every case and every app that asked. You read it, sign what you want to sign, and each document keeps its own signature, artifact and audit trail.

## What you see

Open **Signing folder** in the menu. The list shows, per document:

- the document you are asked to sign
- the case it belongs to, as the app that asked labels it
- who asked, and on what date
- the deadline, when one was set
- the signature level

The soonest deadline comes first. Documents without a deadline come last, oldest request first.

Click a document name to read it without leaving the folder.

## Signing a selection

Tick the documents you want to sign and choose **Sign selected**. Each document is signed the same way it would be on its own page, so nothing about the signature changes because you signed forty at once.

After the pass, the folder tells you what happened per document. A document that could not be signed says why, and the rest are still signed. Nothing is left half signed: a document is either fully signed or untouched, so you can simply open the folder again and carry on.

What a signature proves, and what it does not, is answered on the verification screen. The folder repeats none of it.

## The folder is a query

The folder is read the moment you open it. There is no list somebody keeps up to date, so a request that was cancelled elsewhere is gone the next time you look, and a document you just signed has left the folder.

## Mandates

An app can declare which groups may sign a given type of record. An administrator records that declaration:

```http
POST /apps/filinq/api/signing/mandates
{
  "typeReference": "dossiq/besluit",
  "groups": ["portefeuillehouders"],
  "rule": "Only the portefeuillehouder signs a besluit"
}
```

A document outside your mandate is absent from your folder, and signing it directly is refused with the rule quoted back. Withdraw a declaration with `DELETE /apps/filinq/api/signing/mandates/dossiq/besluit`.

Where a record type carries no declaration, the folder shows everything you have a pending signature on. Filinq invents no rule of its own.

## For developers

| Endpoint | What it does |
|----------|--------------|
| `GET /apps/filinq/api/signing/folder` | The folder for the calling user, paged with `limit` and `offset` |
| `POST /apps/filinq/api/signing/folder/sign` | Signs a selection, `requestIds` in the body, one result per document |
| `GET /apps/filinq/api/signing/mandates` | The declarations on this instance (administrator) |
| `POST /apps/filinq/api/signing/mandates` | Records one declaration (administrator) |
| `DELETE /apps/filinq/api/signing/mandates/{app}/{schema}` | Withdraws one declaration (administrator) |

A folder entry references the record it belongs to (`sourceApp`, `subjectRegister`, `subjectSchema`, `subjectId`, `subjectLabel`). It copies none of the consuming app's fields: the reference is what filinq stores, and the app that owns the case owns the rest.

Spec: `openspec/specs/document-signing/spec.md`, requirements REQ-SFC-01 to REQ-SFC-04.
