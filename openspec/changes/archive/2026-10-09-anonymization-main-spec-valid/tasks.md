# Tasks: anonymization-main-spec-valid

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 2. -->

Spec only. Build rules: `openspec/woo-build-rules.md`.

## 1. The repair (done in the spec PR)

- [x] 1.1 Move REQ-ANON-00 and REQ-ANON-CAL, byte for byte, into `## Requirements` of
  `openspec/specs/anonymization/spec.md`.
  - Test: `openspec validate anonymization --type spec --strict` answers valid (it failed with two
    errors before); a sorted line diff against `development` shows only blank lines added.
- [x] 1.2 Every change with an `anonymization` delta still validates.
  - Test: `openspec validate <change> --strict` for document-sanitization,
    anonymise-pdf-only-output-mode, publication-policy-labels-and-nav, enable-kenteken-entity-type,
    grondslagen-woo-art5, anonymise-prohibition-consent-guard, image-redaction and
    odt-anonymisation-frontend; image-redaction no longer reports that its archive would be refused.

## 2. After merge

- [x] 2.1 Once the spec PR is merged on `development`, re-run the check of 1.1 on `development` and
  archive this change with `openspec archive anonymization-main-spec-valid --yes`.
  - Test: the archive succeeds, and `openspec validate anonymization --type spec --strict` is still
    valid afterwards with 28 requirements.
- [x] 2.2 One PR for the archive, `--base development`; merge `development` in, never rebase; no
  `Co-Authored-By` trailer. Done means merged on `development` with CI green.

2026-10-09: the repair is on `development` (`openspec validate anonymization --type spec --strict` valid); archived on `build/openspecs-1`, whose PR to `development` is the one PR of 2.2.
