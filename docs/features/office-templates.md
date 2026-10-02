# Make templates in Word or LibreOffice

A template no longer has to be written in the app. The communications department makes a letter in Word or LibreOffice, types merge tags where the data goes, and uploads the file. Filinq reads the tags, checks them against the register the letter is filled from, and from then on generates documents from that file in the house style it already has.

## Write the template

Type a merge tag where a value goes:

| You type | You get |
|---|---|
| `${aanvrager.naam}` | The value of `naam` inside `aanvrager` in the data the document is generated from. |
| `${besluit.datum}` | Any dotted path works the same way. |
| `${fragment:ondertekening-burgemeester}` | The text of the text fragment with that slug (see below). |
| `${regel}` ... `${/regel}` | The part between the two tags is repeated once per row of the list `regel`. Inside it, `${regel.omschrijving}` is the field of that row. |

A tag inside a table row that points into a list, such as `${regels.bedrag}`, repeats the row once per item.

Type each tag in one go. When you edit a tag letter by letter, Word sometimes splits it internally; the upload then shows a tag you did not mean, and retyping it fixes that.

## Upload it

1. Open **Templates** and click **Upload office template**.
2. Pick the DOCX or ODT, give it a name and a namespace.
3. Fill in **Bound register** and **Bound schema** when the template is filled from one schema. Filinq then checks every tag against that schema.
4. Click **Upload**.

An ODT is converted to DOCX once, at upload, and the original ODT is kept. The page says so; check the preview, because a conversion can shift the layout a little.

These files are refused:

- a file with macros: a `.docm` or `.dotm`, or a DOCX that contains a macro part;
- a file larger than the upload limit (20 MB unless your admin changed it);
- a file whose content is not the format its name claims, or that is damaged.

## Tags that match nothing

After the upload, the template page lists every tag with what the check found: known, text fragment, or unknown. An unknown tag is shown as a warning, for example a typo such as `${aanvraagr.naam}`.

Fix it in the document and upload a new revision, or map the tag to a property of the schema in the **Filled from property** column and click **Save mapping**. A mapping is useful when a whole estate of letters uses its own names, such as `naam_aanvrager` for `aanvrager.naam`.

An admin can make unknown tags block the upload instead of warn (`templates_unknown_tag_severity` set to `blocking`). A template without a bound schema is never blocked: its tags are not checked at all, and the page says so.

## Generate documents

An office template generates like any other template: as a PDF, as the filled DOCX you can still edit, as ODT or as HTML. A tag without a value stays empty and the generation warns you which tag it was, so a missing date never disappears silently. The PDF, ODT and HTML come from LibreOffice on the server; without LibreOffice a PDF is still made, and the other formats are shown as unavailable with the reason.

The office path only fills in values. It runs nothing from the template, and a value can never change the layout of the document.

## Text fragments

A text fragment is a piece of text that many templates share, such as a signature block or a standard paragraph about objection. Manage them on the **Text fragments** tab of the templates page. A template inserts one with `${fragment:slug}`, in an office template and in an HTML template alike.

A fragment may hold merge tags itself; they are filled from the same data. A fragment inside a fragment is not expanded. When a template refers to a fragment that does not exist, the document shows `[ontbrekende bouwsteen: slug]` at that place and the generation warns you.

Anyone can add a fragment. Changing or deleting one is for template editors, because a change shows up in every document generated after it.

## Import a whole estate

1. Put the templates in a ZIP. Folders become the template's category. Put text fragments as `.txt` or `.html` files in a folder called `fragments`; the file name is the slug.
2. Click **Import ZIP**, pick the ZIP, and fill in the namespace and, if you have one, the bound schema.
3. The import runs in the background. The window shows how far it is and a row per file: imported or failed with the reason, the number of tags and the tags that matched nothing.
4. Map the unknown tags right there and click **Save mapping** per template.

A damaged file does not stop the import: it is reported and the rest goes on.

## Versions, locks and copies

Uploading a new revision works like saving a template: the previous revision is kept as a version first, and restoring a version brings back its exact file. While someone else holds the edit lock, a new revision is refused. **Duplicate** gives the copy its own copy of the file, without history and without a lock.
