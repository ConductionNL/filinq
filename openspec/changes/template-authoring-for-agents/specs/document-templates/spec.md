# Document templates for agents

**Status**: in-progress
**OpenSpec changes**: template-authoring-for-agents

Templates an agent can find, author, and apply to the document a user already
has open — with the numbers coming from data rather than from the model.

## Requirement: a template can be applied to an existing document in place

`applyTemplateToDocument` SHALL render a template into a file that already
exists, replacing its body, and SHALL do so through the same lock, version
precondition and audit mark that `editDocument` uses.

It SHALL NOT create a new file. `generateCorrespondence` remains the tool that
creates a new file for a recipient; the two SHALL state that difference in the
first sentence of their descriptions, because that is where a model choosing
between them reads.

#### Scenario: the document on screen becomes a quotation
- **GIVEN** a user has an empty `.docx` open
- **WHEN** an agent applies the "Offerte" template to that file
- **THEN** that file's body is the rendered quotation
- **AND** no second file is created

#### Scenario: an open editor is not clobbered
- **GIVEN** the file is locked by an editor session
- **WHEN** an agent applies a template to it
- **THEN** the call is refused
- **AND** the file is unchanged

## Requirement: an incomplete render writes nothing

When any declared placeholder cannot be resolved, the call SHALL return the
unresolved placeholders with their expected sources, and SHALL NOT write to the
file.

A rendered document with an unfilled placeholder looks finished and is wrong in
the direction nobody checks — a missing total reads as a formatting slip rather
than as missing data.

#### Scenario: a missing amount does not reach the file
- **GIVEN** the "Offerte" template declares a required `bedrag`
- **WHEN** a template is applied with no value for `bedrag`
- **THEN** the response names `bedrag` as unresolved
- **AND** the file's bytes are unchanged

## Requirement: templates declare their placeholders

A template SHALL carry a declaration for each placeholder: key, description,
source (`client`, `lead`, `product`, `computed`, `literal`, `user`) and whether
it is required. `describePlaceholders` SHALL return that contract.

Placeholders SHALL NOT be inferred by scanning the body for bracketed text: any
bracketed prose would silently become a placeholder, and the agent would have no
way to learn where a value is meant to come from.

Creating or updating a template whose body references an undeclared placeholder
SHALL be rejected.

#### Scenario: the contract is readable before rendering
- **WHEN** an agent asks a template to describe its placeholders
- **THEN** it receives each key with its source and whether it is required

## Requirement: pricing comes from a rate card, and absence is reported

`product.search` SHALL resolve free text to candidate products with their unit
and unit price, and SHALL return every candidate rather than selecting one.

Zero matches SHALL be returned as zero matches. The tool description SHALL state
that the agent must then ask the user, and MUST NOT estimate.

An hourly rate is exactly the plausible-sounding fact a model supplies
unprompted; this requirement exists to make that unnecessary and visibly wrong.

#### Scenario: an ambiguous service is not silently priced
- **GIVEN** "Development (senior)" at €125/hour and "Development (medior)" at €95/hour
- **WHEN** an agent searches products for "dev work"
- **THEN** both candidates are returned with their rates
- **AND** neither is presented as the answer

#### Scenario: an unknown service is not invented
- **WHEN** an agent searches products for something with no match
- **THEN** zero candidates are returned
- **AND** the agent asks the user rather than quoting a figure
