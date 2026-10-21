---
kind: code
depends_on: []
---

# Proposal: evidence-rows-owned-by-the-system

## Why

Since #1433 Filinq writes its evidence and audit records with the system as
owner, so OpenRegister's owner bypass no longer lets the person who triggered a
record edit or delete it. Rows written before #1433 still carry that person as
owner, and the bypass still applies to them: the officer who ran an erasure
can still edit its certificate, and a signer can still edit their own signer
record, through the object API.

## What changes

- A repair step hands every existing row of the server-only schemas to the
  system identity (OpenRegister's `systemUserId`, default `__system__`).
- It runs on every upgrade and is idempotent: rows already owned by the system
  are not touched, and it deletes nothing.

## Out of scope

Schemas that users still write themselves keep their owners.
