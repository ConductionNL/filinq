/**
 * The admin chooses when four eyes are required for anonymising
 * (redaction-four-eyes-setting REQ-SET-12): a radio group with the default
 * "Only for Woo cases" first, an unknown stored value read as that default,
 * and the choice sent on save.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/specs/admin-settings/spec.md#requirement-the-admin-chooses-when-four-eyes-are-required-req-set-12
 */

import { readFileSync } from 'node:fs'
import { describe, expect, it, vi } from 'vitest'
import { loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text, translatePlural: (app, one) => one }))
vi.mock('@nextcloud/axios', () => ({ default: { get: async () => ({ data: {} }), post: async () => ({ data: {} }) } }))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url, generateOcsUrl: (url) => url }))
vi.mock('@nextcloud/dialogs', () => ({ showError: () => {}, showSuccess: () => {} }))

globalThis.t = (app, text) => text

const page = readFileSync(new URL('../../src/views/settings/Settings.vue', import.meta.url), 'utf8')
const nl = JSON.parse(readFileSync(new URL('../../l10n/nl.json', import.meta.url), 'utf8')).translations

describe('Four eyes when anonymising', () => {
	it('offers woo, always and off, the default first', async () => {
		const options = await loadSfcOptions(new URL('../../src/views/settings/Settings.vue', import.meta.url).pathname)
		expect(options.computed.fourEyesOptions.call({})).toEqual([
			{ value: 'woo', label: 'Only for Woo cases' },
			{ value: 'always', label: 'Always' },
			{ value: 'off', label: 'Off' },
		])
	})

	it('is a radio group bound to redaction_four_eyes', () => {
		expect(page).toMatch(/v-for="option in fourEyesOptions"[\s\S]*?:modelValue="settings\.redaction_four_eyes"[\s\S]*?type="radio"/)
		expect(page).toContain('@update:modelValue="settings.redaction_four_eyes = $event"')
	})

	it('reads a fresh install, or a value it does not know, as Only for Woo cases', () => {
		expect(page).toMatch(/this\.settings\.redaction_four_eyes = \['woo', 'always', 'off'\]\.includes\(\s*data\.redaction_four_eyes,\s*\)\s*\?\s*data\.redaction_four_eyes\s*:\s*'woo'/)
	})

	it('sends the choice on save, so picking Off saves off', () => {
		expect(page).toContain("redaction_four_eyes: this.settings.redaction_four_eyes || 'woo',")
	})

	it('reads in Dutch as the board names it', () => {
		expect(nl['Four eyes when anonymising']).toBe('Vier ogen bij anonimiseren')
		expect(nl['Only for Woo cases']).toBe('Alleen bij Woo-zaken')
		expect(nl.Off).toBe('Uit')
	})
})
