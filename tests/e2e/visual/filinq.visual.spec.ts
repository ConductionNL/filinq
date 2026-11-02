/*
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Visual-regression baselines for Filinq's key surfaces (GAP-5).
 *
 * Run:    npx playwright test --project visual
 * Update: npx playwright test --project visual --update-snapshots
 *
 * Baselines live in tests/e2e/visual/<spec>-snapshots/ and ARE committed.
 * See _visual-helpers.ts for the platform-rendering caveat.
 */
import { test } from '@playwright/test'
import { shootSurface } from './_visual-helpers.ts'

const APP = '/index.php/apps/filinq'

test.describe('Filinq — visual baselines', () => {
	test('dashboard', async ({ page }) => {
		await shootSurface(page, `${APP}/#/`, 'dashboard.png')
	})

	/*
	 * The Intake inbox, rendered by src/views/intake/IntakeIndex.vue.
	 *
	 * This is a list surface whose whole job is layout: channel, sender,
	 * subject and received date side by side, with the assign and reject
	 * actions on each row. A regression here is exactly the kind that every
	 * behavioural assertion in tests/e2e/workflows/intake-inbox.spec.ts still
	 * passes through, because that spec asks whether the buttons work and not
	 * whether the columns are still readable.
	 *
	 * NO BASELINE PNG IS COMMITTED WITH THIS SPEC, deliberately. A baseline is
	 * rendered by the host's font stack, so one shot anywhere but the dev
	 * container is a baseline nobody else reproduces, and a baseline that does
	 * not reproduce is worse than none (see README). Generate it once, in the
	 * dev container, with:
	 *
	 *   npx playwright test --project visual --update-snapshots
	 *
	 * and commit the PNG. Until then this test fails loudly with a missing
	 * snapshot rather than passing over nothing.
	 */
	test('Intake inbox', async ({ page }) => {
		await shootSurface(page, `${APP}/#/intake`, 'IntakeIndex.png')
	})
})
