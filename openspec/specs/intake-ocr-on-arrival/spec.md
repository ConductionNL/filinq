# intake-ocr-on-arrival Specification

## Purpose
A document that arrives in the intake inbox is read in the background, and
the record says how far the reading got. Matrix row `in-ocr-auto`
(filinq).

## Requirements

### Requirement: An arriving document is queued for reading without anyone starting it (REQ-IOA-001)

When `IntakeService::receive()` stores a new intake document whose file
needs OCR and the `ocr_on_arrival` setting is on, filinq MUST queue a
background reading of that file and MUST set `readingState` to `queued`.
A repeat delivery of the same source reference MUST NOT queue a second
reading. With the setting off, nothing MUST be queued.

Rows: `in-ocr-auto` (filinq matrix)

#### Scenario: A scanned letter is queued as it arrives

- GIVEN the admin left "Read the text of arriving documents" on
- WHEN the scanner channel delivers `brief-gemeente.pdf`, a scan without a text layer
- THEN the intake document is stored with `readingState` `queued` and one reading job is queued for its file
- @e2e exclude background job queueing; covered by PHPUnit on IntakeService::receive with the job list

#### Scenario: The setting is off

- GIVEN the admin turned "Read the text of arriving documents" off
- WHEN a scan arrives
- THEN no reading job is queued and the document has no reading state
- @e2e exclude background job queueing; covered by PHPUnit on IntakeService::receive

### Requirement: The reading writes the text or the reason (REQ-IOA-002)

The reading job MUST set `readingState` to `reading` before it starts.
When text is recognised it MUST store the text in `contentText` and set
`readingState` to `read`. When OCR is off or not installed, when no text
is found, or when the reading throws, it MUST set `readingState` to
`failed` with a `readingError` that says why.

#### Scenario: The text is recognised

- GIVEN a queued intake document whose scan holds printed text
- WHEN the reading job runs
- THEN `contentText` holds the recognised text and `readingState` is `read`
- @e2e exclude background job; covered by PHPUnit on IntakeOcrJob

#### Scenario: Tesseract is not installed

- GIVEN a server without Tesseract
- WHEN the reading job runs
- THEN `readingState` is `failed` and `readingError` is "Text recognition is off or not installed on this server"
- @e2e exclude background job; covered by PHPUnit on IntakeOcrJob

### Requirement: The worklist shows the reading state (REQ-IOA-003)

The intake worklist MUST show each row's reading state as "Waiting to be
read", "Reading", "Text recognised" or "Text could not be read", and for
the last MUST show the reason. A document whose reading failed MUST stay
in the worklist and MUST stay assignable.

#### Scenario: A registrar sees a failed reading

- GIVEN a registrar on `/intake` with a document whose reading failed
- WHEN the worklist loads
- THEN the row says "Text could not be read" with the reason, and the assign action is available
- @e2e exclude label mapping; covered by the unit test on the reading state labels

### Requirement: An image is read as an image (REQ-IOA-004)

`OcrService::processFile()` MUST read an image file with the image path
only and a PDF with the PDF path only.

#### Scenario: A photographed letter is read once

- GIVEN an intake document whose file is `foto-brief.jpg`
- WHEN the reading job runs
- THEN the image path reads it and the PDF path is not called
- @e2e exclude engine branch; covered by PHPUnit on OcrService::processFile
