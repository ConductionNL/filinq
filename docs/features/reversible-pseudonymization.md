# Reversible pseudonymisation

Anonymise a document and keep a key, so a permitted colleague can put the names back later.

By default an anonymised copy keeps no key. The names are gone for good. When you choose to keep a key, Filinq stores which placeholder stands for which value. Only an admin, or someone in a group the admin names, can use that key.

## When you use it

You share a Woo dossier with the placeholders in it. Months later an objection arrives about "[PERSOON: 1]". With a key you can find out who that was. Without one you cannot.

## Anonymise with a key

1. Open a document on the anonymisation page and review the detected values.
2. Under **After anonymising**, pick **Keep an encrypted key, so a permitted colleague can restore the names**.
3. Anonymise as usual.

The placeholders are the ones OpenRegister writes, such as `[PERSOON: 1]` or `[ADRES: 2]`. Filinq does not invent its own.

If no key could be kept, the sidebar says so and why. That copy then behaves like an irreversible one.

Anonymising the same document again replaces its key. An irreversible rerun removes the old key.

## Restore the names

1. Open the anonymised copy.
2. Click **Restore original**. You only see it when you may restore.
3. The dialog tells you the restore goes into the audit trail. Click **Restore and log**.

For a text file (plain text, Markdown, CSV, HTML, JSON or XML) you get a new file next to the anonymised copy, named `<name>_restored`. The anonymised copy stays as it was.

For a PDF or another format Filinq cannot rewrite safely, no file is made. The dialog shows a table of placeholders and the values behind them instead.

## Who may restore

Admins may always restore. To let others restore, list their groups in the app setting `pseudonymisation_restore_allowed_groups` as a JSON array:

```bash
occ config:app:set filinq pseudonymisation_restore_allowed_groups --value='["privacy-officers"]'
```

An empty list means admins only. A value that is not a list of group names means nobody may restore, not even admins. That way a broken setting never opens the key to everyone.

A user also has to be able to open the anonymised copy. Someone who cannot gets "Document not found", the same answer as for a link that does not exist.

## What gets logged

Every attempt goes into the OpenRegister audit trail, with the user, the time and the anonymisation link:

| Action | When |
|---|---|
| `filinq.pseudonymisation.restore_denied` | The user may not restore, or cannot open the copy |
| `filinq.pseudonymisation.restore_failed` | The copy has no key, or the key would not decrypt |
| `filinq.pseudonymisation.restore_granted` | Written before the restored file or report is made |
| `filinq.pseudonymisation.restored` | The outcome: a copy or a report |

If the audit trail cannot record the granted entry, nothing is restored. The user sees "Nothing was restored, because the audit trail could not record it."

## How the key is kept

The key is a `pseudonymMap` object in the Filinq register, one per anonymisation link.

- The mapping is encrypted with Nextcloud's `ICrypto` and the server secret before it is saved.
- The property is `writeOnly`, so no read through OpenRegister returns it, not even to an admin.
- The schema only lets admins read, create, change or delete these objects.
- Only the restore path decrypts it, after the permission check and the audit entry.
- Deleting the anonymisation link deletes its key.

The non-sensitive fields stay readable: how many placeholders the key covers, the numbering scope, when it was stored and by whom.

## Limits

- The key is protected by the server secret. Someone who controls the server can decrypt it. Per-object keys are an OpenRegister follow-up.
- A key does not expire on its own yet. Retention of the key is a separate change.
- Image redaction and redaction at scale stay irreversible.

## API

| Method | Route | What it does |
|---|---|---|
| POST | `/apps/filinq/api/anonymization/anonymize/{fileId}` | `reversible: true` keeps a key. The answer carries `pseudonymisation` with `keyKept`, `entryCount` or a `reason` |
| GET | `/apps/filinq/api/pseudonymisation/status/{fileId}` | For an anonymised copy the caller can open: `linkId`, `reversible`, `entryCount`, `mayRestore` |
| POST | `/apps/filinq/api/pseudonymisation/{linkId}/restore` | Restores: `mode` is `copy` (with `fileId`, `fileName`, `restored`) or `report` (with `entries`). 403 when refused, 404 when the copy is not yours, 409 without a readable key, 503 when the audit trail is down |
