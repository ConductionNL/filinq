/**
 * Golden: what resolveI18nValue returns for every kind of value and active
 * language, recorded on the implementation where the language pick ran
 * inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing register-i18n resolution
 */

import { describe, it, vi } from 'vitest'
import { resolveI18nValue } from '../../src/utils/registerI18n.js'
import { golden } from './golden/golden.js'

let language = 'nl'

vi.mock('@nextcloud/l10n', () => ({
	getLanguage: () => language,
}))

const VALUES = [
	null,
	undefined,
	'',
	'plain',
	0,
	12,
	false,
	true,
	['a'],
	{},
	{ nl: 'Naam', en: 'Name', de: 'Name DE' },
	{ nl: '', en: 'Name' },
	{ nl_NL: 'Regio', nl: 'Naam' },
	{ 'pt-BR': 'Nome', pt: 'Nome PT' },
	{ de: 'Name DE', fr: 'Nom' },
	{ de: '', fr: 7, es: 'Nombre' },
	{ nl: 3, en: null, x: { nl: 'deep' } },
	{ '': 'empty key', en: '' },
]

const LANGUAGES = ['nl', 'nl_NL', 'pt-BR', 'en', 'fr', '', null, undefined]

describe('resolveI18nValue golden', () => {
	it('resolves every value in every language', () => {
		const out = []
		for (const lang of LANGUAGES) {
			language = lang
			out.push({
				lang: lang === undefined ? '(undefined)' : lang,
				plain: VALUES.map((v) => resolveI18nValue(v)),
				fallback: VALUES.map((v) => resolveI18nValue(v, 'FB')),
			})
		}
		golden('resolveI18nValue', out)
	})
})
