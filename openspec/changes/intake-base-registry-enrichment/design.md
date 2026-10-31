# Design: intake-base-registry-enrichment

Kind: code. One service, one decision endpoint, one dialog section.

## Context

Read at development `2088cc1f`, openregister at `ae898b0`.

- `lib/Service/IntakeService.php:95` `receive()` adds
  `partySuggestion` from `PartySuggestionService::suggestFor()` (:148) and
  saves (:151).
- `lib/Service/PartySuggestionService.php:104` reads entity relations
  through `FileEntityStatsService` and maps PERSON, LOCATION, EMAIL and
  PHONE_NUMBER to a party (:69-74); the suggestion carries `confidence`,
  `sourceSpan`, `source: entity-detection` and `written: false`.
  `recordDecision()` (:199) is reached by `IntakeController::decideParty()`
  (`lib/Controller/IntakeController.php:271`, route
  `api/intake/party-decisions`).
- `lib/Service/EntityDetectionService.php:50` lists BSN among the typed
  identifiers always sent for redaction.
- `lib/Service/DocumentObjectServiceResolver.php:64` resolves
  `OCA\OpenRegister\Service\ObjectService` from the container: the pattern
  this change reuses for the providers.
- openregister `lib/Service/Integration/Providers/KvkProvider.php:275`
  `lookupByKvkNumber()` and `BrpPersonProvider.php:314` `lookupByBsn()`,
  routed through the connector sources `kvk` and `brp-haalcentraal`; both
  answer a 4-state cause when a source is missing or down
  (`lib/Controller/PersonLookupController.php` docblock).
- `intakeDefaultRule` and `intakeDocument` as in
  `lib/Settings/filinq_register.json`.

New: `RegistryEnrichmentService`, `registrySuggestion`,
`registryLookupNote`, `brpPurpose`, the decision route.

## Goals / Non-goals

Goals: one registry suggestion per identifier, offered never written;
BRP only under a declared purpose; a lookup failure never touches the
arrival.

Non-goals: BAG, bulk re-enrichment, enrichment outside intake.

## Decisions

### D1. After the party suggestion, in a background job

`receive()` stays fast: it queues a job for the saved document. The job
reads the detected identifiers, runs the lookups and writes
`registrySuggestion`. Alternative considered: looking up inside
`receive()`. Rejected: a slow registry would hold the channel's delivery.

### D2. Providers resolved duck-typed

`RegistryEnrichmentService` resolves `OCA\OpenRegister\Service\Integration\Providers\KvkProvider`
and `...\BrpPersonProvider` from the container and checks
`isEnabled()` before calling. A missing class or disabled provider writes
`registryLookupNote` ("KvK lookup is not configured") and no suggestion.
Alternative considered: calling openregister's HTTP routes. Rejected: a
server-side call inside one instance needs no HTTP round trip and keeps the
BSN out of any URL.

### D3. BRP needs a purpose and a switch

A BSN is looked up only when the matched `intakeDefaultRule.brpPurpose` is
set and the admin setting `intake.brp_lookup_enabled` is true (default
false). The purpose travels with the query. The BSN is checked with the
elfproef first (openregister `lib/Formats/BsnFormat.php`, per ADR-011) and
is never written to a log or to `registryLookupNote`. The suggestion keeps
name and address only.

### D4. The suggestion shape

`registrySuggestion[]`: `{source: kvk|brp, identifier (masked for BSN),
fields: {name, address, kvkNumber?}, retrievedAt, written: false}`. The
registrar's accept writes the fields to the party the document is assigned
with, the same way an accepted party suggestion is written, and sets
`written: true`; dismiss sets `dismissed: true`. Both are kept in the
object's audit trail.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| which purpose applies | declarative, `intakeDefaultRule.brpPurpose` | the organisation states it, per channel and sender |
| lookup | imperative background job | an external call per identifier |
| accept or dismiss | imperative endpoint beside `decideParty()` | a person's decision on one document |
| processing activity | declarative `x-openregister-processing` on `intakeDocument` | the BRP lookup is a processing of personal data and belongs in the register of processing |

## Seed data

One seeded intake document with a KvK registry suggestion (a fictional
company, KvK `12345678`) and one with `registryLookupNote` "KvK lookup is
not configured".

## Risks / trade-offs

- A BRP query without legal ground. Mitigated by the off-by-default switch,
  the per-rule purpose and the audit.
- A registry answer that differs from the letterhead. The suggestion shows
  both, and a person decides.

## Open questions

- The exact purpose codes the BRP audit accepts come from openregister's
  `audit-trail-shipped-and-purpose-bound`; until it lands, the purpose is a
  free text the audit records.
