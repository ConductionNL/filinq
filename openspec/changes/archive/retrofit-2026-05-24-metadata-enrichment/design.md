# Design — retrofit-2026-05-24-metadata-enrichment

**Retrofit change. Tasks describe retroactive annotation, not new implementation work.**

## Goal

Bring 3 already-shipped methods of `LanguageClassifier` under `metadata-enrichment` coverage by drafting one numbered REQ (REQ-META-11) describing the class boundary, then attaching `@spec` annotations.

## Method → Task Map

| File | Method | Task |
|------|--------|------|
| `lib/Service/LanguageClassifier.php` | `detectLanguage` | task-1 |
| `lib/Service/LanguageClassifier.php` | `classifyTopic` | task-1 |
| `lib/Service/LanguageClassifier.php` | `countWordOccurrences` | task-1 |

## Granularity calls

- **All 3 methods collapse to one REQ.** They implement one cohesive behavior (the class-boundary contract for "who owns the word-list scoring algorithm"). Splitting per-method would inflate the spec without buying review clarity — the existing REQ-META-01/03 already cover the abstract behavior of each method.
- **REQ-META-11 is about the class boundary, not algorithm details.** The algorithm itself is already specified in REQ-META-01..03; this REQ specifies *where* it lives and the consumer contract.

## Notable observed-but-suspicious behavior surfaced in REQ Notes

- `TextAnalysisService::countWordOccurrences()` is a byte-identical clone of the LanguageClassifier helper. The coverage scan flagged the wrong direction (it called LanguageClassifier the duplicate); HEAD shows TextAnalysisService is the leftover. TODO note added to REQ-META-11.
- 5-match threshold and 10-keyword vocabularies are constants by design (calibration), not config.
- `countWordOccurrences()` is whitespace-naive — only ASCII space ` ` counts as a word boundary.

## What this change does NOT do

- No code logic changes — observed behavior only.
- Does not consolidate the residual `TextAnalysisService::countWordOccurrences()` duplicate (flagged as TODO in REQ Notes).

## Source

- `openspec/coverage-report.json` generated 2026-05-24
- Cluster: `bucket_2a.metadata-enrichment`
