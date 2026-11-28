# Entity search

Find every document a person, an email address, an IBAN or another detected value appears in, and see per document whether it was anonymised.

The search reads what OpenRegister's detection found when documents were extracted. Filinq keeps no copy of those values and detects nothing itself. A document nobody extracted is not in the results.

Use it for a Woo request or an AVG request: which documents name this person, in which dossiers, and which of them still need anonymising.

## Who may search

Admins, and the groups in the app setting `entity_search.allowed_groups` (a JSON list). The list is empty by default, so only admins may search:

```bash
occ config:app:set filinq entity_search.allowed_groups --value='["privacy-officers"]'
```

A value that is not a list of group names means nobody may search, admins included. Everybody else gets "You are not allowed to use the entity search." from the server, and does not see **Entity search** in the menu.

People outside an admin group see only values detected in the organisations they belong to, the same rule OpenRegister applies to its own entity list. Somebody in no organisation finds nothing.

## Search

1. Open **Entity search** in the menu.
2. Type the value, part of it is enough, and choose a type if you want: person, email address, IBAN and so on.
3. Click **Search**.

The results list each value with its type, category and how often it occurs.

## See where a value occurs

Click a value. You see per document:

- the document and the dossier it sits in;
- whether an anonymised copy exists, or whether the document is itself an anonymised copy;
- OpenRegister's risk level for the file;
- how confident the detection was.

Documents you cannot open are counted, never named. The search gives you a look into the catalogue, not the right to read other people's files. Occurrences in register objects and emails are counted separately.

## The processing log

Every search and every detail view writes a row in the `entitySearchLog` schema before the answer is shown: who, when, the filters and the number of results. The value you searched for is never stored. The row holds its sha256, so an auditor can check a known value against the log without the log becoming a list of personal data.

When the log cannot be written, the search is refused. Nobody can look up a person without it being recorded.

The rows are declared as their own processing activity (`filinq-entity-search`, on the ground of a public task), so they appear in the platform's processing register. Only admins can read the log, and nobody can change or delete a row.

## Not yet

Collecting the documents you found into a Woo request waits for the Woo request workflow. Until then the page has no collect action.

## API

| Verb | Route | Does |
|---|---|---|
| GET | `/apps/filinq/api/entity-search/access` | 200 when you may search, 403 when not. Reads and logs nothing |
| GET | `/apps/filinq/api/entity-search?query=&type=&category=&limit=&offset=` | Search, at most 100 per page |
| GET | `/apps/filinq/api/entity-search/{entityUuid}` | One value and where it occurs |

Refusals answer 403 (not allowed), 404 (no such value, or another organisation's), 400 (no value, type or category), or 503 (the catalogue or the processing log is unavailable), with `reason` in the body.
