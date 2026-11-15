## ADDED Requirements

### Requirement: A merge preview names the fields a template fills

filinq SHALL offer a merge preview of a template for given data references. It SHALL return the rendered HTML, the warnings, and the template's merge fields. Each field SHALL carry its path, its label, its source, its value, and whether it is filled. The preview SHALL also say whether every field is filled. The merge preview SHALL NOT store a document or write an audit entry.

#### Scenario: every field is filled
- GIVEN a template that prints `{{ aanhef }}` and `{{ client.address }}`, and data that holds both
- WHEN a caller asks for its merge preview
- THEN both fields are returned as filled with their values, and the preview reports that all fields are filled

#### Scenario: a field stays empty
- GIVEN the same template and data without `client.address`
- WHEN a caller asks for its merge preview
- THEN `client.address` is returned as not filled, and the preview reports that not all fields are filled

#### Scenario: a template names its fields
- GIVEN a template whose `mergeFields` declares `{path: "aanhef", label: "Aanhef", source: "op basis van het opgegeven geslacht"}`
- WHEN a caller asks for its merge preview
- THEN that field is returned with the label "Aanhef" and that source, in the declared order

#### Scenario: only printed variables are fields
- GIVEN a template that uses `{{ title|upper }}`, `{{ chart(data) }}` and `{% for row in rows %}{{ row.name }}{% endfor %}`
- WHEN its merge fields are extracted
- THEN `title` is a field, and neither the filter, the function nor the loop variable is
