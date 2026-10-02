# Redaction and what leaves the building

## What this is for

A black rectangle over live text is the commonest Woo failure. The page looks
redacted. Select the text underneath, and the name is still there.

Filinq answers that in three parts. A person checks the result before anything
is written. The copy that is written is verified against the routes a name can
come back through. What gets published references the copy and never the
original.

## A person checks it first

Detection is a machine's opinion about where the names are. It is a good
opinion. It is not a decision.

Filinq refuses to write a redacted copy until somebody has marked the document
checked. The refusal lives in the service, not on the screen, so it holds for
the API and for a batch run just as it does for a person clicking. A batch of
fifty-five thousand documents is exactly where a screen-only gate stops being a
gate.

The check names a person and a moment, and it covers one detection run. Run the
detection again and the earlier check no longer applies: it was about a
different set of findings. Filinq says so in the refusal rather than silently
asking again.

**The message a Dutch reader sees:**

> Dit document is gecontroleerd, maar de detectie is daarna opnieuw uitgevoerd.
> Die controle ging over andere bevindingen en geldt dus niet voor deze.

A check that does not say who made it is refused too. Nobody can be asked about
an unsigned approval.

## The copy is verified, not merely claimed

A claim that a redaction is irreversible is worth what verifies it. Filinq
checks the bytes it wrote, on every route a removed value can come back
through:

| Route | What is checked |
|-------|-----------------|
| Text under the mark | The value does not appear in the extracted text |
| Embedded thumbnail or preview | Absent, or regenerated from the redacted page |
| XMP, EXIF and document properties | Stripped or rewritten |
| Incremental update or prior revision | One revision, no incremental section |
| Annotations and form fields | Removed, not hidden |
| Files attached inside the PDF | Removed, or redacted themselves |

The check runs on the bytes that were actually written, last. A grondslagen
summary appends a page after the redaction, and a PDF/UA rewrite re-lays the
text. Verifying before either would answer about a file that no longer exists.

The verdict is recorded on the `anonymizationLink`, the row that pairs the
original with its copy. Ask six months later whether a published copy was
checked, and there is an answer: `clean`, `leaking` or `unverifiable`, with the
output mode it holds for and the routes that were walked.

`unverifiable` is recorded too. An empty field and a clean verdict look the
same to anyone filtering on "was this checked", and one of them means nobody
looked.

## The publication list references copies

A Woo-publicatielijst composes itself over the records a saved view returns.
Each entry resolves to the redacted copy through the link that pairs it with
its source. The original is never linked, never embedded and never named.

A record in the view with no redacted copy stops the list. Leaving it out and
publishing the rest is tempting, and it is worse: afterwards nobody can tell a
record that was excluded from one that was never in the view. Filinq reports
which records are not ready and produces nothing.

## A download can wait for conditions

Publish a document under a hergebruiksvoorwaarde and the reader accepts the
terms before the file arrives. Filinq records who accepted, when, and which
version.

Change the terms and readers are asked again. Acceptance of an older text is
acceptance of a different text.

If the acceptance cannot be written, the file is not served. An unrecorded
acceptance is the same as none, and serving first would produce exactly the
file the terms exist to account for.

## API

| Method | URL | Description |
|--------|-----|-------------|
| POST | `/api/redaction/documents/{fileId}/checked` | Record that you checked this detection run |
| POST | `/api/redaction/publication-list` | Compose a list over a saved view |
| GET | `/api/redaction/agreement` | The conditions a document is gated on |
| POST | `/api/redaction/agreement/accept` | Accept them, and unlock the download |

Marking a document checked is restricted to `docudesk-woo-officers` and
`docudesk-policy-admins`. Both ship empty, so nobody signs an approval by
default. The checker is taken from the session, never from the request, so no
caller can sign in somebody else's name.

## What lives elsewhere

Detecting entities is OpenRegister's. Publishing the result is opencatalogi's.
The screen where a handler adds and removes markings is the anonymisation
review workbench. Recovering an original is the reversible pseudonymisation
path, which is gated and audited and is the only place recovery is allowed at
all.

## Screenshots

None yet. The surfaces this change ships are the API and the refusal messages;
the review screen belongs to the anonymisation review workbench and is captured
there once it lands.
