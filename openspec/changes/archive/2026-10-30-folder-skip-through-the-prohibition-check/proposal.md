---
kind: code
depends_on: []
---

# Proposal: folder-skip-through-the-prohibition-check

## Why

The folder anonymisation screen saved its skip and legal-basis decisions by
PATCHing OpenRegister's `/api/entity-relations/{id}` directly. That route
checks only write access to the file, so a person on the publish-never list
could be skipped from the folder screen without the prohibition check the
single-file screen runs (F2 S3 of the filinq security audit).

## What changes

- The folder screen sends each decision to filinq's
  `PATCH /api/anonymization/relations/{id}`, the route the single-file screen
  uses, which checks file access, refuses a prohibited skip and asks for the
  override role when forced.
- A refusal is shown on the entity, as other failures already are.
