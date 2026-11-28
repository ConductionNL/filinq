/**
 * The English browser catalogue carries only what differs from its key.
 *
 * scripts/build-l10n-js.js drops identity entries (key -> key) from en.js,
 * because `t()` falls back to the key anyway and those entries were almost
 * all of a 469 KB file every page loaded. en.json stays complete, and every
 * other locale keeps every entry.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec ADR-124 (speed budget: page usable <= 300 ms warm)
 */

import { readFileSync } from 'node:fs'
import { createRequire } from 'node:module'
import path from 'node:path'
import { describe, expect, it } from 'vitest'

const require = createRequire(import.meta.url)
const { browserEntries } = require('../../scripts/build-l10n-js.js')
const {
	loadJsTranslations,
	loadSourceCatalogue,
} = require('../../scripts/lib/l10n.js')

const L10N_DIR = path.resolve(__dirname, '../../l10n')

/**
 * Read the translations of one locale's .json catalogue.
 *
 * @param {string} locale - e.g. `en`
 * @return {object} key -> translation
 */
function jsonTranslations(locale) {
	return JSON.parse(readFileSync(path.join(L10N_DIR, `${locale}.json`), 'utf8'))
		.translations
}

describe('browserEntries', () => {
	it('drops identity entries for English only', () => {
		const translations = {
			Save: 'Save',
			'Sign doc': 'Sign the document',
			Close: 'Close',
		}
		expect(browserEntries('en', translations)).toEqual({
			'Sign doc': 'Sign the document',
		})
		expect(browserEntries('en_GB', translations)).toEqual({
			'Sign doc': 'Sign the document',
		})
	})

	it('keeps plural entries, whose value is a list', () => {
		const translations = { '_%n file_::_%n files_': ['%n file', '%n files'] }
		expect(browserEntries('en', translations)).toEqual(translations)
	})

	it('keeps every entry of another locale, identical or not', () => {
		const translations = { PDF: 'PDF', Save: 'Opslaan' }
		expect(browserEntries('nl', translations)).toBe(translations)
	})
})

describe('the committed catalogues', () => {
	it('en.js holds no identity entry', () => {
		const { translations } = loadJsTranslations(path.join(L10N_DIR, 'en.js'))
		const identities = Object.keys(translations).filter(
			(key) => translations[key] === key,
		)
		expect(identities).toEqual([])
	})

	it('en.js carries every English entry that differs from its key', () => {
		const json = jsonTranslations('en')
		const { translations } = loadJsTranslations(path.join(L10N_DIR, 'en.js'))
		for (const key of Object.keys(json)) {
			if (json[key] !== key) {
				expect(translations[key]).toEqual(json[key])
			}
		}
	})

	it('nl.js keeps every nl.json entry', () => {
		const { translations } = loadJsTranslations(path.join(L10N_DIR, 'nl.js'))
		expect(translations).toEqual(jsonTranslations('nl'))
	})

	it('the source catalogue still lists every en.json key', () => {
		const { app, translations } = loadSourceCatalogue(L10N_DIR)
		expect(app).toBe('filinq')
		expect(Object.keys(translations).sort()).toEqual(
			Object.keys(jsonTranslations('en')).sort(),
		)
	})
})
