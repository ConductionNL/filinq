# Erasure requests

Remove a person from every document they appear in, while the documents and the dossiers themselves stay.

Somebody asks to be erased under the AVG. You record the request, read a preview of every document the person is in, leave out what should stay with a reason, and run the erasure. Filinq rewrites each document without the person, writes a new version where the current one is final, and issues a certificate of what it did and did not do.

Filinq never deletes a document for an erasure request.

## Who may handle erasure requests

Admins, and the groups in the app setting `subject_erasure_groups` (a JSON list). The default is `["docudesk-privacy-officer","docudesk-policy-admins"]`:

```bash
occ config:app:set filinq subject_erasure_groups --value='["privacy-officers"]'
```

A value that is not a list of group names means nobody may act, admins included. Every refusal goes into the OpenRegister audit trail. A step the audit trail cannot record is not done.

## Record a request

1. Open **Erasure requests** in the menu and click **New erasure request**.
2. Fill in the person to remove and, one per line, the other ways the documents name them: an email address, a phone number, another spelling.
3. Fill in the legal ground. The answer is due in one month unless you set another date.
4. Click **Record request**.

The request is saved before anything is looked up. Nothing in the documents changes yet.

## Read the preview

Open the request and click **Build preview**. The preview lists, per document:

- how many times the person occurs, and the values found;
- whether the current version is final;
- whether the document is refused, the obligation and who must decide;
- files Filinq cannot rewrite and check, with the reason.

The preview matches the person through the entity catalogue plus the names you entered, not on a surname alone. It shows at most 200 documents and then says how many there really are.

To leave a document in place, give the reason under **Why is this left in place?** and click **Save what is left in place**. An exclusion without a reason is refused.

## Refused documents

Two obligations stop an erasure. The document is left untouched and listed with the obligation and who decides:

| Obligation | Where Filinq reads it | Who decides |
|---|---|---|
| Legal hold | An active Filinq legal hold case, or an active OpenRegister legal hold on the record that holds the file | The legal department that placed the hold |
| Retention | The record that holds the file is appraised to be kept permanently (`blijvend_bewaren`) | The archivist responsible for the selectielijst term |

The record that holds the file is the OpenRegister object whose folder the file sits in. A record due for destruction does not stop an erasure.

A source Filinq cannot read counts as an obligation. One refused document does not stop the rest of the request.

The obligations are checked again when the erasure runs, so a hold placed after the preview still wins.

## Run the erasure

Click **Erase** and confirm. Per document Filinq:

- rewrites the content without the person and checks that the person is gone;
- where the current version is final, writes a new version and clears the older version's content, keeping the version record;
- destroys the encrypted keys of any reversible pseudonymisation of this person, so the names cannot be put back.

A run that stops says how many documents it reached. **Resume erasure** picks up at the document it stopped on.

## The certificate

When the run completes, **Show certificate** shows what happened: the documents erased and how often, the moment, who ran it, the legal ground, the refused documents with their reasons, and documents already published that need republishing. The certificate cannot be changed or deleted, and it stays after the request is gone.

Send the requester the certificate. For a refused document, the requester's answer is the obligation and the person who decides.

## API

All routes are under `/apps/filinq/api/subject-erasures`:

| Verb | Route | Does |
|---|---|---|
| GET | `` | List requests |
| POST | `` | Record a request (`subject`, `identifiers`, `ground`, `dueAt`) |
| GET | `/{id}` | One request |
| POST | `/{id}/preview` | Build the preview |
| PUT | `/{id}/exclusions` | Save exclusions (`exclusions`: document, reason) |
| POST | `/{id}/run` | Run or resume |
| GET | `/{id}/certificate` | The certificate |

Refusals answer 403 (not allowed), 404, 400 (missing field or reason), 409 (wrong step), or 503 (the entity catalogue or the audit trail is unavailable), with `reason` in the body.
