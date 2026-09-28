# document-creatie-sjablonen Specification (delta)

## ADDED Requirements

### Requirement: A decision letter carries its legal basis and objection deadline (REQ-DLB-001)

Generation MUST add a `bezwaar` object to the template context when the data
carries a decision date, with the term in weeks (app setting
`bezwaar_termijn_weken`, default 6) and the last day to object, counted from
the day after the decision date, with a deadline on a Saturday or Sunday moved
to the Monday. Without a decision date the context MUST NOT carry `bezwaar` and
the generation warnings MUST say that no decision date was found. The merge
field dialog MUST offer the legal basis and objection deadline fields.

#### Scenario: The letter states the last day to object

- GIVEN a decision letter template using `{{ bezwaar.uiterlijk }}` and data with `besluitDatum` 2026-09-01
- WHEN the letter is generated
- THEN it reads 13-10-2026 as the last day to object
- @e2e exclude date arithmetic with no UI surface, covered by PHPUnit (tests/unit/Service/ObjectionTermCalculatorTest.php)

#### Scenario: No decision date is a warning, not a wrong date

- GIVEN data without a decision date
- WHEN the letter is generated
- THEN the context has no `bezwaar` and the warnings name the missing decision date
- @e2e exclude generation warning, covered by PHPUnit (tests/unit/Service/DocumentServiceTest.php)

#### Scenario: The author picks the fields instead of typing them

- GIVEN an author in the template editor
- WHEN they open the merge field dialog
- THEN the legal basis and the objection deadline are in the list
- @e2e tests/e2e/spec-coverage/templates.spec.ts
