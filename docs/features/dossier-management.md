# Dossier management

## Overview

A dossier groups the documents that are anonymised together under one or more
Woo Art. 5 legal bases. It is Filinq's unit of municipal work: folder batches run
on it, the legal-bases summary reports on it, and a Woo publication publishes it.

Until September 2026 the dossier had a schema, a register spec, a controller and
five seeded example objects, and **no surface** — a user could not create, list
or open one anywhere in the app. Everything below is that surface.

## The dossier's membership: a union, not a folder

A dossier keeps a bound **home folder** (`@self.folder`) as the physical home of
the files it owns. Membership, though, is the deduplicated **union** of

1. the files in that home folder, and
2. the explicit references in the dossier's `documents[]` list.

That relaxes the strict folder-equals-dossier equivalence, so **one document can
belong to several dossiers** without being copied. Adding an existing file to a
second dossier appends a reference and moves nothing.

A reference whose target file no longer exists, or that the current user cannot
read, is shown as a **missing** row and counted separately on the index. It is
never silently dropped: a dossier that quietly lists four of its five documents
is worse than one that says the fifth is missing, and this is Woo evidence.

## Removing a document: two operations behind one verb

Removing is either a **trash** or an **unlink**, and the confirmation says which
before you confirm:

| Situation | What happens |
|---|---|
| The file lives in this dossier's home folder and no other dossier references it | Moved to the Nextcloud trash, where it can be restored. Never a hard delete. |
| The file lives elsewhere, or another dossier also references it | Only this dossier's membership reference is dropped. The file is untouched. |

The UI reads `GET /api/dossiers/{id}/documents/{fileId}/removal-mode` before
rendering the dialog. A confirmation that reads the same for both is how someone
deletes a file they meant to unlink.

## Lifecycle

`status` is optional. A dossier that predates the property, or that has never
been moved, reads as `open` — absence is a value with a meaning, not missing
data.

```
open ──▶ in-review ──▶ processed ──▶ published ──▶ closed
          │   ▲             │                        ▲
          │   └── reopen ───┘                        │
          └──────────────── close ───────────────────┘
```

The transitions are declared on the schema as `x-openregister-lifecycle`, so
**OpenRegister refuses an out-of-order write** regardless of what any client
sends. The detail page offers only the transitions legal from the current status,
so the rule is visible rather than discovered by being refused.

## Legal bases

Legal bases are stored as slugs referencing `base` objects — the six canonical
Woo Art. 5 uitzonderingsgronden, seeded on install, plus any a tenant adds. The
index and detail resolve each slug to its label.

A slug that is **not** in the vocabulary is shown in a warning colour with the
raw slug as its label, rather than dropped. A legal basis that silently
disappears from a Woo dossier is a compliance problem, not a rendering detail.

## Renaming

Renaming a dossier also renames its bound home folder, so the record and its
files stay in step. The rename of the **object** always succeeds; the folder
rename is best-effort and reports rather than blocks:

- no write permission on the folder → the dossier is renamed, with a warning
- a sibling already holds the target name → the dossier is renamed, the folder is
  left alone, and the warning says so. A name collision never merges two folders.

Every dossier write is a **full-payload** save. OpenRegister saves are
PUT-semantic, so a rename that posted only `name` would null `bases`,
`checkedOn`, `status` and `documents`.

## API

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/api/dossiers` | List dossiers with status, document count and resolved legal bases |
| POST | `/api/dossiers` | Create a dossier and its bound home folder |
| GET | `/api/dossiers/{id}` | One dossier, aggregated |
| PUT | `/api/dossiers/{id}/name` | Rename, syncing the bound folder |
| PUT | `/api/dossiers/{id}/status` | Move to a new lifecycle status |
| POST | `/api/dossiers/{id}/documents` | Add an existing file by reference |
| DELETE | `/api/dossiers/{id}/documents/{fileId}` | Trash or unlink, per the rules above |
| GET | `/api/dossiers/{id}/documents/{fileId}/removal-mode` | `trash` or `unlink` |
| POST | `/api/anonymization/dossier/{id}/grondslagen-pdf` | Regenerate the legal-bases summary PDF |

A refused transition answers **409** with a readable message. An absent or
unreadable dossier answers **404**. Neither collapses into a 500.

## Dossier-level actions

The detail header wires capabilities that already exist rather than
reimplementing them:

- **Anonymise this dossier** hands off to Folder Analysis for the bound folder.
- **Generate legal-bases PDF** calls the existing summary endpoint.
- **Publish** is presence-gated on the Woo publication pipeline. That pipeline
  has not shipped, so the section explains the capability is absent instead of
  offering an action that would fail.

## Authorisation, stated accurately

Reads and writes go through OpenRegister with its RBAC engaged, so whatever the
`dossier` schema's cascade declares is what is enforced. That cascade currently
declares `read: ["authenticated"]`, so the read guard resolves to an existence
test for any authenticated user in the organisation. `update` and `delete` are
restricted to `docudesk-policy-admins` plus OpenRegister's owner bypass, so an
operator can always manage the dossiers they created.

The **file** half is real regardless: every document listing runs through the
caller's own Nextcloud view, so a file the operator cannot see never enters a
response. Tightening the object cascade is tracked in ConductionNL/filinq#441.

## Not yet built

- The **+ Add document** button routes to Folder Analysis, which already owns
  upload and folder selection, rather than uploading or picking in place. The
  link and unlink API it would call ships and is covered by tests.
- The **auto-dossier modal** on multi-upload (create a dossier when several
  documents are uploaded at once) is not wired. `DossierFormModal` already
  accepts the prefilled name and the select-all-bases toggle that flow needs.
