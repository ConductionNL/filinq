/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * CI regression config for the shared `E2E Tests (Playwright)` job.
 *
 * WHY A SECOND CONFIG EXISTS
 * --------------------------
 * The shared workflow (ConductionNL/.github/.github/workflows/quality.yml)
 * runs the suite as:
 *
 *     CONFIG="${{ inputs.playwright-test-path }}/playwright.config.ts"
 *     if [ ! -f "$CONFIG" ] && [ -f "playwright.config.ts" ]; then
 *       CONFIG="playwright.config.ts"
 *     fi
 *     npx playwright test --config="$CONFIG"
 *
 * Note what is missing: `--project`. So whichever config it picks, EVERY
 * project declared in it runs. The root `playwright.config.ts` declares three:
 *
 *   chromium     — the regression suite. This is the one CI wants.
 *   docs-capture — journeydoc screenshot capture (ADR-030). Re-shoots every
 *                  tutorial screenshot; the dedicated `Journeydoc Capture`
 *                  job runs it explicitly with `--project docs-capture`.
 *   visual       — pixel-diff baselines. The root config's own comment says
 *                  the PNGs are host-font/GPU specific, so "a CI Linux runner
 *                  will not byte-match a dev-container baseline".
 *
 * Letting the root config be picked would therefore run two projects that are
 * documented as unable to pass on a CI runner, on top of the one that can.
 * Rather than delete or weaken them, `playwright-test-path: tests/e2e` in the
 * caller makes the workflow's FIRST lookup hit this file, which declares only
 * the regression project. The root config is untouched and stays the entry
 * point for local runs, `npm run test:e2e:docs`, and `--project visual`.
 *
 * The report/output paths differ from the root config on purpose: the workflow
 * uploads `server/apps/docudesk/playwright-report/` and
 * `server/apps/docudesk/test-results/`, so on CI the artifacts must land at the
 * APP ROOT, not under `tests/e2e/`. With the root config's paths the "Upload
 * Playwright report" step matches nothing and silently uploads an empty
 * artifact (`if-no-files-found: ignore`) — a failing run with no report to read.
 */

import { defineConfig, devices } from '@playwright/test'
import * as path from 'path'

import { resolveBaseUrl } from './base-url'

const APP_ROOT = path.resolve(__dirname, '..', '..')

export default defineConfig({
	testDir: __dirname,
	globalSetup: path.resolve(__dirname, 'global-setup.ts'),
	// Same budget as the root config, and for the same reason: DocuDesk ships a
	// multi-megabyte bundle that Playwright loads with a cold cache, and the CI
	// runner serves Nextcloud from `php -S`. A real hang still fails, just later.
	timeout: Number(process.env.PW_TEST_TIMEOUT || 120_000),
	expect: { timeout: 15_000 },
	fullyParallel: false,
	retries: process.env.CI ? 1 : 0,
	workers: 1,
	reporter: [
		['html', { open: 'never', outputFolder: path.join(APP_ROOT, 'playwright-report') }],
		['list'],
	],
	outputDir: path.join(APP_ROOT, 'test-results'),

	use: {
		// Single source of truth — see tests/e2e/base-url.ts. Never reintroduce
		// a `|| 'http://localhost:8080'` fallback here.
		baseURL: resolveBaseUrl(),
		storageState: path.resolve(__dirname, '.auth', 'admin.json'),
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		navigationTimeout: 60_000,
		actionTimeout: 20_000,
	},

	projects: [
		{
			name: 'chromium',
			testIgnore: ['**/docs-screenshots.spec.ts', '**/visual/**'],
			use: { ...devices['Desktop Chrome'] },
		},
	],
})
