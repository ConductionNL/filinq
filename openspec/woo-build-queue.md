# Woo build queue for this repository

9 OpenSpec changes in this repository close gaps in the Woo capability programme. Each has a change folder under `openspec/changes/` and an issue titled `[OpenSpec] <change-name>` that the OpenSpec workflow keeps in step with the spec.

## How to pick up a change

1. Take the first change below whose dependencies are all merged on `development`. A dependency in another repository is linked to its issue there; check that issue's linked PR is merged.
2. Inside a wave, the order below is the order to build. Statutory rows come first.
3. Read `openspec/woo-build-rules.md` before the first command, then the change's `proposal.md`, its specs and its `tasks.md`.
4. The decisions the specs cite (D1 to D13) are in `openspec/woo-decisions.md`. A spec never contradicts one. If a task seems to, stop and say so in the issue.
5. Work on the branch the issue names, open one PR with `--base development`, and close the issue through the PR.

Two things need a person, not an agent: settling the Woo refusal grounds against the law (dossiq `woo-refusal-grounds-list`, task 1, blocks seeding), and the screen-reader pass for row 15.5.

## Wave 1

| change | rows | depends on |
|---|---|---|
| [filinq/anonymization-main-spec-valid](https://github.com/ConductionNL/filinq/issues/1350) | supports 4.24 (unblocks image-redaction archive) | nothing |
| [filinq/diwoo-documentsoort-to-opencatalogi](https://github.com/ConductionNL/filinq/issues/1344) | supports 2.3, 2.25 | [opencatalogi/diwoo-metadata-on-the-publication](https://github.com/ConductionNL/opencatalogi/issues/1753) |
| [filinq/pdfua-verapdf-matterhorn](https://github.com/ConductionNL/filinq/issues/1342) | 15.3, 15.7 | nothing |
| [filinq/woo-request-workflow](https://github.com/ConductionNL/filinq/issues/1343) | 7.9 | nothing |

## Wave 2

| change | rows | depends on |
|---|---|---|
| [filinq/anonymization-review-workbench](https://github.com/ConductionNL/filinq/issues/1345) | 4.25, 14.15 | [openregister/anonymisation-discloses-itself](https://github.com/ConductionNL/openregister/issues/4379) |
| [filinq/grondslagen-read-from-dossiq](https://github.com/ConductionNL/filinq/issues/1346) | supports 12.29, 13.28 | [dossiq/woo-refusal-grounds-list](https://github.com/ConductionNL/dossiq/issues/3288) |
| [filinq/image-redaction](https://github.com/ConductionNL/filinq/issues/1347) | 4.24 | [openregister/anonymisation-image-seam](https://github.com/ConductionNL/openregister/issues/4380) |
| [filinq/scan-intake-from-a-watched-folder](https://github.com/ConductionNL/filinq/issues/1351) | supports 1.7 | [integriq/sources-sftp-adapter-intake-hand-over](https://github.com/ConductionNL/integriq/issues/2551), `filinq/scan-intake-with-separator-sheets` |

## Wave 3

| change | rows | depends on |
|---|---|---|
| [filinq/redaction-guarantees-from-the-engine](https://github.com/ConductionNL/filinq/issues/1348) | supports 4.5, 4.27, 18.1, 18.2, 18.3 | [openregister/redaction-release-safeguards](https://github.com/ConductionNL/openregister/issues/4392), [openregister/redaction-policy-as-data](https://github.com/ConductionNL/openregister/issues/4402) |
