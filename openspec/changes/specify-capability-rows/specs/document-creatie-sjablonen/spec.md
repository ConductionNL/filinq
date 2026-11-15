# document-creatie-sjablonen Specification (delta)

## Purpose

Marking a document reviewed when its review date has come.

## ADDED Requirements

### Requirement: The owner marks a due document reviewed (REQ-SCR-014)

A document due for review MUST offer a reviewed action to its owner. Marking it reviewed MUST record who reviewed it and when, take it off the due list, and set the next review date from its review interval.

Rows: `wk-review-due` (filinq matrix)

#### Scenario: A beleidsregel is reviewed

- GIVEN a beleidsregel due for review with an interval of twelve months
- WHEN its owner marks it reviewed on 1 March
- THEN it leaves the due list
- AND its next review date is 1 March next year
- @e2e exclude specification only; the change that builds it adds the Playwright test
