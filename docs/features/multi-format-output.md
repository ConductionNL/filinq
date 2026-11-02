# Multi-format output

Make the PDF for the citizen and the editable Word file for the neighbouring municipality in one generation. Both files come from the same rendered template, so they cannot disagree about a name or a date.

## Ask for several formats

Send `options.formats` to `POST /apps/filinq/api/documents/generate`:

```json
{
  "templateId": "<template uuid>",
  "dataRefs": [{"register": "...", "schema": "...", "id": "..."}],
  "filename": "besluit",
  "options": {"formats": ["pdf", "docx"]}
}
```

Filinq renders the template once. It then makes each format from that render and files every file in your Files, in the same folder a single generation uses (`DocuDesk/<namespace>` unless you set `options.output.targetPath`).

The answer lists each file:

```json
{
  "outputs": [
    {"format": "pdf", "status": "generated", "fileId": 812, "fileName": "besluit.pdf", "downloadUrl": "https://.../remote.php/dav/files/you/DocuDesk/besluiten/besluit.pdf", "size": 20480},
    {"format": "docx", "status": "failed", "fileId": null, "fileName": null, "downloadUrl": null, "size": null, "error": "LibreOffice is not available on this server"}
  ],
  "metadata": {"...": "the generatedDocument entry"},
  "warnings": []
}
```

One format that fails does not stop the others. A request with `options.format` (one format) still gets the file itself back, exactly as before. Sending both `format` and `formats` is refused with 400.

## Formats

| Format | Made by | Needs LibreOffice |
|---|---|---|
| `pdf` | mPDF | no |
| `html` | the rendered template itself | no |
| `docx` | LibreOffice, from the HTML | yes |
| `odf` | LibreOffice, from the HTML | yes |

A DOCX made from HTML is for editing. Its layout is close to the PDF, not identical: LibreOffice reads the HTML as a web page. Send the PDF when the layout matters, the DOCX when someone has to change the text.

## See what this server can make

`GET /apps/filinq/api/documents/formats` answers per format whether it can be made now, and why not:

```json
{"formats": {"pdf": {"available": true}, "docx": {"available": false, "reason": "LibreOffice is not available on this server"}, "odf": {"available": false, "reason": "LibreOffice is not available on this server"}, "html": {"available": true}}}
```

- `?flow=correspondence` gives the formats a letter can have: `pdf`, `docx`, `html` and `email`.
- `GET /apps/filinq/api/templates/{id}/formats` gives the same answer for one template.

The answer is never cached, so installing LibreOffice shows at once. Forcing a format the list says is unavailable fails with 503 and the same reason.

## Letters and correspondence

The output format choice on **Letters & correspondence** comes from this list. A format the server cannot make stays visible but cannot be picked, and shows why.

## Audit trail

The `generatedDocument` entry of a multi-format generation has `outputs`: one line per format with its file id, its status and the error when it failed. `format` holds the first format you asked for, and `fileId` the first file that was made. A single-format generation has no `outputs`.

## Not yet

Office (DOCX) templates, and HTML made from them, come with office template authoring.
