# refusal-grounds-source Specification (delta)

---
status: proposed
---

## Purpose

filinq reads the Woo refusal grounds from dossiq's one list, falls back to a
read-only snapshot for redaction only when dossiq is absent, and retires its
own copy. Decisions D3 and D12. Supports Woo rows 12.29 and 13.28.

## ADDED Requirements

### Requirement: One resolver reads the grounds from dossiq, or from a read-only snapshot (REQ-GRD-001)

filinq SHALL read refusal grounds only through
`OCA\Filinq\Service\Grounds\RefusalGroundsResolver`, whose `list()` answers
`source` (`dossiq` or `snapshot`), `version` and `grounds`, each ground with
the keys of dossiq's `WooRefusalGrounds::list()`. The resolver SHALL call
dossiq when dossiq is installed and enabled and
`OCA\Dossiq\Woo\WooRefusalGrounds` exists, and SHALL read
`lib/Settings/woo-refusal-grounds.snapshot.json` when it is not or when dossiq
throws `WooRefusalGroundsUnavailable`. It SHALL NOT answer an empty list as a
result; an unreadable snapshot SHALL throw.

#### Scenario: dossiq installed
- GIVEN dossiq installed with ground `5.1.2.e` active
- WHEN a reviewer opens the grondslag picker in the review table
- THEN the picker lists dossiq's grounds by label and article, and choosing `5.1.2.e` stores the code `5.1.2.e`
- @e2e tests/e2e/spec-coverage/grondslagen-read-from-dossiq.spec.ts

#### Scenario: dossiq absent
- GIVEN dossiq not installed
- WHEN the picker loads
- THEN it lists the snapshot's grounds, marked read-only with the snapshot version, and the admin page says to install dossiq to maintain the list
- @e2e exclude an app-absent path; covered by PHPUnit `RefusalGroundsResolverTest::testWithoutDossiqTheSnapshotAnswers`

#### Scenario: dossiq cannot read its register
- GIVEN dossiq installed and `list()` throwing `WooRefusalGroundsUnavailable`
- WHEN the resolver is asked
- THEN it answers the snapshot with `source: snapshot`
- @e2e exclude a failure path; covered by PHPUnit `RefusalGroundsResolverTest::testAnUnavailableDossiqFallsBackToTheSnapshot`

### Requirement: Every grounds reader in filinq goes through the resolver (REQ-GRD-002)

The pickers (through `GET /api/grounds`), `BaseLabelResolver`,
`LegalBasisCatalog`, `LegalBasisProposalService`, `DossierSummaryDataService`
and `DossierEntityCollector` SHALL read grounds through the resolver and SHALL
NOT read the `base` schema for Woo grounds. A stored code that resolves to
nothing SHALL be shown as "unknown ground" with the code.

#### Scenario: the summary report prints dossiq's label
- GIVEN an entity redacted on `5.1.2.e` and dossiq's label for it changed by an administrator
- WHEN the grondslagen summary is rendered
- THEN it prints the changed label
- @e2e exclude a rendered report; covered by PHPUnit `BaseLabelResolverTest::testLabelsComeFromTheResolver`

#### Scenario: no reader bypasses the resolver
- GIVEN the source tree
- WHEN the test suite runs
- THEN `GroundsReadersTest::testNoClassReadsWooGroundsFromTheBaseSchema` fails on any class outside the resolver that queries the `base` schema for Woo grounds
- @e2e exclude a source scan; covered by PHPUnit

### Requirement: Existing references are re-pointed to dossiq's codes, without guessing (REQ-GRD-003)

A repair step SHALL map every stored Woo `base` slug (dossier `bases[]`,
entity relation bases, publication prohibition and consent `bases`, and
`filinq.grondslagen.entity_type_bases`) to a dossiq code through
`lib/Settings/woo-grounds-legacy-map.json`, which records for each of filinq's
19 slugs either one code or `unmapped`. It SHALL rewrite only one-to-one
mappings, SHALL report every reference it left with the object and the slug,
SHALL be idempotent, and SHALL NOT delete a `base` object. After the repair the
seed SHALL NOT create Woo `base` objects, and a new Woo ground SHALL be
refused on the `base` schema.

#### Scenario: a dossier's grounds are re-pointed
- GIVEN a dossier with `bases: ["art-5-1-2-e"]` and the map giving `5.1.2.e`
- WHEN the repair runs
- THEN the dossier holds `bases: ["5.1.2.e"]`, and a second run changes nothing
- @e2e exclude a repair step; covered by PHPUnit `RepointWooGroundsTest::testAOneToOneSlugIsRewritten`

#### Scenario: an unmapped slug is reported, not guessed
- GIVEN an entity relation citing a slug the map marks `unmapped`
- WHEN the repair runs
- THEN the reference is unchanged and the report names the object and the slug
- @e2e exclude a repair step; covered by PHPUnit `RepointWooGroundsTest::testAnUnmappedSlugIsReported`

### Requirement: The snapshot is read-only and the admin says where the list lives (REQ-GRD-004)

When the resolver answers from the snapshot, every write to the grounds in
filinq SHALL be refused, and the admin settings SHALL show the source, the
snapshot version, and that dossiq maintains the list. When dossiq answers, the
admin settings SHALL link to dossiq's grounds page and SHALL offer no editor
of its own.

#### Scenario: no editing without dossiq
- GIVEN dossiq absent
- WHEN an administrator opens filinq's grondslagen settings
- THEN the list is read-only, the version is shown, and no add or edit action exists
- @e2e tests/e2e/spec-coverage/grondslagen-read-from-dossiq.spec.ts
