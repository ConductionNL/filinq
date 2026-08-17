# Tasks

## 0. 🔴 FIRST: `editDocument` writes a file it cannot read back

Found while testing this change end-to-end on 2026-08-17. It blocks every task
below, and it blocks the existing document-editing feature too.

- [ ] Fix `PackageCodec` write so a written `.docx` parses on re-read
- [ ] Make `editDocument` verify its own output before reporting success
- [ ] Report per-edit outcomes, never a bare count of edits submitted

Evidence, isolated with a control:

| file | written by `editDocument`? | `blockCount` on re-read |
|---|---|---|
| 25151 | no | **8** |
| 25335 | yes | **0** |

File 25335 on disk holds **5 `<w:p>` elements and the visible text
"Quotation"** — so the bytes are not empty, and `readDocument` is not broken.
The written package is malformed in a way the codec's own scanner cannot span.

⚠️ It reported SUCCESS while doing this: "5 anchors applied, new version
`ebeb03fa…`", when one line of five had landed. The agent caught it only
because it read the document back and disbelieved the tool.

Acceptance criteria:
- Round-trip is the test: write a document, re-read it, and assert the block
  count and text match what was written. A write whose result cannot be read is
  worse than a refusal — it destroys the document AND reports success.
- ⚠️ A zero-block document is UNRECOVERABLE by the agent: every edit anchors to
  an existing block, so once a file reads as zero blocks nothing can ever be
  written to it again. The corruption is terminal, not cosmetic.
- Assert the per-edit outcome is reported. "5 applied" for one applied edit is
  the failure that hid this.

## 1. Apply a template to the document already open

- [ ] Add `docudesk.applyTemplateToDocument(fileId, templateId, values)` rendering through the existing engine and writing via `EditSessionService`
- [ ] Take the SAME `ILockManager` lock as `editDocument`, under the same owner
- [ ] State in the tool description that it REPLACES the body rather than appending

Acceptance criteria:
- Prove the lock conflicts: an open editor session must make this refuse, not overwrite. This is the largest write the product performs.
- ⚠️ The description is the only thing telling a model this is destructive. Assert it names the overwrite in its first sentence.
- Negative control: the same call against a file the user cannot write is refused exactly as `editDocument` refuses it.

## 2. An incomplete render writes nothing

- [ ] Collect unresolved placeholders during render
- [ ] Return `{status:'incomplete', unresolved:[…]}` and write NOTHING when any remain
- [ ] Name each unresolved placeholder and its expected source in the response

Acceptance criteria:
- `Waarde: [bedrag]` rendered with no value must NOT reach the file. A quotation with a hole in it looks finished and is wrong in the direction nobody checks.
- Assert the file's bytes are UNCHANGED after an incomplete render — "returned an error" and "wrote nothing" are different claims.
- The agent's only correct next move is to ask for the missing values, which requires being told which ones they are.

## 3. Templates declare their placeholder contract

- [ ] Add placeholder declarations to the `Template` schema: key, description, source, required
- [ ] Add `docudesk.template.describePlaceholders`
- [ ] Reject creation when the body references an undeclared placeholder
- [ ] Bump `info.version` in the same commit

Acceptance criteria:
- Do NOT infer placeholders by scanning for `[...]`: any bracketed prose would become a silent placeholder, and the agent would have no way to know where a value comes from.
- Without the `info.version` bump the import is SKIPPED on every existing install, silently.

## 4. Authoring

- [ ] Add `docudesk.template.create` and `docudesk.template.update`
- [ ] Mark both `write` reach so they surface correctly in discovery and the approval gate

Acceptance criteria:
- An agent that can find a template but not make one cannot produce the first quotation on a fresh instance.
- Reach is what the owner sees when granting; a write tool mislabelled `read` defeats the approval gate.

## 5. A rate card, and an honest "I don't know"

- [ ] Add a `Product` schema (name, description, unit, unitPrice, currency, active)
- [ ] Add `docudesk.product.search` returning CANDIDATES with their rates
- [ ] Seed the products and the "Offerte" template from design.md
- [ ] Say in the tool description that zero matches means the agent must ask, never estimate

Acceptance criteria:
- ⚠️ Never resolve free text to a single product silently. "dev work" matches senior and medior at different rates; picking one is how a quotation goes out at the wrong price.
- Assert zero matches returns zero matches. An hourly rate is exactly the plausible-sounding fact a model supplies unprompted, and this is the tool that exists to stop it.

## 6. Prove the whole case

- [ ] End-to-end: empty `.docx` open → "quotation for client X, 5 hours dev work" → client resolved, lead found or created, template applied in place, priced from the rate card
- [ ] Verify the FILE, not the model's reply

Acceptance criteria:
- Read the resulting `word/document.xml`. A model reporting success is not evidence the document changed.
- Run the ambiguous case too: "dev work" must produce a question about which rate, not a silent choice.
