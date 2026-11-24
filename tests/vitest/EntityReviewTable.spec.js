/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Certain and uncertain findings in the review table (anonymization-review-workbench
 * REQ-DDARW-013): the badge, the filter and the counter by the organisation's
 * certain-from, the default inclusion of an uncertain finding, and the explicit
 * decision the check sends; plus the rendition pane the workbench shows (REQ-DDARW-012).
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
 */

import { join } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), delete: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))
vi.mock('../../src/store/store.js', () => ({ anonymizationStore: {} }))
vi.mock('../../src/services/bases.js', () => ({ fetchBaseOptions: async () => [] }))
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
	translatePlural: (app, one, many, count, vars) =>
		(count === 1 ? one : many).replace(/\{(\w+)\}/g, (_, k) => String(vars?.[k] ?? '')),
}))

const {
	checkReview,
	decidedUncertainRelationIds,
	findingValues,
	isUncertain,
	renditionPane,
} = await import('../../src/services/documentReview.js')

const THRESHOLDS = { floor: 0.6, certainFrom: 0.9, floorConfigurable: true }

/**
 * Review rows as the anonymisation store builds them.
 *
 * @return {Array<object>} The rows.
 */
function rows() {
	return [
		{ type: 'PERSON', value: 'Jan de Vries', highestConfidence: 0.95, included: true, relationIds: [1] },
		{ type: 'PERSON', value: 'J. Jansen', highestConfidence: 0.65, included: true, relationIds: [2, 3] },
		{ type: 'EMAIL', value: 'a@b.nl', highestConfidence: 0.3, included: true, detectionMethod: 'manual', relationIds: [4] },
	]
}

/**
 * The table's logic over the given rows and thresholds.
 *
 * @param {Array<object>} entities The rows.
 * @param {object|null} thresholds The two numbers.
 * @return {Promise<object>} The instance.
 */
async function table(entities, thresholds = THRESHOLDS) {
	const options = await loadSfcOptions(join(import.meta.dirname, '../../src/views/anonymization/EntityReviewTable.vue'))
	return createInstance(options, { props: { entities, thresholds } })
}

beforeEach(() => {
	axios.post.mockReset()
})

describe('certain and uncertain findings', () => {
	it('marks a detected finding below certain-from uncertain, at or above it certain, and a manual one certain', () => {
		expect(isUncertain({ highestConfidence: 0.65 }, 0.9)).toBe(true)
		expect(isUncertain({ highestConfidence: 0.9 }, 0.9)).toBe(false)
		expect(isUncertain({ confidence: 0.5 }, 0.9)).toBe(true)
		expect(isUncertain({ highestConfidence: 0.3, detectionMethod: 'manual' }, 0.9)).toBe(false)
		expect(isUncertain({ highestConfidence: 0.3, method: 'manual' }, 0.9)).toBe(false)
		expect(isUncertain({ highestConfidence: 0.3 }, null)).toBe(false)
	})

	it('counts the uncertain findings and filters on them', async () => {
		const vm = await table(rows())

		expect(vm.certainFrom).toBe(0.9)
		expect(vm.uncertainCount).toBe(1)
		expect(vm.filteredEntities.map((i) => i.e.value)).toEqual(['Jan de Vries', 'J. Jansen', 'a@b.nl'])

		vm.uncertainOnly = true
		expect(vm.filteredEntities.map((i) => i.e.value)).toEqual(['J. Jansen'])
	})

	it('gives the badge per row from the same rule', async () => {
		const vm = await table(rows())
		const badges = vm.entities.map((e) => (vm.isUncertain(e, vm.certainFrom) ? 'uncertain' : 'certain'))

		expect(badges).toEqual(['certain', 'uncertain', 'certain'])
	})

	it('pre-selects an uncertain finding for redaction', async () => {
		const vm = await table(rows())
		const uncertain = vm.entities.find((e) => vm.isUncertain(e, vm.certainFrom))

		expect(uncertain.included).toBe(true)
		expect(vm.selectedCount).toBe(3)
	})

	it('shows no split, badge or filter without thresholds', async () => {
		const vm = await table(rows(), null)

		expect(vm.certainFrom).toBe(null)
		expect(vm.uncertainCount).toBe(0)
		vm.uncertainOnly = true
		expect(vm.filteredEntities).toHaveLength(3)
	})

	it('records the reviewer\'s include, skip or confirm as an explicit decision', async () => {
		const vm = await table(rows())
		const [, uncertain] = vm.entities

		expect(decidedUncertainRelationIds(vm.entities, 0.9)).toEqual([])

		vm.confirmIncluded({ e: uncertain, idx: 1 })
		expect(vm.$emitted).toEqual([['decide', 1]])
		expect(decidedUncertainRelationIds(vm.entities, 0.9)).toEqual([2, 3])

		const certain = vm.entities[0]
		vm.onIncludeToggle({ e: certain, idx: 0 })
		expect(vm.$emitted[1]).toEqual(['toggle', 0])
		// Only uncertain findings are sent: a certain one needs no decision.
		expect(decidedUncertainRelationIds(vm.entities, 0.9)).toEqual([2, 3])
	})
})

describe('the check and its refusal', () => {
	it('sends the by-eye confirmation and the decided findings only when there are any', async () => {
		axios.post.mockResolvedValue({ data: { checked: true } })

		await checkReview(7)
		await checkReview(7, 'p. 3', { byEye: true, decided: [2, 3] })
		await checkReview(7, '', { byEye: false, decided: [] })

		expect(axios.post.mock.calls.map((c) => c[1])).toEqual([
			{ note: '' },
			{ note: 'p. 3', byEye: true, decided: [2, 3] },
			{ note: '' },
		])
	})

	it('names the undecided findings by their value', () => {
		expect(findingValues(rows(), [{ relationId: 3 }, { relationId: 2 }, { relationId: 99 }])).toEqual(['J. Jansen', '#99'])
	})
})

describe('the rendition pane', () => {
	const original = { fileId: 5, fileName: 'besluit.docx', mimeType: 'application/msword', path: '/Woo/2026/besluit.docx' }

	it('shows the converted PDF next to its source', () => {
		expect(renditionPane(original, { kind: 'converted', fileId: 6, fileName: 'besluit.pdf' })).toEqual({
			fileId: 6,
			fileName: 'besluit.pdf',
			mimeType: 'application/pdf',
			path: '/Woo/2026/besluit.pdf',
		})
	})

	it('keeps the source for a PDF used as it is, a scan, no rendition or no path', () => {
		expect(renditionPane(original, { kind: 'native-pdf', fileId: 5 })).toBe(original)
		expect(renditionPane(original, { kind: 'ocr-text-layer', fileId: 5 })).toBe(original)
		expect(renditionPane(original, null)).toBe(original)
		expect(renditionPane({ ...original, path: '' }, { kind: 'converted', fileId: 6, fileName: 'besluit.pdf' }).fileId).toBe(5)
	})
})
