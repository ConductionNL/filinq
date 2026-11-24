---
kind: code
depends_on: []
---

# Proposal: extraction-cv-profile

## Decision

humaniq's `hiring-cv-profile-extraction` waits on a profile extraction contract from filinq
that was never written down (its draft, `for-ruben/filinq-cv-profile-extraction.md`, does
not exist in either repo). Decided under the delegation of 22 Nov 2026, from humaniq's
`candidate-assessment` spec (REQ-CAS-004) and its `JobApplication` profile fields:

- **One read-only service call, no stored object.** `ProfileExtractionService::extractProfile(array $data, string $requestedBy)`
  reads the file and answers the profile. The caller (humaniq) decides what to write and
  where, so filinq holds no copy of a candidate's CV data. A stored `financialExtraction`-style
  object would keep personal data in a second app for no use.
- **Heuristics, no model.** The education level, the years of experience and the competences
  are read with fixed rules over the text (like the KvK and IBAN extractors). A model call
  can be added later behind the same method; personal data never leaves the instance now.
- **The caller names the competences.** Competence codes are an HR vocabulary
  (`EmployeeCompetence.competenceCode`, a vacancy's required and preferred competences).
  filinq finds the codes the caller lists and invents none.

## Why

HR reads a candidate's education, experience and competences off the CV by hand before
humaniq can score the application against the vacancy (humaniq row `dm-vacancy-matching`).
filinq already turns a stored file into text (`DocumentTextSource`), but has no profile
reader.

## What Changes

- `Service\Extraction\CvProfileParser`: the highest education level on the Dutch
  qualification scale (NLQF 1 to 8), the years of experience under a work-experience heading,
  and the listed competence codes found in the text.
- `Service\ProfileExtractionService::extractProfile()`: resolves the file's text and answers
  `{status, educationLevel, experienceYears, competenceCodes, message}`; `unreadable` when the
  file has no text.

## Cross-app

- **humaniq** `hiring-cv-profile-extraction` 1.2 calls it duck-typed, by class name, and fills
  only an empty profile.
