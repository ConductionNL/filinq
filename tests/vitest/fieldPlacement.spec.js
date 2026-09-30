/**
 * Field placement editing: boxes stay on the page, and the list the form
 * sends matches what the server accepts.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/bulk-signing-field-builder/tasks.md#task-3.3
 */

import { describe, expect, it } from 'vitest'
import {
	addPlacement,
	dropSignerPlacements,
	FIELD_TYPES,
	movePlacement,
	resizePlacement,
	toRequestPlacements,
} from '../../src/views/signing/fieldPlacement.js'

describe('field placement', () => {
	it('offers the five static types and no condition', () => {
		expect(FIELD_TYPES).toEqual([
			'signature',
			'initials',
			'date',
			'text',
			'checkbox',
		])
	})

	it('adds a box centred on the click, kept on the page', () => {
		const [box] = addPlacement([], {
			signerIndex: 1,
			page: 2,
			type: 'signature',
			x: 0.99,
			y: 0.5,
		})
		expect(box.page).toBe(2)
		expect(box.signerIndex).toBe(1)
		expect(box.x + box.width).toBeLessThanOrEqual(1)
		expect(box.width).toBeGreaterThan(0)
		expect(box.y).toBeCloseTo(0.5 - box.height / 2)
	})

	it('moves a box but never off the page', () => {
		const list = addPlacement([], {
			signerIndex: 0,
			page: 1,
			type: 'date',
			x: 0.5,
			y: 0.5,
		})
		const moved = movePlacement(list, 0, -2, 3)
		expect(moved[0].x).toBe(0)
		expect(moved[0].y + moved[0].height).toBeCloseTo(1)
		expect(list[0].x).not.toBe(0)
	})

	it('resizes a box within the page and above a minimum', () => {
		const list = addPlacement([], {
			signerIndex: 0,
			page: 1,
			type: 'text',
			x: 0.5,
			y: 0.5,
		})
		const big = resizePlacement(list, 0, 5, 5)
		expect(big[0].x + big[0].width).toBeCloseTo(1)
		expect(big[0].y + big[0].height).toBeCloseTo(1)
		const small = resizePlacement(list, 0, -5, -5)
		expect(small[0].width).toBeGreaterThan(0)
		expect(small[0].height).toBeGreaterThan(0)
	})

	it('drops the boxes of a removed signer and renumbers the rest', () => {
		let list = addPlacement([], {
			signerIndex: 0,
			page: 1,
			type: 'signature',
			x: 0.3,
			y: 0.3,
		})
		list = addPlacement(list, {
			signerIndex: 1,
			page: 1,
			type: 'signature',
			x: 0.6,
			y: 0.6,
		})
		list = addPlacement(list, {
			signerIndex: 2,
			page: 1,
			type: 'initials',
			x: 0.6,
			y: 0.8,
		})
		const after = dropSignerPlacements(list, 1)
		expect(after.map((p) => p.signerIndex)).toEqual([0, 1])
		expect(after[1].type).toBe('initials')
	})

	it('sends exactly the keys the server accepts, rounded', () => {
		const list = addPlacement([], {
			signerIndex: 0,
			page: 1,
			type: 'checkbox',
			x: 0.123456789,
			y: 0.5,
		})
		const [sent] = toRequestPlacements(list)
		expect(Object.keys(sent).sort()).toEqual([
			'height',
			'page',
			'signerIndex',
			'type',
			'width',
			'x',
			'y',
		])
		expect(String(sent.x).length).toBeLessThanOrEqual(6)
	})
})
