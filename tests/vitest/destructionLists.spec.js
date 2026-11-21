/**
 * Destruction lists: the calls to OpenRegister's destruction-list routes, the
 * rows the page shows, and the reasons an approval or rejection cannot go
 * without.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const get = vi.fn()
const post = vi.fn()
vi.mock('@nextcloud/axios', () => ({
	default: { get: (...args) => get(...args), post: (...args) => post(...args) },
}))
vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))

const {
	approvalBody,
	approveDestructionList,
	DECIDABLE_STATUSES,
	entryRows,
	exclusionsWithoutReason,
	isDecidable,
	listStatus,
	loadDestructionList,
	rejectDestructionList,
} = await import('../../src/services/destructionLists.js')

const LISTS = '/index.php/apps/openregister/api/archival/destruction-lists'

describe('destruction lists', () => {
	beforeEach(() => {
		get.mockReset()
		post.mockReset()
	})

	it('knows which statuses still wait for a decision', () => {
		expect(DECIDABLE_STATUSES).toEqual(['in_review', 'awaiting_second_approval'])
		expect(isDecidable('in_review')).toBe(true)
		expect(isDecidable('awaiting_second_approval')).toBe(true)
		expect(isDecidable('approved')).toBe(false)
		expect(isDecidable('rejected')).toBe(false)
		expect(isDecidable('executed')).toBe(false)
		expect(isDecidable('')).toBe(false)
	})

	it('reads one list by its uuid', async () => {
		get.mockResolvedValue({ data: { status: 'in_review', objects: [] } })

		const result = await loadDestructionList('a/b')

		expect(get).toHaveBeenCalledWith(LISTS + '/a%2Fb')
		expect(result).toEqual({
			ok: true,
			data: { status: 'in_review', objects: [] },
		})
	})

	it("hands on OpenRegister's refusal of a non-archivist", async () => {
		get.mockRejectedValue({
			response: {
				status: 403,
				data: { error: 'Onvoldoende rechten: archivaris rol is vereist' },
			},
		})

		const result = await loadDestructionList('l-1')

		expect(result).toEqual({
			ok: false,
			status: 403,
			error: 'Onvoldoende rechten: archivaris rol is vereist',
		})
	})

	it('lists each proposed object with title, schema, register, action date and category', () => {
		const rows = entryRows({
			status: 'in_review',
			objects: [
				{
					uuid: 'o-1',
					title: 'Brief aan inwoner',
					schema: 12,
					register: 7,
					archiefactiedatum: '2026-01-01',
					classification: '1.1',
				},
				{ uuid: 'o-2', title: null, schema: null, register: undefined },
				{ title: 'no uuid' },
			],
		})

		expect(rows).toEqual([
			{
				uuid: 'o-1',
				title: 'Brief aan inwoner',
				schema: '12',
				register: '7',
				archiefactiedatum: '2026-01-01',
				classification: '1.1',
			},
			{
				uuid: 'o-2',
				title: 'o-2',
				schema: '',
				register: '',
				archiefactiedatum: '',
				classification: '',
			},
		])
		expect(entryRows({})).toEqual([])
		expect(listStatus({ status: 'approved' })).toBe('approved')
		expect(listStatus(null)).toBe('')
	})

	it('names every excluded object that still has no reason', () => {
		expect(
			exclusionsWithoutReason(['a', 'b', 'c'], { a: 'Beroep loopt', b: '  ' }),
		).toEqual(['b', 'c'])
		expect(exclusionsWithoutReason(['a'], null)).toEqual(['a'])
		expect(exclusionsWithoutReason(null, {})).toEqual([])
	})

	it('approves in full when nothing is excluded, and in part with each reason otherwise', () => {
		expect(approvalBody([], {})).toEqual({
			action: 'approve_all',
			excluded: [],
			exclusionReasons: {},
		})
		expect(approvalBody(['a', 'a'], { a: ' Beroep loopt ' })).toEqual({
			action: 'approve_partial',
			excluded: ['a'],
			exclusionReasons: { a: 'Beroep loopt' },
		})
		expect(() => approvalBody(['a', 'b'], { a: 'Beroep loopt' })).toThrow('b')
	})

	it('posts a partial approval to OpenRegister with the excluded uuid and its reason', async () => {
		post.mockResolvedValue({ data: { status: 'approved' } })

		const result = await approveDestructionList('l-1', ['o-2'], {
			'o-2': 'Beroep loopt',
		})

		expect(post).toHaveBeenCalledWith(LISTS + '/l-1/approve', {
			action: 'approve_partial',
			excluded: ['o-2'],
			exclusionReasons: { 'o-2': 'Beroep loopt' },
		})
		expect(result).toEqual({ ok: true, data: { status: 'approved' } })
	})

	it('sends no approval while an excluded object lacks a reason', async () => {
		const result = await approveDestructionList('l-1', ['o-2'], {})

		expect(post).not.toHaveBeenCalled()
		expect(result).toEqual({
			ok: false,
			status: 0,
			error: '',
			missingReasons: ['o-2'],
		})
	})

	it('posts a rejection with its reason, and sends none without one', async () => {
		post.mockResolvedValue({ data: { status: 'rejected' } })

		const refused = await rejectDestructionList('l-1', '   ')
		expect(post).not.toHaveBeenCalled()
		expect(refused).toEqual({
			ok: false,
			status: 0,
			error: '',
			missingReason: true,
		})

		const result = await rejectDestructionList('l-1', ' Lijst onvolledig ')
		expect(post).toHaveBeenCalledWith(LISTS + '/l-1/reject', {
			reason: 'Lijst onvolledig',
		})
		expect(result).toEqual({ ok: true, data: { status: 'rejected' } })
	})
})
