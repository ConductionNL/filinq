# Retrofit — metadata-enrichment

Describes observed behavior of 3 methods under the `metadata-enrichment` capability as 1 new REQ (REQ-META-11). The existing REQs cover the abstract "detect language / classify topic" outcomes (REQ-META-01..03) without specifying that the algorithm + vocabularies live in a dedicated `LanguageClassifier` class. This retrofit closes that gap.

## Affected code units

- `lib/Service/LanguageClassifier.php` — `detectLanguage`, `classifyTopic`, `countWordOccurrences`

## Approach

- Read `LanguageClassifier` and its caller (`TextAnalysisService`, which delegates `detectLanguage` and `classifyTopic`) to confirm that the classifier is the canonical owner of the word-list/threshold algorithm and the topic-keyword vocabularies.
- Note that the coverage report described the classifier as a "duplicate" of `TextAnalysisService` — at HEAD that framing is stale. `TextAnalysisService::detectLanguage()` and `::classifyTopic()` are thin forwarders to `LanguageClassifier`; the duplication has been collapsed by extraction.
- Draft a single REQ describing the class boundary (LanguageClassifier owns the vocabularies, thresholds, and scoring; `countWordOccurrences` is a private helper).
- Surface in Notes: TextAnalysisService still has its own `countWordOccurrences` clone (public) — that is observed-but-suspicious code outside this cluster but worth flagging here.

Source: `openspec/coverage-report.json` generated 2026-05-24. See [retrofit playbook](../../../.github/docs/claude/retrofit.md).
