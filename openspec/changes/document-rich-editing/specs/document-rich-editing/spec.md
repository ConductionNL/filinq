## ADDED Requirements

### Requirement: Document metadata MUST be addressed by format-neutral field name

The system MUST expose exactly five writable metadata fields — `title`, `subject`,
`creator`, `keywords`, `description` — and MUST resolve each to the element its
format uses. OOXML stores keywords as `cp:keywords` and ODF as `meta:keyword`; a
caller naming the element rather than the field would be writing per-format code,
which ADR-087 §2 exists to prevent. The formats differ; the capability does not.

A field the document does not carry MUST be returned as an empty string rather than
omitted, so a caller can tell "this document has no subject" from "I did not ask for
the subject".

`created` and `modified` MUST NOT be writable. They record what happened to the
document, and an agent able to set them would turn that record from a fact into a
claim.

#### Scenario: Keywords resolve to each format's own element

- **GIVEN** an `.odt` document
- **WHEN** the `keywords` field is written
- **THEN** the metadata part MUST contain `<meta:keyword>`
- **AND** MUST NOT contain `cp:keywords`
- **AND** reading `keywords` back MUST return the written value

#### Scenario: An absent field reads as empty, not missing

- **GIVEN** a document whose metadata carries a title but no subject
- **WHEN** its metadata is read
- **THEN** the result MUST contain a `subject` key whose value is an empty string

#### Scenario: Timestamps are refused

- **GIVEN** a caller attempting to set `created`
- **WHEN** the write is requested
- **THEN** the system MUST refuse, naming the supported fields

#### Scenario: An unknown field is refused by name

- **GIVEN** a caller naming the field `author`
- **WHEN** the write is requested
- **THEN** the system MUST refuse and MUST list the fields it does support
- **AND** MUST NOT silently ignore the field, because a silently-ignored misspelling reports success and changes nothing

### Requirement: A metadata write MUST leave every other part byte-identical

Writing metadata MUST rewrite only the metadata part. The document body and every
other package entry MUST be byte-identical afterwards.

A document with **no** metadata part MUST have a well-formed one created, carrying
the namespace declarations the written elements need — without them the elements
resolve to nothing and the suite silently ignores them. A metadata part whose root
is **self-closing** MUST be handled, because that is what several writers emit for a
document with no properties set.

Fields the caller does not name MUST be left unchanged.

#### Scenario: The body survives a metadata write

- **GIVEN** a document with a known body part
- **WHEN** its title is written
- **THEN** the body part MUST be byte-identical afterwards

#### Scenario: A missing metadata part is created with namespaces

- **GIVEN** a document with no metadata part
- **WHEN** its title is written
- **THEN** a metadata part MUST be created carrying the title
- **AND** it MUST declare the namespaces its elements use
- **AND** reading the title back MUST return the written value

#### Scenario: A self-closing metadata root is not treated as corrupt

- **GIVEN** a document whose metadata part is `<cp:coreProperties/>`
- **WHEN** a field is written
- **THEN** the write MUST succeed
- **AND** MUST NOT report the part as corrupt

#### Scenario: Unnamed fields survive

- **GIVEN** a document carrying a title and a creator
- **WHEN** only the subject is written
- **THEN** the title and creator MUST be unchanged

### Requirement: Style and layout MUST be applied without disturbing unrelated properties

The system MUST apply `bold`, `italic`, `underline`, `alignment`, `heading`, `list`
and `pageBreakBefore` to an anchored paragraph. Properties MUST be merged into the
existing property blocks: a property of the same name is replaced, and every
unrelated property survives.

Replacing the property block wholesale would silently drop spacing, indentation and
numbering a user set by hand — the class of loss ADR-087 §2 warns about, and one no
test notices unless it is written for it.

Turning a run property **off** MUST write an explicit override rather than omitting
the element, because a paragraph style can turn it on and only an explicit override
countermands that.

A heading level of `0` MUST **remove** the heading style. It is a removal, not an
absence: treating it as "nothing to add" leaves the existing style in place while
reporting the restyle applied.

#### Scenario: Unrelated paragraph properties survive a restyle

- **GIVEN** a paragraph carrying hand-set spacing and indentation
- **WHEN** its alignment is set
- **THEN** the spacing and indentation MUST still be present
- **AND** the alignment MUST be applied

#### Scenario: Re-setting a property replaces rather than duplicates it

- **GIVEN** a paragraph already aligned left
- **WHEN** it is aligned right
- **THEN** exactly one alignment property MUST be present, and it MUST be right

#### Scenario: Heading level 0 returns a paragraph to body text

- **GIVEN** a paragraph styled as a heading
- **WHEN** it is restyled with heading level `0`
- **THEN** the heading style MUST be removed
- **AND** no style named `Heading0` MUST be written, because no suite defines one

#### Scenario: Turning bold off writes an explicit override

- **GIVEN** a paragraph whose style turns bold on
- **WHEN** bold is set to false
- **THEN** an explicit off-override MUST be written, not merely the absence of a bold element

### Requirement: Style MUST be refused by name where it is not implemented

Style and layout are implemented for OOXML only. For ODF the system MUST refuse with
a message naming the limitation **and** naming what does still work on `.odt`.

Returning the markup unchanged is prohibited: it reports success and changes
nothing, which is indistinguishable from a working call and is the failure mode this
requirement exists to prevent.

#### Scenario: An ODF style request is refused, not ignored

- **GIVEN** an `.odt` document
- **WHEN** a style edit is requested
- **THEN** the system MUST refuse
- **AND** the message MUST state that text edits and metadata do work on `.odt`
- **AND** the markup MUST NOT be returned unchanged as though the style had been applied

#### Scenario: An unknown style property is refused

- **GIVEN** a style object naming `colour`
- **WHEN** the edit is requested
- **THEN** the system MUST refuse and list the properties it supports

#### Scenario: An empty style object is refused

- **GIVEN** a style edit carrying no properties
- **WHEN** the edit is requested
- **THEN** the system MUST refuse rather than report an edit applied

### Requirement: A metadata write MUST be as accountable as a text edit

A metadata write MUST run through the same editing session as a body edit: the same
file lock, the same version re-check immediately before the write, the same
`Agent authored` tag applied before the bytes become visible, and the same standing
refusals (open in an editor, under a signing request, anonymisation output).

The session MUST be shared code rather than a second copy. The version-recheck is
the only thing preventing an agent silently overwriting a concurrent human edit, and
two copies of it would drift.

#### Scenario: A stale version refuses a metadata write

- **GIVEN** a document that changed since its metadata was read
- **WHEN** a metadata write is attempted with the earlier version
- **THEN** the write MUST be refused
- **AND** nothing MUST be written

#### Scenario: A metadata write is tagged and restorable

- **GIVEN** a successful metadata write
- **WHEN** the file is inspected
- **THEN** it MUST carry the `Agent authored` tag
- **AND** the previous version MUST remain restorable
