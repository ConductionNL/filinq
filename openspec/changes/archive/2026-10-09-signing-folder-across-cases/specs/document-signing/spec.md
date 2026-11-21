# document-signing Specification (delta)

---
status: proposed
---

## Purpose delta

Everything waiting for one signer, across every record, in one folder
with enough context to sign it, signed in one pass that reuses the
per-request path and its gates. Parity register row 4.27, rating `no`,
entered under decision D1. The existing **Bulk signing** requirement
signs a list of request ids the caller supplies; these requirements are
the gathering in front of it and do not change it.

## ADDED Requirements

### Requirement: The signing folder gathers what is pending for one signer (REQ-SFC-01)

Filinq MUST offer a signing folder for the asking user: every signing
request carrying a pending signer record for that user, across every
record and every consuming app, in one list. The folder MUST be read at
the moment it is asked for and MUST NOT be stored. It MUST be ordered by
deadline first and age second, MUST page, and MUST be offered as a leaf
per ADR-066.

#### Scenario: Forty decisions on nine cases, one folder

- GIVEN a wethouder with pending signer records on forty documents across nine cases
- WHEN they open the signing folder
- THEN all forty are listed in one folder, ordered by deadline and then by age

#### Scenario: The folder holds nobody else's work

- GIVEN two signers with pending requests in the same instance
- WHEN each opens the folder
- THEN each sees only the documents carrying their own pending signer record

#### Scenario: A cancelled request leaves the folder at once

- GIVEN a document in the folder whose request is cancelled elsewhere
- WHEN the folder is opened again
- THEN the document is no longer in it, without anything having to rebuild a list

#### Scenario: A signed document leaves the folder

- GIVEN a document the signer has just signed
- WHEN the folder is read again
- THEN the document is gone and the count has dropped by one

### Requirement: Every entry carries the context needed to sign it (REQ-SFC-02)

Each folder entry MUST carry the record it belongs to as a semantic
reference, what the document is, who requested the signature, when it was
requested, and the deadline where one is declared. The record reference
MUST be a reference to the consuming app's object and MUST NOT be a copy
of its fields. The document MUST be readable from the folder without
leaving it.

#### Scenario: The signer knows what they are signing

- GIVEN a folder of forty entries
- WHEN the signer reads one
- THEN it names the case it belongs to, what the document is, who asked, when, and the deadline

#### Scenario: The document is readable in place

- GIVEN an entry in the folder
- WHEN the signer opens the document from it
- THEN the document is shown without navigating to the consuming app

#### Scenario: No case fields are copied into filinq

- GIVEN an entry whose record lives in a consuming app
- WHEN the entry is stored and read
- THEN it holds a reference to that object and none of its fields
- @e2e exclude data-model assertion; covered by PHPUnit on the folder projection

### Requirement: One pass signs many and keeps every per-document record (REQ-SFC-03)

Signing a folder selection MUST authenticate once and MUST produce, per
document, its own signature, artifact and audit trail, through the same
per-request path a single request takes, with the same signature level
floors and honest-completion gates. A refusal on one document MUST be
reported against that document and MUST NOT stop the others. The pass
MUST be resumable, and an interrupted pass MUST leave every document
either fully signed or untouched.

#### Scenario: Thirty-eight of forty

- GIVEN forty selected documents, two of which fail their completion gate
- WHEN the signer signs the folder
- THEN thirty-eight are signed with their own artifacts and audit entries, and the two failures are reported per document with their reasons

#### Scenario: The pass bypasses no gate

- GIVEN a document whose provider cannot produce a verifiable artifact
- WHEN it is signed through the folder
- THEN it reaches the same honest-completion outcome it would have reached as a single request
- @e2e exclude gate equivalence; covered by PHPUnit comparing the folder pass with the single-request path

#### Scenario: An interrupted pass leaves nothing half done

- GIVEN a pass interrupted after twelve of forty
- WHEN the folder is opened again
- THEN twelve are signed and twenty-eight are still pending, and no document is partly signed

### Requirement: A document outside the signer's mandate is not in the folder (REQ-SFC-04)

A consuming app MUST be able to declare, per record type it references,
which role or mandate may sign that type's documents. Filinq MUST store
the declaration against the type reference and MUST apply it when
building the folder, so a document outside the signer's mandate is absent
from it. A direct signing attempt on such a document MUST be refused,
naming the rule that refused it. Where no declaration exists, the folder
MUST show what the signer has a pending signer record for and nothing
more.

#### Scenario: A mandate keeps the folder honest

- GIVEN a case type declaring that only the portefeuillehouder signs its besluiten
- WHEN a beleidsmedewerker with a pending signer record opens the folder
- THEN that besluit is not in it

#### Scenario: The direct attempt is refused too

- GIVEN the same document and the same person
- WHEN they sign it directly through the API
- THEN the request is refused, naming the mandate rule
- @e2e exclude API enforcement; covered by PHPUnit on the action authorization path

#### Scenario: No declaration, no invented restriction

- GIVEN a record type with no mandate declaration
- WHEN the folder is built
- THEN it holds every document the signer has a pending signer record for
