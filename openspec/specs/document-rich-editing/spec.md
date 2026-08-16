---
status: in-progress
---

# document-rich-editing Specification

**OpenSpec changes**
- document-rich-editing

## Purpose

Defines how an agent changes a document's **style, layout and metadata** — as
opposed to its words — and what it is refused.

Three regions of the package are involved, and they are not interchangeable. Style
and layout live inside the paragraph in OOXML but in a separate style region in ODF;
metadata lives in a different package part in both. The capability is presented to a
caller as one surface with format-neutral field names, because the formats differ
while the capability does not (ADR-087 §2).

Every refusal here is explicit. The recurring failure in this area is a call that
reports success and changes nothing, which is indistinguishable from a working one.

## Requirements

### Requirement: Document metadata MUST be addressed by format-neutral field name

Five fields are writable: `title`, `subject`, `creator`, `keywords`, `description`.
Each MUST resolve to the element its format uses — OOXML `cp:keywords`, ODF
`meta:keyword`. A field the document does not carry MUST read as an empty string
rather than be omitted. `created` and `modified` MUST NOT be writable: they record
what happened to the document, and an agent able to set them would turn that record
from a fact into a claim.

#### Scenario: Keywords resolve to each format's own element

- **GIVEN** an `.odt` document
- **WHEN** `keywords` is written
- **THEN** the metadata part MUST contain `<meta:keyword>` and MUST NOT contain `cp:keywords`

#### Scenario: An unknown field is refused by name

- **GIVEN** a caller naming the field `author`
- **WHEN** the write is requested
- **THEN** the system MUST refuse and list the supported fields

### Requirement: A metadata write MUST leave every other part byte-identical

Only the metadata part is rewritten. A document with no metadata part MUST have a
well-formed, namespaced one created; a self-closing root MUST be handled rather than
reported as corrupt. Unnamed fields MUST survive.

#### Scenario: The body survives a metadata write

- **GIVEN** a document with a known body part
- **WHEN** its title is written
- **THEN** the body part MUST be byte-identical afterwards

### Requirement: Style and layout MUST merge, never replace, existing properties

`bold`, `italic`, `underline`, `alignment`, `heading`, `list` and `pageBreakBefore`
MUST be merged into the paragraph's existing property blocks. Same-named properties
are replaced; unrelated ones survive, because replacing the block wholesale would
silently drop hand-set spacing and indentation.

Switching a run property off MUST write an explicit override, not omit the element.
A heading level of `0` MUST remove the heading style — a removal, not an absence.

#### Scenario: Unrelated paragraph properties survive a restyle

- **GIVEN** a paragraph with hand-set spacing and indentation
- **WHEN** its alignment is set
- **THEN** the spacing and indentation MUST still be present

#### Scenario: Heading level 0 returns a paragraph to body text

- **GIVEN** a paragraph styled as a heading
- **WHEN** it is restyled with heading level `0`
- **THEN** the heading style MUST be removed, and no `Heading0` style written

### Requirement: Style MUST be refused by name where it is not implemented

Style is implemented for OOXML only. An ODF style request MUST throw, naming the
limitation and naming what still works on `.odt`. Returning the markup unchanged is
prohibited — it reports success and changes nothing.

#### Scenario: An ODF style request is refused, not ignored

- **GIVEN** an `.odt` document
- **WHEN** a style edit is requested
- **THEN** the system MUST refuse, and the message MUST state that text edits and metadata do work on `.odt`

### Requirement: A metadata write MUST be as accountable as a text edit

A metadata write MUST run through the same editing session as a body edit: same
lock, same version re-check immediately before the write, same `Agent authored` tag
applied before the bytes become visible, same standing refusals. The session MUST be
shared code, because the version re-check is the only thing preventing an agent
silently overwriting a concurrent human edit.

#### Scenario: A stale version refuses a metadata write

- **GIVEN** a document that changed since its metadata was read
- **WHEN** a metadata write is attempted with the earlier version
- **THEN** the write MUST be refused and nothing written
