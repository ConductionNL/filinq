# Tasks: print-jobs-in-the-app

- [x] 1.1 `printJob` schema with lifecycle and authorization cascade, register bump; PHPUnit validating the exact payload `PrintJobService` writes
- [x] 1.2 `PrintJobService` writes job state through ObjectService and the PDF to IAppData; repair step migrating and removing `print_job_*` app-config entries; PHPUnit for both
- [x] 1.3 `GET api/print/jobs` for the caller's own jobs; PHPUnit that another user's jobs are not listed
- [x] 1.4 Print jobs page and "Send to print" in Correspondence; vitest on the selection-to-batch payload; en and nl strings
- [x] 1.5 Playwright `tests/e2e/spec-coverage/print-jobs.spec.ts`; docs `docs/features/print-jobs.md`
