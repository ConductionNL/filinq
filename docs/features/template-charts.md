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

Office templates with `${chart:…}` and `${image:…}` placeholders are planned
in the `office-charts-and-images` change.

## Try it

Open a template and choose **Edit HTML**. Paste the chart example. Put
`{"dossiers": [{"wijk": "Centrum", "aantal": 4}, {"wijk": "Noord", "aantal": 9}]}`
in **Sample data (JSON)** and choose **Preview**.
