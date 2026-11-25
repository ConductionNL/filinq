# cv-profile-extraction Specification (delta)

## Purpose

filinq reads a candidate's profile from a CV file for the app that asks: the education
level, the years of experience and the competences the caller lists. It stores nothing.

## ADDED Requirements

### Requirement: A CV SHALL be read into a profile (REQ-CVP-001)

`ProfileExtractionService::extractProfile()` SHALL read the text of the given file and
answer the highest education level found on the Dutch qualification scale (1 to 8), the
years of experience in the periods listed under a work-experience heading (overlapping
periods counted once, an open period up to today), and those of the caller's competence
codes that occur in the text as words. A part it cannot find SHALL be null (level, years)
or empty (competences), never guessed.

#### Scenario: A CV with a master's degree and two jobs
- GIVEN a CV with "Master Bedrijfskunde" under Opleiding and "2018 - 2021" and "2021 - heden" under Werkervaring
- WHEN a caller extracts its profile with the codes "loonheffing" and "excel"
- THEN the education level is 7, the years run from 2018 to today, and only the codes found in the text are returned

@e2e exclude A server-side read with no page of its own; `CvProfileParserTest` and `ProfileExtractionServiceTest` assert it.

#### Scenario: A CV without a work-experience heading
- GIVEN a CV that lists years but has no work-experience heading
- WHEN its profile is extracted
- THEN the years of experience are null

@e2e exclude Server-side heuristic; `CvProfileParserTest::testYearsWithoutAnExperienceHeadingAreUnknown` asserts it.

### Requirement: A file without text SHALL be answered as unreadable (REQ-CVP-002)

When the file is missing, not readable by the session user, or yields no text, the answer
SHALL be status `unreadable` with a message, and no profile.

#### Scenario: A scanned CV without OCR
- GIVEN a file that yields no text
- WHEN its profile is extracted
- THEN the status is `unreadable` and the message says the text could not be read

@e2e exclude Server-side answer; `ProfileExtractionServiceTest::testAFileWithoutTextIsUnreadable` asserts it.
