# Tasks: signing-folder-across-cases

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 8. -->

## 1. The folder

- [x] 1.1 A folder query over pending signer records for the asking user, across every record, ordered by deadline then age, paged, read at request time and never stored (REQ-SFC-01)
- [ ] 1.2 Offer the folder as a leaf per ADR-066 so dossiq and decidiq place the same one (REQ-SFC-01), NOT BUILT: filinq ships no leaf infrastructure at all (no `RegisterLeafProvidersEvent` listener, no `registerIntegration`, no `leaves` webpack entry), and a leaf built against none of that is a dark leaf nobody can see. Reported in the PR body rather than guessed at. The folder ships as its own page, which dossiq and decidiq can link to today.
- [x] 1.3 Project the record reference, the document kind, the requester, the request date and the deadline onto each entry, as a semantic reference and not a copy of the consuming app's fields (REQ-SFC-02)
- [x] 1.4 Read the document from the folder without leaving it (REQ-SFC-02)

## 2. The pass

- [x] 2.1 Sign a folder selection in one authenticated pass over the existing per-request path, one signature, artifact and audit entry per document (REQ-SFC-03)
- [x] 2.2 Report a refusal per document with its reason, continue with the rest, and make the pass resumable so an interruption leaves each document fully signed or untouched (REQ-SFC-03)

## 3. The mandate

- [x] 3.1 Store a consuming app's per-type mandate declaration against the type reference and apply it in the folder query, so a document outside the mandate is absent (REQ-SFC-04)
- [x] 3.2 Refuse a direct signing attempt outside the mandate, naming the rule, through the action authorization path per ADR-023 (REQ-SFC-04)

## 4. Quality

- [x] 4.1 PHPUnit inside the container for the query, the projection, the pass against the single-request path, the resumption and the mandate refusal; 75% on new code (ADR-009); Playwright `tests/e2e/signing-folder.spec.ts`; Dutch and English strings; docs in `docs/features/signing-folder.md` (no screenshots: the shared browser service is down in this lane, so a capture would be invented rather than taken)
