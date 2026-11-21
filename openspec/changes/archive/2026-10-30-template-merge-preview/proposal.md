# A template preview names the fields it fills

## Why

An app that makes a document from a filinq template (pipelinq's letter dialog, board PqBriefOpstellen) shows the user the letter before it is made and lists what filinq filled in for them, with a line saying whether anything is missing ("Alle 4 velden ingevuld, niets ontbreekt"). filinq's preview returns only the rendered HTML. A caller cannot tell which fields the template uses, which were filled from the record, and which stayed empty.

## What changes

- A merge preview renders a template against the caller's data references, as the existing preview does, and also returns the template's merge fields: for each one its path, a label, where the value came from, the value, and whether it was filled. It also returns whether every field is filled.
- A template may declare `mergeFields` (path, label, source) to name its fields for people. A template without them still lists the variables it prints, by path.
- The same service is reachable in-process for apps that call filinq's services, and over HTTP as `POST /api/documents/generate/merge-preview`, with the same body as the existing preview.
- The merge preview creates nothing and logs nothing, like the existing preview.

## Not in this change

- Sending a document (e-mail, Berichtenbox). The caller decides how to deliver it.
