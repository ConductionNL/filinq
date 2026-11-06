# Design: intake-worklist-deadline-and-duplicates

Kind: code. Six properties, two repository reads, one worklist.

## Context

Read at development `2088cc1f`.

- `lib/Service/IntakeService.php:95` `receive()`: channel check, the
  `sourceRef` skip (:104-117), the document array (:119-127), the defaults
  stamp (:141, `IntakeDefaultRuleService::stamp()`), the party suggestion
  (:148), and `IntakeRepository::save()` (:151).
- `lib/Service/IntakeRepository.php`: `findWaiting()` (:106, status
  `received`), `findByStatus()` (:129), `findByFile()` (:161),
  `findBySourceRef()` (:188), `save()` (:251). Statuses at :66-116.
- `intakeDocument` in `lib/Settings/filinq_register.json`
  (`components.schemas.intakeDocument`): `receivedAt`, `file`,
  `sourceRef`, `status` with transitions assign, reject, detach, reassign.
- `intakeDefaultRule`: `channel`, `senderPattern`, `stamps`, `order`,
  `active`; matched by `IntakeDefaultRuleService::match()` (:87).
- `documentRegistration` has `direction` and `answers`, from open change
  `documents-in-and-out-of-the-building` (REQ-DIO-02: the discharge of an
  inbound entry is read from the link, never written as a status).
- `src/views/intake/IntakeIndex.vue`: `CnIndexPage` with a mode switch
  (:37-49, waiting and detached), columns at :172, row actions at :62.
- openregister computed fields: a property with `computed.expression`
  (Twig, `date` and `date_modify` filters) and `evaluateOn: read` is
  evaluated in `RenderObject` and never stored
  (`openspec/specs/computed-fields/spec.md` in openregister at ae898b0).

New: every property named below, `findOverdue()`, `findByContentHash()`,
the Overdue mode.

## Goals / Non-goals

Goals: a deadline on every waiting document that a rule sets and a person
can change; an overdue list; an exact-duplicate warning that never refuses
an arrival.

Non-goals: fuzzy duplicates, overdue notifications, working-day calendars
beyond weekends.

## Decisions

### D1. The deadline is stored, the countdown is computed

`handlingDeadline` (date) is stored so the Overdue list can filter on it in
the database. `daysLeft` is a read-time computed field
(`{{ ((handlingDeadline|date('U')) - ("now"|date('U'))) / 86400 }}`,
rounded), so it is right on every read without a job. Alternative
considered: a nightly job that writes an `overdue` flag. Rejected: a stored
flag is wrong between runs.

### D2. The rule sets the period, the person may change it

`intakeDefaultRule.handlingDays` (integer, working days, weekends skipped)
sets the deadline at arrival. With no matching rule, the admin setting
`intake.default_handling_days` applies (default 10). A registrar may change
the deadline; the change needs a `deadlineReason` and is kept in the
object's audit trail. Alternative considered: a deadline per record type.
Rejected: at arrival the record type is not known yet.

### D3. What handled means is declared

`intakeDefaultRule.handledWhen` is `assigned` (default) or `answered`. For
`answered`, the document counts as handled when an outbound
`documentRegistration` answers the inbound registration of this document.
A rejected document is always handled. `findOverdue()` returns documents
whose deadline has passed and which are not handled by their rule.

### D4. Exact duplicates by content hash

`IntakeService::receive()` computes SHA-256 of the file bytes into
`contentHash`. `findByContentHash()` looks for an earlier intake document
with the same hash that is not rejected. When one exists the newcomer gets
`duplicateOf` = its uuid. The arrival is never refused and the
`sourceRef` skip runs first, unchanged. Alternatives considered: refusing a
duplicate, as Paperless-ngx can (rejected: two identical files may belong
to two cases, and the registrar decides), and openregister's
`DuplicateDetectionService` with an `x-openregister-dedup` rule (its
`openspec/specs/duplicate-detection/spec.md`), which scores candidate pairs
across a schema for a data steward but does not run at arrival. filinq also
declares `x-openregister-dedup` with an exact match on `contentHash`, so
openregister's steward view lists the same pairs (ADR-011: reuse, do not
rebuild).

### D5. Rejecting a duplicate uses the existing reject

The row action "Reject as duplicate" fills the reject reason with
"Duplicate of" and the first document's subject and uses the existing
reject transition. No new transition.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| days left | declarative, read-time computed field | always current, no job |
| deadline at arrival | imperative, in `receive()` from the matched rule | working-day arithmetic over the rule |
| duplicate lookup | imperative, in `receive()` | a query against other objects at the moment of arrival |
| duplicate pairs for a steward | declarative `x-openregister-dedup` on `contentHash` | openregister's duplicate view finds them without code |
| handled | declarative rule field, read in `findOverdue()` | the organisation decides what handled means |

## Seed data

The seeded mail rule gets `handlingDays: 10` and `handledWhen: assigned`.
Seed three intake documents: one due in three days, one five days overdue,
and one whose `duplicateOf` names another seeded document.

## Risks / trade-offs

- Hashing a large scan at arrival costs a read of the file. The file is
  already read for the party suggestion, so the hash reuses those bytes.
- A deadline counted in working days ignores public holidays. The rule can
  add days; a holiday calendar is out of scope.

## Open questions

- None blocking. The default of ten working days follows the common
  municipal norm for acknowledging post and can be changed per rule.
