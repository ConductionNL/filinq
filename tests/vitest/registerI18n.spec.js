/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Regression tests for resolveI18nValue().
 *
 * Live-verify finding (2026-07-24, dev instance 8080): the seeded custom
 * dictionary rendered an EMPTY Label column because OpenRegister stores
 * register-i18n tagged fields as a per-language map (`{"nl":"Projectnamen"}`)
 * while the table bound the raw value. Every unit test passed because the
 * fixtures used plain strings — only a live run exposed it.
 *
 * @spec openspec/specs/register-i18n/spec.md
 */

import { describe, expect, it, vi } from 'vitest'

vi.mock('@nextcloud/l10n', () => ({
	getLanguage: () => 'en',
}))

const { resolveI18nValue } = await import('../../src/utils/registerI18n.js')

describe('resolveI18nValue', () => {
	it('returns a plain string unchanged', () => {
		expect(resolveI18nValue('Projectnamen')).toBe('Projectnamen')
	})

	it('resolves the active language from a per-language map', () => {
		expect(resolveI18nValue({ en: 'Project names', nl: 'Projectnamen' })).toBe(
			'Project names',
		)
	})

	it('falls back to the only available language (the live failure case)', () => {
		// This is exactly what was stored on the dev instance and rendered blank.
		expect(resolveI18nValue({ nl: 'Projectnamen' })).toBe('Projectnamen')
	})

	it('never returns an object', () => {
		const out = resolveI18nValue({ nl: 'Projectnamen' })
		expect(typeof out).toBe('string')
	})

	it('uses the fallback for null, undefined and empty maps', () => {
		expect(resolveI18nValue(null, 'Custom dictionary')).toBe('Custom dictionary')
		expect(resolveI18nValue(undefined, 'Custom dictionary')).toBe(
			'Custom dictionary',
		)
		expect(resolveI18nValue({}, 'Custom dictionary')).toBe('Custom dictionary')
	})

	it('ignores empty-string translations when picking a fallback', () => {
		expect(resolveI18nValue({ en: '', nl: 'Projectnamen' })).toBe('Projectnamen')
	})

	it('defaults to an empty string when no fallback is given', () => {
		expect(resolveI18nValue(null)).toBe('')
	})
})
