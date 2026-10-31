# Design: office-charts-and-images

## Context

At development after `template-charts`, the Twig path has `chart()`,
`data_table()` and `nc_image()`, and `TemplateImageResolver` reads a file as the
signed-in user with a raster-only, size-capped check. There is no office render
path: `DocumentService` renders Twig only. PhpWord `^1.2` ships
`TemplateProcessor::setChart()` and `setImageValue()`.

## Decisions

### D1: fill in the office pipeline's pre-pass slot

When office-template-authoring 2.3 lands, its fill step runs fragments first
and then `setValue`/`cloneBlock`. Charts and images fill in the same pre-pass:
for each `${chart:key}` the filler builds a `PhpWord\Element\Chart` from
`charts.key` and calls `setChart()`; for each `${image:key}` it resolves
`images.key` through `TemplateImageResolver`, writes the bytes to a temp file
and calls `setImageValue()` with the declared width and height.

### D2: the same honest failure as the Twig path

Unsupported type, malformed data, or an image the user cannot read replaces the
placeholder with the marker text (`chart error: …`, `image unavailable: …`, in
the user's language) and adds a generation warning.

### D3: pin the PhpWord surface

A unit test pins that `TemplateProcessor::setChart` exists and accepts an
`Element\Chart` for bar, line and pie, so a dependency regression fails the
build instead of the feature.
