# redaction-output-guarantee Specification (delta)

## ADDED Requirements

### Requirement: The person who anonymises differs from the person who checked (REQ-RWB-09)

When the four-eyes setting applies to a run, filinq SHALL refuse to write a redacted copy if the acting user of the run is the user named in the document's review mark (`checkedBy`). The setting applies to every run when it is `always`, to Woo runs only when it is `woo`, and to no run when it is `off`. A run is a Woo run when its caller passes `wooCase: true` or when it runs with scope `dossier`. The comparison SHALL be enforced in the service, next to the review gate of REQ-RWB-02, so the API, the batch path, the folder job and cross-app callers are all covered. A refusal SHALL answer 409 with reason `same_person` and a message that asks for a colleague to anonymise. A run whose acting user is empty SHALL be refused with reason `anonymiser_unknown` while the setting applies.

#### Scenario: One officer checks and anonymises a Woo document

- **GIVEN** the setting is `woo` and Fenna marked the findings of a document in a Woo case as checked
- **WHEN** Fenna asks to anonymise that document
- **THEN** the answer is 409 with reason `same_person` and no redacted copy is written

#### Scenario: A colleague anonymises after the check

- **GIVEN** the setting is `woo` and Fenna marked the findings of a document in a Woo case as checked
- **WHEN** Daan asks to anonymise that document
- **THEN** the redacted copy is written

#### Scenario: Outside a Woo case one officer may do both

- **GIVEN** the setting is `woo` and Fenna checked a document that is not in a Woo case
- **WHEN** Fenna asks to anonymise it
- **THEN** the redacted copy is written

#### Scenario: Always means every document

- **GIVEN** the setting is `always` and Fenna checked a document that is not in a Woo case
- **WHEN** Fenna asks to anonymise it
- **THEN** the answer is 409 with reason `same_person`

#### Scenario: A batch run skips the documents its starter checked

- **GIVEN** the setting is `woo`, a dossier run started by Fenna, and two documents of which Fenna checked one and Daan the other
- **WHEN** the batch runs
- **THEN** only the document Daan checked is written, and the other is reported with reason `same_person`
- @e2e exclude batch path; covered by PHPUnit on the batch service

#### Scenario: A run without a name is refused

- **GIVEN** the setting is `always` and a run with no acting user
- **WHEN** the run reaches the review gate
- **THEN** the answer is 409 with reason `anonymiser_unknown` and nothing is written
- @e2e exclude no screen starts a run without a user; covered by PHPUnit on the gate
