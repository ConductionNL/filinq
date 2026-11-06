# document-register Specification (delta)

## ADDED Requirements

### Requirement: Each document type declares its required metadata as a register schema (REQ-DREG-06)

Each kind of document filinq stores MUST be a schema in the filinq register
(`lib/Settings/filinq_register.json`) that lists the metadata the kind must
carry in its `required` list and constrains each property's type and
format. OpenRegister MUST refuse an object that lacks a required property.
An administrator MUST be able to change a document type's metadata with
OpenRegister's schema editor; filinq MUST NOT carry a schema editor of its
own.

#### Scenario: A document without its required metadata is refused

- GIVEN the `correspondence` schema requires a recipient
- WHEN a correspondence object without a recipient is saved
- THEN OpenRegister refuses it and names the missing property
- @e2e exclude schema validation; covered by PHPUnit against the register import

#### Scenario: An administrator adds a metadata field

- GIVEN an administrator opens the filinq register in OpenRegister
- WHEN they add a required property to a document type's schema
- THEN new objects of that type must carry it
- @e2e exclude OpenRegister's schema editor is tested in OpenRegister
