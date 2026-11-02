# pdf-page-stamping Specification (delta)

## Purpose

filinq stamps a text on every page of a PDF for a sibling app that asks,
starting with decidiq's personal watermark on confidential meeting papers.
Row `age-19` (decidiq matrix, owned by decidiq).

## ADDED Requirements

### Requirement: filinq stamps a text on every page of a PDF (REQ-PST-001)

filinq MUST stamp a given text on every page of a PDF, across the page, at
its foot, or both, MUST keep each page's size and orientation, and MUST
return a new PDF without changing the input. It MUST refuse a file that is
not a PDF, an encrypted PDF, and a file larger than the configured limit,
each with its own code.

#### Scenario: a three-page paper is stamped
- GIVEN a three-page PDF whose second page is landscape
- WHEN it is stamped with "J. de Vries, 28-09-2026 14:05, Vertrouwelijk" across the page and at the foot
- THEN the result has three pages, the second still landscape, and the text is on each page
- @e2e exclude backend service without a screen; covered by PHPUnit with a fixture PDF

#### Scenario: an encrypted paper
- GIVEN an encrypted PDF
- WHEN it is stamped
- THEN the refusal code is `encrypted` and no PDF is returned
- @e2e exclude backend service; covered by PHPUnit

### Requirement: A sibling app asks for a stamp through a typed command (REQ-PST-002)

filinq MUST offer `OCA\Filinq\Event\DocumentStampRequestedEvent` carrying
the calling app, the PDF content, the text and the placement. Its listener
MUST write the stamped PDF into the result slot and mark the event handled,
or mark it with a refusal code and reason, and MUST NOT throw to the caller
or store the stamped copy.

#### Scenario: a council member opens a confidential paper in decidiq
- GIVEN a closed meeting whose papers must be stamped, and member J. de Vries who may read the agenda item
- WHEN he opens a paper and decidiq dispatches the event with his name, the date and "Vertrouwelijk"
- THEN decidiq receives the stamped PDF and serves it, with his name and the date on every page
- @e2e exclude cross-app command; covered by PHPUnit with the real event and a local run with decidiq
