# Design: the folder is the query

Kind: code. One query, one projection, one pass.

## Context

`SigningService::bulkSign(array $requestIds)` signs a list of ids in one
session and returns a result per id, with a per-request failure rather
than a throw. `SigningActorResolver::findSignerForUser()` already answers
"which signer record on this request is this user", which is the whole
predicate the folder query needs.

`signingRequest` and `signerRecord` are OpenRegister schemas in the
`filinq` register. The folder therefore needs no storage of its own.

## D1. The folder is a query, never a stored list

A stored folder drifts: a request cancelled elsewhere stays in it, a
newly arrived one is missing from it, and the signer trusts a list that
is wrong. The folder is read from pending signer records for the asking
user at the moment they ask.

That also settles the access question. The query returns what this user
has a pending signer record for, so a folder can never hold a document
its reader has no business seeing.

## D2. Context is projected, not fetched by the signer

The row exists because opening forty cases is the cost. A folder that
lists forty file names moves that cost rather than removing it. So each
entry carries the record it belongs to, what the document is, who asked,
since when, and the deadline if one was declared, projected in the query.

The record reference is a semantic reference to an object in the
consuming app's register, not a copy of its fields. filinq does not know
what a zaak is and does not learn.

## D3. One pass reuses the per-request path

The pass authenticates once and then walks the entries, reusing the
existing per-request signing path. Nothing is fast-pathed: the level
floors, the honest-completion gates and the audit entry per document are
the ones a single request passes. A pass that skipped a gate would
multiply exactly the defect `signing-trust-rebuild` exists to remove.

One refusal is one result row. The remaining documents are still signed,
and the signer is told which ones were not and why, in one summary rather
than forty error toasts.

## D4. The mandate rule is declared and applied, never inferred

ADR-023 draws the line: data authorization is OpenRegister's, action
authorization is the app's, declared rather than hardcoded. Whether this
person may sign this kind of document is an action rule, and the
consuming app declares it per record type because only the consuming app
knows what a mandaat means for its own types.

filinq stores the declaration against the type reference and applies it
in the folder query, so a document outside the mandate is absent rather
than present and refused. The direct attempt is refused too, with the
rule named, because absence alone is not enforcement.

## D5. The folder is a leaf

Per ADR-066 the folder is contributed as a leaf and placed by dossiq and
decidiq. filinq does not know which page it lands on, and both consumers
get the same folder rather than two.

## Risks

- **Forty signatures, one HTTP request.** A pass over forty documents on
  a qualified provider is not a quick call. The pass reports per
  document, is resumable, and an interrupted pass leaves the documents it
  signed signed and the rest pending. It never leaves a half-signed
  document.
- **A mandate declared nowhere.** With no declaration, the folder shows
  what the signer has a pending signer record for and nothing more. That
  is the current behaviour of the per-request path, so an instance that
  declares nothing is no worse off than today, and the spec says so
  rather than implying a protection that is not there.
- **The folder read as an inbox.** It is a list of pending signatures,
  not a task queue. Nothing in it is assigned, delegated or reassigned
  here; delegation belongs to the signer identity work, not to a view.
