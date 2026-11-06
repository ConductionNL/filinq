---
kind: code
---

# Proposal: redaction-four-eyes-setting

## Summary

Filinq records who checked a document's findings before it anonymises (REQ-RWB-02, REQ-RWB-06). Nothing stops the person who checked from also anonymising, so one officer can do both. Ruben decided on 30 October 2026 (DECISIONS row 266): an admin setting, on by default for Woo cases, under which the person who marks a document checked must differ from the person who anonymises it.

## What changes

- A new admin setting `redaction_four_eyes` with three values: `woo` (default), `always` and `off`. It sits in the Anonymisation section of the admin page.
- `AnonymizationService::runAnonymize` compares the review mark's `checkedBy` with the acting user of the run when the setting applies. Same person: the run is refused with reason `same_person` and nothing is written. The check sits next to the existing review gate, so the API, the batch path, the folder job and dossiq's cross-app call are all covered.
- A run counts as a Woo run when its caller says so (`wooCase: true` in the run options) or when it runs with scope `dossier`. dossiq's `FilinqRedactionClient` passes `wooCase` for a case on a Woo case type.
- A run without a known acting user is refused under the setting, because a missing name cannot be shown to differ.
- The review card says "Een collega moet dit document anonimiseren" after a check while the setting applies, so the checker knows a second person is needed.

## Why

A redaction that leaves the building under the Woo is a publication decision. Two people looking at it is the control Woo officers ask for, and competitors such as ZyLAB offer it (row red-four-eyes). Making it a setting keeps small organisations with one officer able to work outside Woo cases.

## Impact

- `lib/Service/SettingsService.php` (key, default, `WRITABLE_KEYS`), `src/views/settings/Settings.vue` (Anonymisation section).
- `lib/Service/Redaction/RedactionReviewGate.php`, `lib/Service/Redaction/RedactionOutputGuard.php`, `lib/Service/AnonymizationService.php`, `lib/Service/AnonymizeRun.php`.
- `src/components/ReviewMarkCard.vue` for the second-person hint.
- dossiq: `lib/Service/FilinqRedactionClient.php` and `lib/Service/Redaction/DocumentRedactionService.php` pass `wooCase`, and the DqLakken page tells the officer a colleague must redact. That task is listed here and built in dossiq.

## Assumptions to confirm

- "Woo case" is read as: the caller flags it, or the run is a filinq dossier run (filinq dossiers are Woo dossiers). If Ruben wants a different signal, only task 2 changes.
