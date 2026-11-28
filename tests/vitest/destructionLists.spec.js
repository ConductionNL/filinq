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
vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) =>
		text.replace(/{(\w+)}/g, (match, key) =>
			vars && key in vars ? vars[key] : match,
		),
}))

const {
	approvalBody,
	approveDestructionList,
	DECIDABLE_STATUSES,
	entryRows,
	exclusionsWithoutReason,
	holdLabel,
	isDecidable,
	listStatus,
	loadDestructionList,
	loadHolds,
	loadTitles,
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

	// @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
	it("asks filinq's hold register about each proposed record once", async () => {
		get.mockImplementation((url) =>
			Promise.resolve({
				data: url.endsWith('/o-1')
					? { held: true, cases: [{ name: 'Beroep Jansen' }] }
					: { held: false, cases: [] },
			}),
		)

		const holds = await loadHolds(['o-1', 'o-2', 'o-1', '', 7])

		expect(get).toHaveBeenCalledTimes(2)
		expect(get).toHaveBeenCalledWith(
			'/index.php/apps/filinq/api/legal-holds/status/o-1',
		)
		expect(holds).toEqual({
			'o-1': { held: true, cases: [{ name: 'Beroep Jansen' }] },
			'o-2': { held: false, cases: [] },
		})
	})

	// @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
	it('leaves out a record whose hold status could not be read', async () => {
		const read = vi.fn((uuid) =>
			uuid === 'o-1'
				? Promise.reject(new Error('down'))
				: Promise.resolve(uuid === 'o-2' ? null : { held: true }),
		)

		expect(await loadHolds(['o-1', 'o-2', 'o-3'], read)).toEqual({
			'o-3': { held: true },
		})
		expect(await loadHolds(null, read)).toEqual({})
	})

	// @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
	it('reads each schema id once and names it by its title, leaving unreadable ids out', async () => {
		const read = vi.fn((url) => {
			if (url.endsWith('/schemas/45')) {
				return Promise.resolve({ id: 45, title: 'Generated Document' })
			}
			if (url.endsWith('/schemas/46')) {
				return Promise.reject(new Error('forbidden'))
			}
			return Promise.resolve({ id: 47, title: ' ' })
		})

		expect(await loadTitles('schemas', ['45', '45', '46', '47', '', 9], read)).toEqual({
			45: 'Generated Document',
		})
		expect(read).toHaveBeenCalledTimes(3)
		expect(read).toHaveBeenCalledWith('/index.php/apps/openregister/api/schemas/45')
		expect(await loadTitles('registers', null, read)).toEqual({})
	})

	// @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
	it('reads titles through OpenRegister by default', async () => {
		get.mockResolvedValueOnce({ data: { title: 'Filinq Register' } })

		expect(await loadTitles('registers', ['26'])).toEqual({ 26: 'Filinq Register' })
		expect(get).toHaveBeenCalledWith('/index.php/apps/openregister/api/registers/26')
	})

	// @spec openspec/changes/archiefwet-retention-engine/tasks.md#task-5.1
	it('names the holding case to hold authority and only says "held" to everybody else', () => {
		expect(holdLabel(undefined)).toBe('')
		expect(holdLabel({ held: false, cases: [{ name: 'x' }] })).toBe('')
		expect(holdLabel({ held: true })).toBe('Under a legal hold')
		expect(
			holdLabel({ held: true, cases: [{ uuid: 'c-1' }, { name: ' ' }] }),
		).toBe('Under a legal hold')
		expect(
			holdLabel({
				held: true,
				cases: [{ name: 'Beroep Jansen' }, { name: 'Audit 2026' }],
			}),
		).toBe('Under a legal hold: Beroep Jansen, Audit 2026')
	})
})
