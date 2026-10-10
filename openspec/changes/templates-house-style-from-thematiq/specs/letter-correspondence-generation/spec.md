# letter-correspondence-generation Specification (delta)

## Purpose

Generated documents follow the house style an organisation sets once in
thematiq. Row `sur-generated-documents` (thematiq matrix, owned by
thematiq).

## ADDED Requirements

### Requirement: filinq keeps a house style per thematiq token set (REQ-HSP-001)

When thematiq is installed, filinq MUST keep one `huisstijl` per thematiq
token set, marked as managed by thematiq, holding the profile's logo, primary
colour, fonts, cover image and footer lines, and MUST refresh it when the
profile changes and only then. Without thematiq filinq MUST behave as before.

#### Scenario: the organisation changes its logo in Theming
- GIVEN a managed house style for the default token set
- WHEN the administrator uploads a new document logo in Theming and a letter is generated
- THEN the managed house style carries the new logo and the letter shows it
- @e2e exclude cross-app sync; covered by PHPUnit with a stub profile and a local run with thematiq

### Requirement: A document without a named house style uses the user's own (REQ-HSP-002)

When a generation request names no `huisstijlId`, filinq MUST apply the
managed house style of the requesting user's token set. A named
`huisstijlId` MUST take precedence.

#### Scenario: two municipalities, two letterheads
- GIVEN the BUCH municipalities with one token set per municipality in thematiq
- WHEN a clerk of Bergen and a clerk of Uitgeest each generate the same letter without naming a house style
- THEN each letter carries its own municipality's logo, colours and footer
- @e2e exclude generation across two apps; covered by PHPUnit and a local run noted in the PR

### Requirement: A managed house style is changed only in thematiq (REQ-HSP-003)

filinq MUST refuse an update of a house style managed by thematiq, with a
message that names Theming as the place to change it. A hand-made house
style MUST stay editable.

#### Scenario: an editor tries to change the managed footer
- GIVEN a template editor and a house style managed by thematiq
- WHEN the editor saves a new footer through filinq
- THEN the save is refused with "This house style follows thematiq; change it in Theming"
- @e2e exclude API refusal; covered by Newman
