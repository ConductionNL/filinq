---
kind: code
depends_on: [inbound-documents-and-the-worklist]
---

# Proposal: intake-base-registry-enrichment

Matrix row `in-basisreg` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September
2026.

## Why

A letter from a company arrives with its KvK number in the letterhead. The
registrar reads the number, looks the company up in the Handelsregister in
another tab, and types its name and address onto the record. A letter
from a resident carries a BSN; the same happens against the BRP.

filinq already reads the number out of the document. OpenRegister's entity
detection finds `BSN` and KvK-type identifiers
(`lib/Service/EntityDetectionService.php:50`), and
`PartySuggestionService::suggestFor()`
(`lib/Service/PartySuggestionService.php:104`) turns detected names and
addresses into a party suggestion at arrival. What it never does is ask the
base registry what the number belongs to. `MetadataService`
(`lib/Service/MetadataService.php`) is text analysis only; a search for
`BAG`, `BRP`, `KVK` or `basisregist*` in it finds nothing.

The lookups themselves exist in OpenRegister: `KvkProvider::lookupByKvkNumber()`
and `BrpPersonProvider::lookupByBsn()`, routed through the connector sources
integriq holds. This change is filinq's half: ask them at arrival and offer
the answer to the registrar.

The row sits in intake, filinq's core area.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-basisreg` | Fill in a document's metadata from the national base registries. | no: metadata enrichment is text analysis only, the `metadata#enrich` route has no caller |

### Demand

None recorded.

### Competitors rated yes

None. The row is competitor-derived from Visma Circle's "Basisregistratie
enrichment" (sourceRef) and built for the core-area rule.

## What changes

- At arrival, after the party suggestion, filinq reads the identifiers
  detected on the file: KvK numbers and BSNs.
- A KvK number is looked up in the Handelsregister. The company name,
  visiting address and KvK number become a registry suggestion on the
  intake document.
- A BSN is looked up in the BRP only when the matching intake default rule
  names a purpose (doelbinding) and an admin switched BRP lookups on. The
  suggestion carries name and address, never the full person record.
- The registrar sees the suggestion beside the party suggestion in the
  worklist and accepts, corrects or dismisses it. Nothing is written to a
  record without that step.
- A lookup that cannot run (no source configured, registry down, number
  fails its check) says why on the document and leaves the arrival alone.

## Capabilities

### New capabilities

- `intake-registry-enrichment`: registry suggestions for an arriving
  document from the Handelsregister and, under a declared purpose, the BRP.

### Modified capabilities

None. The party suggestion of `inbound-documents-and-the-worklist` is left
as it is; the registry suggestion sits beside it.

## Impact

- `lib/Settings/filinq_register.json`: `intakeDocument` gains
  `registrySuggestion` (array) and `registryLookupNote` (string);
  `intakeDefaultRule` gains `brpPurpose` (string). Version bump and seed.
- New `lib/Service/RegistryEnrichmentService.php`, resolving the
  OpenRegister providers duck-typed through the container, as
  `DocumentObjectServiceResolver` resolves `ObjectService`.
- `lib/Controller/IntakeController.php`: accept or dismiss a registry
  suggestion, next to `decideParty()`.
- `src/views/intake/IntakeIndex.vue`: the registry suggestion in the
  assign dialog with accept, correct and dismiss.
- Admin settings: a switch for BRP lookups, off by default.

## Cross-app dependencies

- openregister: provides `KvkProvider` and `BrpPersonProvider` (present at
  ae898b0). A BAG address lookup does not exist there; until it does, an
  address is suggested as read from the document, not verified.
- openregister: the purpose-bound audit of BRP queries (its open change
  `audit-trail-shipped-and-purpose-bound`) must accept the purpose filinq
  passes.
- integriq: holds the `kvk` and `brp-haalcentraal` sources with their
  credentials. filinq never stores a registry credential.

## Out of scope

- BAG address verification (openregister's half, above).
- Writing registry data onto a record without a person.
- Enrichment of documents that are not in the intake inbox.
