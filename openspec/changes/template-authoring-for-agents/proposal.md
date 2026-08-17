---
kind: code
---

## Why

Asked to *"write a quotation for client X for 5 hours of dev work"* with an empty
`.docx` open, an agent cannot do it. Measured against the live catalogue on
2026-08-17, the pieces exist but do not connect:

| Step | Tool | Status |
|---|---|---|
| Find client X | `pipelinq.client.search` / `.get` | ✅ |
| Find or create the lead | `pipelinq.lead.search`, `pipelinq.createLead` | ✅ |
| Find the quotation template | `docudesk.template.search` / `.get` | ✅ |
| Price "5 hours of dev work" | — | ❌ **nothing on the instance** |
| Apply the template to the OPEN document | — | ❌ **no such path** |
| Author a new template | — | ❌ read-only |

Three gaps, of which the middle one is the architectural blocker.

### The template path and the open-document path never meet

`generateCorrespondence` resolves a recipient from OpenRegister, applies the
organisation huisstijl and renders a letter — into a **new file**. The agent's
other route, `editDocument`, writes into the file the user is looking at but
knows nothing about templates.

So the natural request — *"I have this document open, make it a quotation"* —
has no implementation. The user must either accept a second file appearing
somewhere else, or accept prose with no house style. Today's demo works around
this by pre-seeding a document with `[placeholders]` for the agent to overwrite,
which is a demo fixture rather than a feature.

### Templates are read-only

`template.search` and `template.get` exist; nothing creates or edits one. An
agent can find a quotation template and cannot make one, so the first quotation
on a fresh instance is impossible without a human authoring the template by hand.

### An agent that cannot price will invent a price

There is no product, service or rate-card capability anywhere on the instance —
verified across all 160 distinct tools. Asked for "5 hours of dev work" the model
has three options: ask, refuse, or make a number up. **On a quotation, the third
is the one that causes damage**, and it is also the most likely: an hourly rate is
exactly the kind of plausible-sounding fact a model will supply unprompted.

This change therefore treats "no rate is known" as a condition to be handled
explicitly, not as an edge case.

## What Changes

- **`docudesk.applyTemplateToDocument`** — render a template INTO an existing
  file, in place, under the same lock/version discipline `editDocument` already
  uses. This is the missing join between the two halves.
- **`docudesk.template.create` / `docudesk.template.update`** — author a template,
  with its placeholder set declared rather than inferred.
- **`docudesk.template.describePlaceholders`** — return the placeholders a
  template expects and where each is resolved from. An agent that cannot see the
  contract has to guess it from prose.
- **Unresolved placeholders are a REFUSAL, not a blank.** Rendering with a
  missing `[bedrag]` silently produces a quotation with a hole in it. The call
  returns the unresolved list and writes nothing.
- **A `Product` schema with a rate card**, plus `docudesk.product.search`, so
  "5 hours of dev work" resolves to a described line item at a known rate — and,
  when it does not, the agent is told so rather than left to improvise.

## Capabilities

### New Capabilities
- `document-templates` — authoring templates, applying them to an existing
  document, and resolving their placeholders against real data.

## Why not extend `generateCorrespondence`

It is correspondence-shaped: one recipient, one letter, resolved from a
`Correspondence` object, logged to a correspondence audit trail. A quotation
written into the document already on screen is a different operation with a
different lifecycle, and overloading one tool with "…or write it over there
instead" is how a tool's contract becomes unreadable to the model choosing it.

The two share the renderer; they do not share a tool.
