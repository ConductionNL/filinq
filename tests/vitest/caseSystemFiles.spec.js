/**
 * "In the case system" on My Documents and the delivery counts on the
 * documents report: one lookup per page, and two stat widgets that count
 * waiting and failed deliveries.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { effectiveManifest } from './support/effectiveManifest.js'

const calls = []
let answer = {}
vi.mock('@nextcloud/axios', () => ({
	default: {
		get: (url, config) => {
			calls.push(['get', url, config])
			return Promise.resolve({ data: answer })
		},
	},
}))

const { fetchCaseSystemFiles } = await import('../../src/services/caseSystemFiles.js')

beforeEach(() => {
	calls.length = 0
	answer = {}
})

describe('fetchCaseSystemFiles', () => {
	it('does not call the server for no files', async () => {
		expect(await fetchCaseSystemFiles([])).toEqual({})
		expect(await fetchCaseSystemFiles()).toEqual({})
		expect(calls).toEqual([])
	})

	it('asks once for the whole page and answers the delivered files by id', async () => {
		answer = { delivered: [3, 9] }

		const delivered = await fetchCaseSystemFiles([3, 5, 9])

		expect(calls).toEqual([
			['get', '/index.php/apps/filinq/api/bridge/case-system/files', { params: { fileIds: '3,5,9' } }],
		])
		expect(delivered).toEqual({ 3: true, 9: true })
	})

	it('reads an answer without a list as nothing delivered', async () => {
		answer = { error: 'x' }
		expect(await fetchCaseSystemFiles([1])).toEqual({})
	})
})

describe('documents report', () => {
	const page = effectiveManifest().pages.find((p) => p.id === 'ProducedDocumentsReport')
	const widget = (id) => page.config.widgets.find((w) => w.id === id)

	it('counts the deliveries waiting for and failed to reach the case system', () => {
		expect(widget('deliveries-waiting').content.source).toEqual({
			register: 'filinq',
			schema: 'caseSystemDelivery',
			metric: 'count',
			filter: { deliveryStatus: 'ready_for_writeback' },
		})
		expect(widget('deliveries-failed').content.source.filter).toEqual({ deliveryStatus: 'writeback_failed' })
	})

	it('lays both out on their own row without overlapping another widget', () => {
		const cells = page.config.layout
		const ids = cells.map((cell) => cell.widgetId)
		expect(ids).toContain('deliveries-waiting')
		expect(ids).toContain('deliveries-failed')
		for (const a of cells) {
			for (const b of cells) {
				if (a === b) continue
				const overlapX = a.gridX < b.gridX + b.gridWidth && b.gridX < a.gridX + a.gridWidth
				const overlapY = a.gridY < b.gridY + b.gridHeight && b.gridY < a.gridY + a.gridHeight
				expect(overlapX && overlapY).toBe(false)
			}
		}
		expect(new Set(cells.map((cell) => cell.id)).size).toBe(cells.length)
	})
})
