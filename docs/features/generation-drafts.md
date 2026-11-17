# Edit a document before it is stored

Sometimes the generated letter needs one more sentence, or you want to stop halfway and come back tomorrow. A draft lets you do both. The document is rendered from the template and the records as usual, but nothing is stored or logged until you finish it.

## Open a letter for editing

1. Go to **Letters & correspondence**, pick the template and fill in the recipient.
2. Choose **Open for editing** next to **Generate letter**.

The letter opens on **Drafts** with the recipient's details already merged in. No file is downloaded and the correspondence log has no new entry yet.

**Open for editing** is not offered for a template that has a plain-language version. Such a template produces the formal and the plain letter together from the same data, so it is generated directly.

## Edit, save and finish

The draft editor has the same editing area as the template page: bold, italic, underline, two heading levels, lists, and **Edit HTML** for the source. There are no merge fields here; the data is already filled in.

- **Save** keeps your changes. You can close the page and come back.
- **Finish** saves what you changed, then stores and logs the document exactly like a direct generation: a correspondence entry for a letter, a generated-document entry otherwise. The entry names you as the editor and links the draft. The document downloads in the format you chose.
- **Discard** removes the draft. Nothing was stored from it.

What you type stays text. If you type `{{ naam }}`, the letter says `{{ naam }}`; the template is not run again on your edits. Scripts and other unsafe HTML are removed before the document is made.

A finished draft can no longer be changed.

## Leave a guided document and come back

In a wizard, **Save and finish later** is on every question and on the review step. It keeps your answers so far and the question you were on. The draft is listed on **Drafts**; open it and the wizard continues at that question with your answers filled in.

On the review step, **Open for editing** renders the document from your answers into a draft, so you can change the text before it is stored. When you finish it, the wizard checks the answers again, as it does for **Generate document**.

## Drafts are yours and short-lived

**Drafts** lists your drafts that are still being answered or edited, the most recently changed first.

- Only you and an administrator can open your drafts. A colleague who opens the link sees that the draft was not found.
- A draft is removed 30 days after its last save. Every save starts the 30 days again. A daily job removes drafts that were left alone, and no file is made from them.
- A draft holds personal data from the records it was made from, kept only to prepare the document. It is registered as a processing activity with that purpose and the 30-day limit.

<!-- A screenshot of the draft editor belongs here (ADR-010); it is captured from a running instance. -->
