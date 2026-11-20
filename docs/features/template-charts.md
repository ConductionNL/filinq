# Charts, tables and images in templates

A decision letter often needs more than text: a chart of objections per
district, a table of the cases it covers, the municipal logo. In a Twig
template you write three functions for that. Filinq draws everything on your
own server, and no data leaves it.

## A chart

```twig
{{ chart('bar', {
    labels: dossiers|column('wijk'),
    series: [{name: 'Bezwaren', values: dossiers|column('aantal')}]
}, {title: 'Bezwaren per wijk'}) }}
```

- The type is `bar`, `line` or `pie`.
- `labels` names the points. Each series has a `name` and one value per label.
- Options: `title`, `width`, `height`, `palette` (a list of hex colours),
  `showLegend`, `valueFormat` (`integer`, `decimal:2`, `currency`, `percent`),
  `orientation: 'horizontal'` for a bar chart and `donut: true` for a pie.
- Without a palette, the colours start from the house style's primary colour.

The data comes from what the template already has: the objects in `dataRefs`
or values in the request. There is no separate query to secure.

## A table

```twig
{{ data_table(dossiers, [
    {key: 'naam', label: 'Dossier'},
    {key: 'ontvangen', label: 'Ontvangen', format: 'date'},
    {key: 'bedrag', label: 'Bedrag', format: 'currency'}
]) }}
```

Only the columns you list appear, in that order. Dates read `01-03-2026` and
amounts `€ 1.250,50`, whatever the server's locale. An empty list shows one
row saying there is no data, in your language. Pass `{emptyText: '…'}` to
word it yourself.

## An image

```twig
{{ nc_image(logoFileId, {alt: 'Logo gemeente Demostad', width: 160}) }}
```

The file is read as you, the person generating the document. You can place
only an image you can open yourself. PNG, JPEG, GIF and WebP work. Filinq
checks the content, not the file name, so an SVG renamed to `.png` is
refused. The limit is 5 MB; an administrator changes it with
`occ config:app:set filinq templates.max_image_bytes --value=<bytes>`.

## When something is wrong

A chart with bad data, or an image you cannot open, does not stop the
document. Its place shows a marker such as `[chart error: no data]` or
`[image unavailable: not found or no access]`, and the generation answer
lists the same text under `warnings`. The marker never says whether someone
else's file exists.

## Output formats

- **HTML and PDF** carry the chart as a drawing, sharp at any zoom.
- **ODT** (documents) and **DOCX** (letters) carry it as a picture. LibreOffice
  converts it on your server before the file is made. If that fails, the
  marker names the format, for example `[chart error: the chart could not be
  converted for odf]`.

## In an office (Word) template

A Word template uses placeholders instead of functions. Type them as plain
text, each in one piece, so Word does not split them.

Word templates are filled once the office generation path from the
`office-template-authoring` change is available on your server; until then a
template is generated from its HTML version.

**A chart.** `${chart:bezwaren}` becomes a real Word chart that you can still
edit in Word or LibreOffice. The generation data names it under `charts`:

```json
{
  "charts": {
    "bezwaren": {
      "type": "bar",
      "data": {
        "labels": ["Q1", "Q2", "Q3"],
        "series": [{"name": "Bezwaren", "values": [4, 7, 2]}]
      },
      "options": {"title": "Bezwaren per kwartaal"}
    }
  }
}
```

The types are `bar`, `line` and `pie`, with the same `labels` and `series` as
the `chart()` function. `"orientation": "horizontal"` lays bars on their side,
and `legend` turns the legend on or off. A pie shows its first series. An
empty point counts as 0, because a Word chart has no gaps.

**An image.** `${image:logo}` becomes the image whose Nextcloud file id is in
`images.logo`. Give the id alone, or the id with a size:

```json
{"images": {"logo": {"fileId": 812, "width": 120, "height": 60}}}
```

The same rules apply as for `nc_image()`: only files the person generating can
open, PNG, JPEG or GIF only (WebP cannot go into a Word file), and the same
size limit.

**A table over a list.** A Word template needs no table function. Make a
table with a header row and one row of placeholders, and Filinq repeats that
row for every item. For a repeated section that is not a table, wrap it in a
block:

```text
| Zaaknummer      | Status          |
| ${zaak.nummer}  | ${zaak.status}  |

${block_besluiten}
Besluit ${besluit.titel}
${/block_besluiten}
```

A chart or image that cannot be made leaves the same marker text as in an HTML
template, for example `[chart error: no data]` or
`[image unavailable: not found or no access]`, and the warning is listed with
the result.

The sample template `tests/sample-documents/chart-template.docx` has all three.

## Try it

Open a template and choose **Edit HTML**. Paste the chart example. Put
`{"dossiers": [{"wijk": "Centrum", "aantal": 4}, {"wijk": "Noord", "aantal": 9}]}`
in **Sample data (JSON)** and choose **Preview**.
