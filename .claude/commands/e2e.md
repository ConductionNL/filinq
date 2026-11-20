---
description: Run Filinq's Playwright end-to-end tests
---
Run the browser tests in `tests/e2e`, configured in `playwright.config.ts`.

1. Make sure a Nextcloud with Filinq and OpenRegister is running, for example `docker compose -f docker/docker-compose.yml up -d --build` (http://localhost:8080, admin / admin).
2. `npm run test:e2e`, or `npx playwright test $ARGUMENTS` for one spec file.

Report each failing test with its step and the error.
