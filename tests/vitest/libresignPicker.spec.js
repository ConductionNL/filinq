/**
 * LibreSign in the signing provider picker.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The settings page is too large to mount here, so this reads its template:
 * the LibreSign option exists only when the server says LibreSign is
 * enabled, the qualified switch only when LibreSign is the provider, and a
 * configured-but-missing LibreSign shows an error instead of an empty choice.
 *
 * @spec openspec/changes/archive/2026-09-29-libresign-signing-provider/tasks.md#task-2.2
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it } from 'vitest'

const page = readFileSync(
	new URL('../../src/views/settings/Settings.vue', import.meta.url),
	'utf8',
)

describe('the signing provider picker', () => {
	it('offers LibreSign only when the LibreSign app is enabled', () => {
		expect(page).toMatch(/<option v-if="libresignAvailable" value="libresign">/)
		expect(page).toContain(
			'this.libresignAvailable = data.libresignAvailable === true',
		)
	})

	it('asks about a qualified certificate only for LibreSign, and saves it', () => {
		expect(page).toMatch(
			/v-if="settings\.signing_provider === 'libresign'"\s+class="setting-item"/,
		)
		expect(page).toContain(
			"libresign_qualified: this.settings.libresign_qualified ? '1' : '0'",
		)
	})

	it('says so when LibreSign is chosen but not enabled', () => {
		expect(page).toMatch(
			/settings\.signing_provider === 'libresign'\s+&& !libresignAvailable/,
		)
	})
})
