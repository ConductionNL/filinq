# Tasks: documents-in-and-out-of-the-building

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 12. -->

## 1. The registration entry

- [x] 1.1 Add a `documentRegistration` schema to the `filinq` register with `direction`, `registeredAt`, `unit`, `document`, `registrationNumber`, `answers` and `withdrawnReason`, with a descriptor version bump (REQ-DIO-01)
  - `direction` is a closed enum: a document either came in or went out, and a
    third value would be a document that never left, which is not a post
    register entry at all.
  - `registeredAt` is the moment of REGISTERING, not the date printed on the
    document. The two differ often enough that conflating them loses the point
    of a post register.
  - The discharge is a LINK (`answers`) and there is no `answered` flag on the
    inbound entry. A flag can be set without an answer existing; a link cannot.
    Asserted by name, including the absence of `answered` and `dischargedAt`.
- [~] 1.2 Declare `x-openregister-generated` on `registrationNumber`: a format carrying the year and the position, and a yearly reset; no counter in filinq (REQ-DIO-01)
  - DECLARED AND VERIFIED AGAINST THE REAL VALIDATOR, not just written:
    OpenRegister's own `GeneratedIdentifierDeclaration::fromProperty()` accepts
    it and renders `2026-00042` for sequence value 42. No counter in filinq, so
    two registrations in the same second cannot race for one number and silently
    reuse it.
  - 🔴 "A SEQUENCE NAMED PER UNIT" IS NOT POSSIBLE TODAY, AND SHIPPING IT WOULD
    HAVE BEEN SILENT. `GeneratedIdentifierListener::scopeKey()` builds the scope
    as `'gen:' . $declaration->sequence() . '|' . $period`, substituting NOTHING.
    So `filinq-post-{unit}` creates ONE series literally named that, shared by
    every unit, while reading in the schema as though each unit had its own. Two
    units' numbers would interleave in one pool and nothing would report it.
  - So the sequence is the literal `filinq-post` and the series is
    INSTANCE-WIDE. Said in the property's own description and asserted by a test
    that refuses a `{` in the sequence name, so it cannot be added back as an
    apparent improvement.
  - WHAT WAITS, named rather than guessed: per-unit series need OpenRegister to
    resolve placeholders in a sequence name against the object being saved. That
    is a platform change and belongs in openregister's `generated-identifier`
    change, not here.
- [ ] 1.3 Refuse an update that changes a set registration number, in the service every write path resolves through (REQ-DIO-01)
- [ ] 1.4 Refuse the registration, naming the missing sequence, when the register declares no generated identifier (REQ-DIO-01)

## 2. Discharge and the open post list

- [ ] 2.1 An outbound registration names the inbound one it answers; the discharge is read from the link and never written as a status on the inbound entry (REQ-DIO-02)
- [ ] 2.2 The open post list per unit, oldest first, offered as a leaf per ADR-066 (REQ-DIO-02)
- [ ] 2.3 Record a withdrawn allocation with its reason and moment when a numbered registration is not written, and expose the series so it reads end to end (REQ-DIO-03)

## 3. The plain-language rendition

- [ ] 3.1 A template declares a plain-language counterpart and its required statements; generation produces both renditions from one generation record, and the plain one names the formal document (REQ-DIO-04)
- [ ] 3.2 Refuse the generation on an unresolved required statement, and regenerate the plain rendition whenever the formal one is regenerated (REQ-DIO-04)
- [ ] 3.3 A machine-drafted plain rendition is a suggestion: not filed and not sent until a named person accepts it, with the acceptance recorded (REQ-DIO-05)

## 4. The download notification

- [ ] 4.1 Register a download listener that records file, version, moment, route and identity, names the link instead of a person on a public link, and enqueues the notification rather than delivering it on the download path (REQ-DIO-06)
- [ ] 4.2 Declare the notification in the `x-openregister-notifications` dialect with steward and watcher recipients, staff only, and collapse repeats inside a declared window (REQ-DIO-07)

## 5. Quality

- [ ] 5.1 PHPUnit inside the container for the numbering, the discharge, the withdrawal, both renditions, the suggestion gate and every download route; 75% on new code (ADR-009); Playwright `tests/e2e/documents-in-and-out.spec.ts`; Dutch and English strings; docs in `docs/features/documents-in-and-out.md` with screenshots
