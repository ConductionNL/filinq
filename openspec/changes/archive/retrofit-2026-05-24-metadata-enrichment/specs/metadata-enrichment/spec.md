---
retrofit_extensions:
  - REQ-META-11
---

# Metadata Enrichment — Retrofit Delta

Adds REQ-META-11 describing the `LanguageClassifier` class boundary — the canonical owner of the Dutch/English word-list scoring algorithm and the per-topic keyword vocabularies used by REQ-META-01 (language detection) and REQ-META-03 (topic classification).

## ADDED Requirements

### REQ-META-11: Language and Topic Classifier Class Boundary

DocuDesk SHALL implement the language-detection and topic-classification algorithms in a dedicated `LanguageClassifier` service that owns the word-list vocabularies, the minimum-match threshold, and the scoring tiebreaker. Other services (`TextAnalysisService`, `MetadataService`) SHALL consume the classifier via dependency injection; they MUST NOT re-implement the vocabulary or scoring logic.

The class encapsulates three constants — `DUTCH_WORDS` (10 stop-ish high-frequency Dutch words), `ENGLISH_WORDS` (10 high-frequency English words), and `TOPIC_KEYWORDS` (4 topic categories with 6 keywords each: `legal`, `financial`, `medical`, `technical`). The detection helpers share a private `countWordOccurrences()` implementation that counts whitespace-padded `' word '` substrings (so word boundaries are required on both sides, matching what REQ-META-01 / REQ-META-03 already specify abstractly).

#### Scenario: Classifier owns the word lists

- **WHEN** REQ-META-01 / REQ-META-03 are implemented
- **THEN** the word vocabularies live in `LanguageClassifier` constants and are NOT redefined in `TextAnalysisService` or `MetadataService`
- **AND** `TextAnalysisService::detectLanguage()` / `::classifyTopic()` forward to the injected `LanguageClassifier`

#### Scenario: Language detection threshold

- **WHEN** `LanguageClassifier::detectLanguage(text)` is called
- **THEN** it lowercases the text, computes Dutch and English match counts via `countWordOccurrences`, and returns `"nl"` when `dutchCount > englishCount AND dutchCount > 5`
- **AND** otherwise returns `"en"` when `englishCount > 5`
- **AND** otherwise returns `null`

#### Scenario: Topic classification scoring

- **WHEN** `LanguageClassifier::classifyTopic(text)` is called
- **THEN** for each of the four topics it computes the keyword-match count via `countWordOccurrences`
- **AND** returns the topic with the highest non-zero score (`array_search` on the max score)
- **AND** returns `null` if the highest score is `0`

#### Scenario: Word-occurrence helper requires word boundaries

- **WHEN** `countWordOccurrences(text, words)` is called
- **THEN** for each target word it sums `substr_count(text, " word ")` — i.e. the word must be padded by spaces on both sides
- **AND** the running total across the list is returned
- **AND** a substring match inside a longer word (e.g. `"the"` inside `"theater"`) is NOT counted

#### Notes

- The class is stateless and has no constructor dependencies; it can be resolved either via DI or instantiated directly (used as a unit-test seam).
- `TextAnalysisService` still defines its own public `countWordOccurrences()` with byte-identical logic — that is a residual duplicate not yet consolidated. TODO: remove `TextAnalysisService::countWordOccurrences()` once no external caller relies on it (the coverage scan flagged `LanguageClassifier::countWordOccurrences` as the duplicate, but at HEAD the situation has inverted — the classifier owns the logic, the analyzer is the leftover).
- The 5-match threshold and the 10-keyword vocabularies are constants, not config — by design (REQ-META-04 calibration). Changing them requires a code change + spec revision.
- `countWordOccurrences()` is byte-naive — non-ASCII whitespace (NBSP, tabs) is not treated as a boundary. Real-world DocuDesk text is whitespace-normalised earlier in the pipeline; if that ever changes, detection accuracy will drop.
