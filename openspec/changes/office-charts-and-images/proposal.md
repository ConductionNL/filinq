# Office charts and images

## Why

The `template-charts` change put charts, tables and images into documents made
from Twig templates. Its office half could not be built: native DOCX charts and
`${image:key}` placeholders are filled inside the office fill pipeline, and that
pipeline (office-template-authoring REQ-DDOTA-003) does not exist yet. This
change carries that half on its own, so `template-charts` could close on what it
shipped and nothing is claimed that is not built.

Matrix row: `gen-charts` ("Put a chart built from the data into a generated
document.", source Carbone charts and pivot tables). The Twig half moved it to
built with rating partial; this change is the missing half that makes the
rating yes for office templates.

## What changes

- `${chart:key}` in an office template becomes a native Word chart, built from
  `charts.key = {type, data, options}` in the generation context.
- `${image:key}` in an office template becomes the image from Nextcloud file
  `images.key`, read as the generating user with the same checks `nc_image()`
  already applies (TemplateImageResolver).
- The author documentation gains the row-cloning recipe for collection tables
  on the office path.

## Depends on

`office-template-authoring` task 2.3 (the office render path). Build that first.

## Impact

- New: an office chart filler and an office image filler, called from the
  office render path.
- Reused: `TemplateImageResolver` (template-charts), PhpWord `TemplateProcessor`.
- No schema change, no register bump.
