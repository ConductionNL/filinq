## ADDED Requirements

### Requirement: The grondslagen seed MUST be the Woo Art. 5 grounds (A–S)

The `dossier` register's `base` seed MUST contain the 19 Woo Art. 5 exception grounds (legend A–S), with article-derived slugs (`art-5-1-1-a` … `art-5-2-2`), a legend-prefixed Dutch `name` (e.g. "J — Persoonlijke levenssfeer"), and the Woo text + article reference in `description`. Demo seed references (`bases[]`) MUST use the new slugs.

#### Scenario: The 19 grounds are seeded

- **WHEN** the DocuDesk register configuration is imported
- **THEN** the `base` objects include all 19 Woo Art. 5 grounds A–S with `art-5-*` slugs
- **AND** no demo `dossier`/reference still points at a removed legacy slug

### Requirement: Grondslag pickers MUST source the list from the register

The anonymisation grondslag pickers MUST fetch the `base` objects from OpenRegister rather than a hardcoded list, display each grondslag's human `name`, and store its `slug`. On a fetch error the UI MUST fall back to a known slug list so the pickers keep working. No hardcoded grondslagen mirror may remain in the picker components.

#### Scenario: Pickers show names and store slugs

- **GIVEN** the register returns the seeded `base` objects
- **WHEN** a grondslag picker renders its options
- **THEN** each option shows the grondslag `name` and, when selected, persists the `slug`

#### Scenario: Fetch failure falls back to the slug list

- **GIVEN** the register is unreachable or returns no `base` objects
- **WHEN** a grondslag picker loads
- **THEN** it falls back to the known slug list and remains usable

#### Scenario: No hardcoded mirror remains

- **WHEN** the picker components are inspected
- **THEN** they contain no hardcoded `BASES_OPTIONS` grondslagen list (they consume the shared fetch)
