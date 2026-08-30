## ADDED Requirements

### Requirement: An embedded chart MUST leave the package internally consistent

Embedding a chart MUST write the chart part, declare its content type, add a
relationship, and reference that relationship from a drawing in the body. The
relationship id in the relationships part and in the drawing MUST be identical.

None of these is optional and none degrades gracefully. A missing content-type
override, a dangling relationship or a mismatched id produces a document the suite
**refuses to open** — not a document with a missing chart.

#### Scenario: The relationship id matches between the rels and the drawing

- **GIVEN** a chart embedded into a document
- **WHEN** the package is inspected
- **THEN** the relationships part MUST declare the id
- **AND** the drawing in the body MUST reference the same id

#### Scenario: The content type is declared

- **GIVEN** a chart embedded into a document
- **WHEN** `[Content_Types].xml` is inspected
- **THEN** it MUST carry an Override naming the chart part and the DrawingML chart content type

### Requirement: An existing relationship MUST never be overwritten

The relationship id MUST be derived by scanning the existing relationships and
taking the next free one. A fixed id works on a freshly generated document and
silently REPLACES a real relationship — an image, a hyperlink, a header — on a
document that already has several.

#### Scenario: A document with six relationships gets the seventh

- **GIVEN** a document already carrying six relationships
- **WHEN** a chart is embedded
- **THEN** the chart MUST take `rId7`
- **AND** all six pre-existing relationships MUST survive unchanged

#### Scenario: A second chart collides with neither the first nor its relationship

- **GIVEN** a document that already contains one embedded chart
- **WHEN** a second is embedded
- **THEN** it MUST take its own chart part and its own relationship id
- **AND** the first chart's part MUST survive

### Requirement: A chart definition MUST be validated, never guessed at

Every series MUST carry exactly one value per category. A mismatch MUST be refused,
naming both counts.

Padding a short series would draw a chart the caller did not describe; truncating
the categories would drop data. Neither is a safe guess, and the caller is a
language model.

A pie chart MUST refuse a second series and MUST NOT emit axis elements — a pie with
axes opens with a repair prompt.

#### Scenario: A short series is refused

- **GIVEN** three categories and a series carrying two values
- **WHEN** the chart is embedded
- **THEN** the system MUST refuse, naming both counts

#### Scenario: A pie chart carries no axes

- **GIVEN** a pie chart
- **WHEN** the chart part is inspected
- **THEN** it MUST contain no category or value axis

### Requirement: Placement MUST respect the document's structure

A chart appended to the end MUST be placed BEFORE the section properties, because a
`w:sectPr` must be the last child of the body and a paragraph after it is invalid.

A chart may be placed after a named paragraph, addressed by the same
content-derived anchor scheme as a text edit. An unresolvable anchor MUST be refused
and nothing written.

#### Scenario: An appended chart precedes the section properties

- **GIVEN** a document whose body ends with section properties
- **WHEN** a chart is appended
- **THEN** the drawing MUST appear before the section properties

#### Scenario: An unresolvable anchor is refused

- **GIVEN** an anchor matching no paragraph
- **WHEN** a chart is embedded
- **THEN** the system MUST refuse and nothing MUST be written

### Requirement: Chart support MUST be refused by name where it is not implemented

Charts are implemented for `.docx` only. An `.odt` request MUST be refused with a
message naming the limitation, rather than silently returning the document
unchanged.

#### Scenario: An ODF chart request is refused

- **GIVEN** an `.odt` document
- **WHEN** a chart is requested
- **THEN** the system MUST refuse, naming that an ODF chart is an embedded object

### Requirement: The chart MUST be verified by a suite actually rendering it

A conversion returning success MUST NOT be treated as evidence that a chart was
embedded correctly: a suite that silently skips an unparseable part still produces
output.

Verification MUST compare the rendered output of the same document with and without
the chart.

#### Scenario: The rendered output grows because the chart is drawn

- **GIVEN** a document rendered through a real office suite with and without an embedded chart
- **WHEN** the two outputs are compared
- **THEN** the output carrying the chart MUST be materially larger
- **AND** a suite error MUST fail the verification outright
