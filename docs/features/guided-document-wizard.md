# Make a document by answering questions

A wizard puts a short interview in front of a template. A clerk answers one question at a time, checks the answers, and gets the document. Nobody has to know which register, schema or data path the template reads.

Every document a wizard makes keeps the interview: which wizard ran, its version at the time, and the answers. So you can always see how a letter came to say what it says.

## Add a wizard to a template

1. Open the template and go to the **Wizard** tab.
2. Give the wizard a name and click **Add a question**.
3. Write the question, give it a short key, and pick the answer type:

| Answer type | What the clerk does | Where the answer goes |
|---|---|---|
| Text | Types an answer | The data path you give, such as `applicant.name` |
| Choice | Picks one of the choices you list, one per line as `value=label` | The data path you give |
| Date | Picks a date | The data path you give |
| Register object | Picks an object, such as a dossier, from the register and schema you name | The object's data, under its schema name, exactly like a hand-written data reference |

4. To ask a question only sometimes, fill in **Ask only when this earlier question…**. Pick an earlier question and say when: it is equal to a value, not equal to a value, or answered.
5. Leave **Offer this wizard to clerks** on and click **Save wizard**.

A template has at most one active wizard. Only template editors can change one, and not while someone else has the template open.

Filinq checks the wizard when you save it. It refuses a key used twice, a condition on a later question, a choice question without choices, and a register object question without its register and schema. A data path the template's schema does not have is saved, with a warning.

## Run a wizard

Open the template and click **Generate with wizard**. The button is there only when the template has an active wizard.

- One question per step, with **Question 2 of 4** above it. **Previous** keeps your answers.
- A question that depends on an earlier answer appears as soon as that answer is given, and disappears when it changes.
- The review step lists every question that was asked, with your answer. **Change** takes you back to it.
- A text or choice answer that lands on a field of the object you picked is marked **replaces the value from the picked object**. Your answer wins.
- **Generate document** sends one request to the normal generate endpoint and downloads the file.

Filinq checks the answers again on the server before anything is rendered. A required question without an answer, a choice that is not on the list, or a date that is not a date stops the generation. The review step then marks the question that was refused.

Everything works with the keyboard alone: Tab between fields, Space to pick a choice, Enter on **Next** and **Generate document**.

## Start from a dossier

A dossier page shows a **Generate with wizard** button, with the wizard's name, for every active wizard that asks for a dossier. The wizard opens with the dossier already picked, and with every answer the dossier's data can give. These are suggestions: each one is marked, and you can change it before you generate. The questions the dossier cannot answer are asked as usual.

## For integrations

Generation stays on `POST /api/documents/generate`. A wizard run adds `options.wizardContext` with `wizardId`, `wizardVersion` and `answers`. Register object answers arrive as `dataRefs`, the other answers as `adHocData` at their data paths. A request without `wizardContext` behaves exactly as before.

| Route | What it does |
|---|---|
| `GET /api/templates/{id}/wizard` | The template's active wizard, or `null` |
| `GET /api/wizards?register=&schema=` | The active wizards that ask for an object of that register and schema |
| `POST /api/wizards`, `PUT /api/wizards/{id}`, `DELETE /api/wizards/{id}` | Author a wizard. 409 for a second active wizard, 422 per question, 423 while the template is locked by someone else |
| `POST /api/wizards/{id}/prefill` | Suggested answers for a run started from an object: `{answers, unresolved}` |

`options.wizardContext` cannot be combined with `options.formats` yet. Use `options.format`.

## Privacy

The answers are stored on the generated document's record, because they explain the document. They often describe a person. They stay in OpenRegister with the same access rules as the record, never leave the instance, and are deleted with the record. A run that is not finished is not stored anywhere: closing the browser discards it.
