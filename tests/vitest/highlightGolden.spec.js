// @vitest-environment jsdom
/**
 * Golden: the highlight segments and the highlighted DOM, recorded on the
 * single-function implementations.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing highlighting behaviour
 */

import { describe, it } from 'vitest'
import {
	applyDomHighlights,
	clearDomHighlights,
} from '../../src/services/highlightDom.js'
import {
	buildHighlightSegments,
	PENDING_TYPE,
} from '../../src/services/highlightText.js'
import { golden } from './golden/golden.js'

const TEXTS = [
	'',
	null,
	'Jan Jansen woont in Utrecht, bij jan.',
	'aaaa',
	'Kuipers KUIPERS kuipers',
	'no match here',
	'İstanbul and istanbul',
	'Jan JansenJan',
	'JanJansen',
	'xaaa',
	' ',
	'room 0',
]

const ENTITY_SETS = [
	null,
	[],
	[{ value: 'Jan', type: 'PERSON' }],
	[
		{ value: 'Jan', type: 'PERSON' },
		{ value: 'Jan Jansen', type: 'PERSON' },
		{ value: 'Utrecht', type: 'LOCATION' },
	],
	[
		{ value: 'aa', type: 'X' },
		{ value: 'a', type: 'Y' },
	],
	[null, { value: '' }, { value: 0, type: 'N' }, { value: 'kuipers' }],
	[
		{ value: 'Kuipers', type: PENDING_TYPE },
		{ value: 'İ', type: 'I' },
	],
	[
		{ value: 'Jansen', type: 'B' },
		{ value: 'Jan', type: 'A' },
	],
	[
		{ value: 'xa', type: 'L' },
		{ value: 'aa', type: 'S' },
	],
	[{ value: ' ', type: 'SP' }],
]

const HTML = [
	'<p>Jan Jansen woont in <b>Utrecht</b>.</p><p>   </p><p>Bel Jan.</p>',
	'<div><span>Kuipers</span> en <i>kuipers</i><br>niets</div>',
	'<p>no match</p>',
]

/**
 * Count the text nodes under an element.
 *
 * @param {HTMLElement} root The element.
 * @return {number}
 */
function countTextNodes(root) {
	const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null)
	let count = 0
	while (walker.nextNode()) {
		count++
	}
	return count
}

describe('highlighting golden', () => {
	it('splits every text the recorded way', () => {
		golden(
			'buildHighlightSegments',
			TEXTS.flatMap((text) =>
				ENTITY_SETS.map((entities) =>
					buildHighlightSegments(text, entities),
				),
			),
		)
	})

	it('highlights and clears the DOM the recorded way', () => {
		const out = []
		for (const html of HTML) {
			for (const entities of ENTITY_SETS) {
				for (const pending of [undefined, '', 'woont', 'Jan']) {
					const root = document.createElement('div')
					root.innerHTML = html
					applyDomHighlights(root, entities, pending)
					const once = root.innerHTML
					applyDomHighlights(root, entities, pending)
					const twice = root.innerHTML
					applyDomHighlights(root, ENTITY_SETS[3], pending)
					const other = root.innerHTML
					clearDomHighlights(root)
					out.push({
						once,
						twice,
						other,
						cleared: root.innerHTML,
						textNodes: countTextNodes(root),
					})
				}
			}
		}
		applyDomHighlights(null, ENTITY_SETS[2])
		clearDomHighlights(null)
		golden('applyDomHighlights', out)
	})
})
