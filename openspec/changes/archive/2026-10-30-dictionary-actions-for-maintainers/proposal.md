---
kind: code
depends_on: []
---

# Proposal: dictionary-actions-for-maintainers

## Why

Since #1436 only a dictionary maintainer (the `filinq-policy-admins` group, or
an admin) may create, change or delete custom dictionaries and their terms.
The pages still offered Add, Delete, Import and Add term to everyone, so a
user without the role pressed a button and got a refusal.

## What changes

- `GET /api/custom-dictionaries/capabilities` answers `{ canMaintain }` for the
  caller, from the same check the write endpoints use.
- The dictionary list shows Add and Delete, and the dictionary detail shows
  Import, Add term and term delete, only when `canMaintain` is true. Reading
  stays open to every organisation member.
