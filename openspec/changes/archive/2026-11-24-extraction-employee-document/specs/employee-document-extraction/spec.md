# employee-document-extraction Specification (delta)

## Purpose

filinq reads an employment contract or an identity document for the app that asks, each
value with its confidence. It stores nothing.

## ADDED Requirements

### Requirement: An identity document SHALL be read from its machine-readable zone (REQ-EDE-001)

For `identity-document`, filinq SHALL find the machine-readable zone (two lines of 44 or three
lines of 30) in the document text and SHALL answer the last name, the given names, the date
of birth, the document number, the expiry date, the nationality and the personal number. Each
value SHALL carry confidence 0.95 when every check digit of the zone holds and 0.5 when one
does not.

#### Scenario: A passport zone with valid check digits
- GIVEN the text of a passport whose zone reads `P<UTOERIKSSON<<ANNA<MARIA` and `L898902C36UTO7408122F1204159ZE184226B<<<<<10`
- WHEN it is read as an identity document
- THEN the last name is ERIKSSON, the given names ANNA MARIA, the date of birth 1974-08-12, each at confidence 0.95

@e2e exclude A server-side read with no page of its own; `MrzParserTest` asserts it.

#### Scenario: A misread check digit lowers the confidence
- GIVEN the same zone with one digit of the document number misread
- WHEN it is read
- THEN every value carries confidence 0.5

@e2e exclude Server-side; `MrzParserTest::testAMisreadDigitLowersTheConfidence` asserts it.

### Requirement: An employment contract SHALL be read into its terms (REQ-EDE-002)

For `employment-contract`, filinq SHALL answer the employee's names, the start and end date,
the hours per week, the gross monthly or hourly wage and the contract type (`permanent` or
`temporary`) that it finds, at confidence 0.8 for a value after its label and 0.6 for a value
read from a phrase. A term it cannot find SHALL be absent, never guessed.

#### Scenario: A Dutch contract
- GIVEN a contract reading "Werknemer: Jan de Boer", "met ingang van 1 maart 2026", "32 uur per week", "€ 3.250,00 bruto per maand" and "voor bepaalde tijd"
- WHEN it is read as an employment contract
- THEN it answers Jan, de Boer, 2026-03-01, 32, 3250.0 per month and temporary

@e2e exclude Server-side; `EmploymentContractParserTest` asserts it.

### Requirement: The service SHALL answer, not store (REQ-EDE-003)

`EmployeeDocumentExtractionService::extractEmployeeDocument()` SHALL answer
`{status, docType, fields, message}` with status `extracted`, `unreadable` (no text, or nothing
read) or `refused` (another document type), and SHALL store nothing.

#### Scenario: A scan without text
- GIVEN a file that yields no text
- WHEN it is read
- THEN the status is `unreadable` and no values are answered

@e2e exclude Server-side; `EmployeeDocumentExtractionServiceTest::testAFileWithoutTextIsUnreadable` asserts it.
