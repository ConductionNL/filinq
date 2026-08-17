# Design — template authoring and application for agents

## Context

DocuDesk already renders templates (`generateCorrespondence`) and already edits
documents in place (`editDocument`, via `EditSessionService`, which owns the
lock, the version precondition, the codec pass and the ADR-088 mark). What is
missing is the operation that needs both at once.

Measured 2026-08-17 on the live instance: 160 distinct tools, of which
`docudesk.template.search` and `docudesk.template.get` are the only template
surface, and no tool anywhere resolves a product or a rate.

## Goals / Non-Goals

**Goals**
- An agent can turn the document the user is looking at into a rendered template.
- An agent can author a template, and can read a template's placeholder contract.
- A quotation's numbers come from data, or the agent is told they are unavailable.

**Non-Goals**
- Replacing `generateCorrespondence`. It stays as-is for recipient-driven letters.
- A pricing *engine* (discounts, tax rules, currency conversion). This change adds
  a rate card and line items; anything conditional belongs elsewhere.
- WYSIWYG template editing in the UI. This is the tool surface only.

## Decisions

### D1 — Apply-to-document is a distinct tool, sharing the renderer

`applyTemplateToDocument(fileId, templateId, values)` renders through the same
engine `generateCorrespondence` uses, then writes via `EditSessionService` so it
inherits the lock, the version precondition and the audit mark rather than
re-implementing them.

⚠️ It must take the SAME lock as `editDocument`. A template application that
bypassed it would be the one write that can clobber an open editor, and it is the
largest write the product performs — it replaces the whole body.

### D2 — 🔴 An unresolved placeholder REFUSES; it never renders blank

Rendering `Waarde: [bedrag]` with no value produces a quotation that looks
finished and is wrong. Worse, it is wrong in the direction nobody checks: a
missing total reads as a formatting slip, not as missing data.

So the renderer collects unresolved placeholders and, if any remain, returns
`{ status: 'incomplete', unresolved: [...] }` and **writes nothing**. The agent's
correct next move is to ask the user for the missing values, which it can only do
if it is told which ones they are.

This is the same failure shape as the truncated tool catalogue found on
2026-08-17: a partial result that does not announce itself gets consumed as a
complete one.

### D3 — Placeholders are DECLARED on the template, not inferred from its body

`describePlaceholders` returns, per placeholder: its key, a human description, its
source (`client`, `lead`, `product`, `literal`, `user`), and whether it is
required. Inferring the set by scanning for `[...]` would make any bracketed prose
in a template a silent placeholder, and would give the agent no way to know where
a value is meant to come from.

### D4 — A rate card is data, and "no rate" is an answer

New `Product` schema: `name`, `description`, `unit` (`hour`, `day`, `piece`),
`unitPrice`, `currency`, `active`. `product.search` resolves free text like
"dev work" to candidates with their rates.

⚠️ It returns candidates, never a single guess. "5 hours of dev work" may match
"Development (senior)" and "Development (junior)" at different rates, and picking
one silently is how a quotation goes out at the wrong price. Zero matches returns
zero matches — the agent must then ask, and the tool description says so.

### D5 — Template creation declares its placeholders up front

`template.create` takes the body plus the placeholder declarations from D3. A
template whose body references an undeclared placeholder is rejected at creation,
so the contract cannot drift from the document. Failing at authoring time is
cheap; failing at render time is a broken quotation.

## Risks / Trade-offs

- **Two tools that both "make a document from a template"** is a discoverability
  risk for the model. Mitigated in the descriptions: `generateCorrespondence` says
  *"creates a NEW file for a recipient"*, `applyTemplateToDocument` says
  *"rewrites a file you already have"*. The distinction is in the first clause of
  each, where a model choosing between them will actually read it.
- **A rate card invites scope creep** toward discounts, tiers and tax. The schema
  is deliberately flat; anything conditional is out of scope and should stay out
  until there is a real case.
- **`applyTemplateToDocument` replaces the whole body**, so an accidental call on
  the wrong file is destructive. It is `write` reach, it takes the lock, and the
  prior version remains in Nextcloud's version history — but the tool description
  must state that it overwrites rather than appends.

## Seed Data (ADR-001)

`Product` seeded for a consultancy: "Development (senior)" @ €125/hour,
"Development (medior)" @ €95/hour, "Design" @ €110/hour, "Project management"
@ €115/hour, "Workshop" @ €1,400/day. Enough that "dev work" is genuinely
ambiguous and D4's candidate-list behaviour is exercised by the seed rather than
only by a test.

One `Template` seeded: "Offerte" with declared placeholders `klant`,
`contactpersoon`, `onderwerp`, `regels`, `bedrag`, `geldigTot` — sourced from
`client`, `client`, `user`, `product`, `computed`, `literal` respectively.

## Declarative-vs-imperative decision (ADR-031)

- `Product`, `Template` placeholder declarations — **declarative**, schema
  register entries in `lib/Settings/docudesk_register.json`.
- Rendering, placeholder resolution, apply-to-document — **imperative**. This is
  document generation, an explicit ADR-031 exception, and it needs the existing
  `EditSessionService` lock discipline.
