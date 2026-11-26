## MODIFIED Requirements

### Requirement: Edits address stable anchors, never positional indexes

`PackageCodec::applyEdits()` gains a fourth action, `style`, alongside `replace`,
`insertAfter` and `delete`. Anchor resolution, the fail-the-whole-set-on-one-bad-anchor
rule, and the descending-span application order are unchanged.

A `style` edit carries a `style` object and **no text**. That separation is
deliberate: a caller who wants a paragraph bold must not have to resend its text,
because the caller is a language model and a model that resends text is a model that
will paraphrase the paragraph while "only" changing its formatting.

A `style` edit carrying no style properties MUST be refused. An edit that changes
nothing while reporting success is the failure this action is most likely to produce.

#### Scenario: A style edit changes formatting without touching the words

- **GIVEN** an anchored paragraph
- **WHEN** an edit with action `style` and `{"bold": true}` is applied
- **THEN** the paragraph's run properties MUST carry bold
- **AND** its visible text MUST be unchanged

#### Scenario: A style edit with no properties is refused

- **GIVEN** an edit with action `style` and an empty style object
- **WHEN** the edit set is resolved
- **THEN** the whole set MUST be refused, naming the supported properties

#### Scenario: The other three actions are unaffected

- **GIVEN** an edit set using `replace`, `insertAfter` or `delete`
- **WHEN** it is applied
- **THEN** behaviour MUST be identical to before this change
