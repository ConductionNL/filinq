/*
 * SPDX-FileCopyrightText: 2026 DocuDesk Contributors
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The ONE place the e2e suite decides which Nextcloud it talks to.
 *
 * Why this file exists
 * --------------------
 * `playwright.config.ts` used to resolve `baseURL` as
 *
 *     process.env.NEXTCLOUD_URL || 'http://localhost:8080'
 *
 * while `tests/e2e/global-setup.ts` resolved it as
 *
 *     PLAYWRIGHT_BASE_URL ?? NEXTCLOUD_URL ?? NC_BASE_URL ?? 'http://localhost:8080'
 *
 * Those two disagree. Running the documented
 * `PLAYWRIGHT_BASE_URL=http://localhost:8087 npx playwright test` logged in
 * against 8087 and then ran every spec against **8080** — the SHARED dev
 * container, which bind-mounts other people's working trees. The suite passes
 * either way, so the split is invisible: you get green numbers for an instance
 * you never deployed to.
 *
 * The `|| 'http://localhost:8080'` fallback is the root cause. A missing
 * target must be an error, not a silent redirect onto shared infrastructure.
 *
 * Why `BASE_URL` is still accepted
 * --------------------------------
 * A strict `PLAYWRIGHT_BASE_URL`-only resolver was adopted on another repo
 * during its Vue 3 migration and hard-failed every CI run, because the shared
 * `ConductionNL/.github` quality workflow exports the target as **`BASE_URL`**
 * — not `PLAYWRIGHT_BASE_URL`, not `NEXTCLOUD_URL`. So: strict about having a
 * value, permissive about which of the known names carries it.
 */

/** Environment variables that may carry the target instance, in priority order. */
const CANDIDATES = [
	'PLAYWRIGHT_BASE_URL',
	'BASE_URL',
	'NEXTCLOUD_URL',
	'NC_BASE_URL',
] as const

/**
 * Resolve the Nextcloud base URL for the e2e suite.
 *
 * @throws {Error} When none of the recognised variables is set. There is
 * deliberately no default — see the file header.
 * @return {string} The base URL, without a trailing slash.
 */
export function resolveBaseUrl(): string {
	for (const name of CANDIDATES) {
		const value = process.env[name]
		if (value && value.trim() !== '') {
			return value.trim().replace(/\/+$/, '')
		}
	}
	throw new Error(
		`No Nextcloud target set for the e2e suite. Set one of: ${CANDIDATES.join(', ')}.\n`
		+ 'There is no default on purpose: the previous fallback silently pointed the '
		+ 'suite at http://localhost:8080, the SHARED dev container.\n'
		+ 'Example: PLAYWRIGHT_BASE_URL=http://localhost:8087 npx playwright test',
	)
}
