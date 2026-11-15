/**
 * Golden: the match mode and active badges of the custom dictionary index and
 * detail pages, rendered for every match mode and active state.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the custom dictionary badges
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { renderSfc } from './sfc/sfcRender.js'

const store = vi.hoisted(() => ({
	customDictionaryStore: {
		canMaintain: true,
		loading: false,
		dictionaryItem: null,
		terms: [],
		dictionaries: [],
	},
}))

vi.mock('@nextcloud/l10n', () => ({
	getLanguage: () => 'nl',
	translate: (app, text, vars) =>
		'[' + text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')) + ']',
}))

vi.mock('../../src/store/store.js', () => store)

const PAGES = join(import.meta.dirname, '../../src/views/customDictionary')

const CASES = {
	exactActive: { matchMode: 'exact', active: true },
	caseInsensitiveInactive: { matchMode: 'caseInsensitive', active: false },
	wordBoundaryUnset: { matchMode: 'wordBoundary' },
	unknownInactive: { matchMode: 'odd', active: false },
	emptyActive: { matchMode: '', active: true },
	nothing: {},
}

/**
 * The lines of the rendered HTML that carry a status badge.
 *
 * @param {string} html Rendered HTML.
 * @return {Array<string>} The badge lines.
 */
function badges(html) {
	return html.split('\n').filter((line) => line.includes('CnStatusBadge'))
}

describe('custom dictionary badges', () => {
	for (const [name, row] of Object.entries(CASES)) {
		it(name, async () => {
			store.customDictionaryStore.dictionaryItem = {
				label: 'Dict',
				...row,
			}
			const index = await renderSfc(join(PAGES, 'CustomDictionaryIndex.vue'), {
				slotProps: Object.fromEntries(
					[
						'column-label',
						'column-termCount',
						'column-matchMode',
						'column-active',
						'row-actions',
					].map((slot) => [slot, { row }]),
				),
			})
			const detail = await renderSfc(join(PAGES, 'CustomDictionaryDetail.vue'))
			golden('custom-dictionary-badges-' + name, {
				index: badges(index),
				detail: badges(detail),
			})
		})
	}
})
