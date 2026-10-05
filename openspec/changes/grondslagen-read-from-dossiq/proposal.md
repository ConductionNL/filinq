---
kind: code
depends_on: []
---

# Proposal: grondslagen-read-from-dossiq

## Why

There are four lists of Woo art. 5 refusal grounds in the fleet and they
disagree (opencatalogi 15, filinq 19, dossiq's spec 12, dossiq's code 10).
filinq's is `grondslagen-woo-art5` (7/7, built): 19 `base` objects in the
`dossier` register, slugs `art-5-1-1-a` to `art-5-2-2`, read by the pickers
through `src/services/bases.js` and by the backend through
`BaseLabelResolver`, `LegalBasisCatalog`, `LegalBasisProposalService`
(config `filinq.grondslagen.entity_type_bases`), `DossierSummaryDataService`
and `DossierEntityCollector`.

Ruben's decision **D3** of 2026-10-05: the refusal grounds live in dossiq, one
list, seeded from filinq's 19 after the count is settled against the law;
opencatalogi's constant and filinq's copy are retired and both read dossiq's.
Decision **D12**: Woo requests require dossiq with no fallback, but the
grounds keep a read-only fallback for redaction only, because filinq redacts
without any request involved. When dossiq is absent, filinq uses a read-only
statutory snapshot: grounds can be picked and attached, not edited, and the
admin page says where they would come from.

This change closes no row itself. It supports 12.29 ("Every change to the list
of exception grounds is itself recorded"; our column `no`) and 13.28 ("The list
of exception grounds is a controlled list an administrator maintains, and an
entry can sit under another"; `partial`), which
`dossiq/woo-refusal-grounds-list` closes. Build plan: new supporting change,
wave 2, size S, after `dossiq/woo-refusal-grounds-list`.

## What changes

1. One resolver. `OCA\Filinq\Service\Grounds\RefusalGroundsResolver` with
   `list(bool $includeRetired = false): array{source, version, grounds}` and
   `byCode(string $code): ?array`. It calls dossiq's
   `OCA\Dossiq\Woo\WooRefusalGrounds::list()` and `::byCode()` when dossiq is
   installed and enabled and the class exists. When it is not, or when dossiq
   throws `WooRefusalGroundsUnavailable`, it reads the vendored snapshot
   `lib/Settings/woo-refusal-grounds.snapshot.json` and marks the answer
   `source: snapshot`. It never answers an empty list as if no grounds
   existed.
2. Every reader goes through it: the pickers (through a new
   `GET /api/grounds`), `BaseLabelResolver`, `LegalBasisCatalog`,
   `LegalBasisProposalService`, the dossier summary and the entity collector.
   The pickers store a ground's `code`, not a `base` slug.
3. Existing references are re-pointed. A repair step maps every stored `base`
   slug (on dossiers, entity relations, publication prohibitions and consents,
   and the `entity_type_bases` config) to dossiq's code through a mapping file
   taken from dossiq's settled mapping. It maps only where the mapping is one
   to one, reports the rest by object and slug, and never guesses.
4. filinq's own list is retired. The seed no longer creates the 19 Woo `base`
   objects. The existing objects stay readable for the references the repair
   could not map, and the `base` schema refuses a new Woo ground.
5. The admin page says where the grounds come from: "dossiq" with a link to
   dossiq's settings page, or "read-only snapshot, version X: install dossiq
   to maintain this list". With the snapshot nothing is editable.

## What does not change

- How a redaction cites a ground: still per entity, through the same pickers.
  The stored value changes from slug to code.
- Redaction works without dossiq. Nothing blocks a redaction or a publication
  for want of dossiq (D3, D12).

## Cross-app contract

dossiq side, fixed in `dossiq/woo-refusal-grounds-list` design D-3:
`OCA\Dossiq\Woo\WooRefusalGrounds::list(bool $includeRetired = false): array`
and `byCode(string $code): ?array`, each ground with the keys `id, code,
article, paragraph, letter, label, description, parent, status, legalSource`;
the read runs as the system; an unreadable register throws
`WooRefusalGroundsUnavailable`. The snapshot is
`{version, generatedAt, source: 'dossiq', grounds: [same keys]}`, shipped by
dossiq as `lib/Settings/woo-refusal-grounds.snapshot.json`. filinq tests its
side against a copy of that shape; dossiq tests its side in its own change.

## App absent

- dossiq absent, disabled, or its class not loadable: the snapshot answers,
  read-only, marked `source: snapshot`.
- dossiq present but throwing `WooRefusalGroundsUnavailable`: the snapshot
  answers, and the admin page says dossiq's list could not be read.
- Snapshot missing or unreadable: the resolver throws, the picker shows an
  error naming the cause, and a redaction can still be committed without a
  ground only where the existing rules allow that today. It never invents a
  list.

## Fail closed

- A code that resolves to nothing is shown as "unknown ground" with the code,
  never silently dropped, and the repair reports it.
- With the snapshot, every write path to the grounds is refused.

## Dependencies and wave

- `dossiq/woo-refusal-grounds-list` (wave 1): the class, the codes, the
  snapshot and the settled mapping from filinq's 19 slugs. Its task 1 settles
  the law first; this change cannot ship its mapping file before that.
- `filinq/woo-request-workflow` uses the same resolver class. Whichever lands
  first creates it.
- Wave 2. Implements decisions D3 and D12 for filinq.
