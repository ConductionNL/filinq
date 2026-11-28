/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The review workbench helpers: the checked-mark calls, the badges, the
 * proposed-grondslag marker and the selection pre-fill.
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.3
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'

const axios = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), delete: vi.fn() }))
vi.mock('@nextcloud/axios', () => ({ default: axios }))

const {
	anonymizedFileOf,
	basesAreProposed,
	checkReview,
	fetchAnonymizationLink,
	fetchReview,
	manualEntityPayload,
	policyBadges,
	proposedBasesFor,
	reviewState,
	selectedTextIn,
	uncheckReview,
} = await import('../../src/services/documentReview.js')

beforeEach(() => {
	axios.get.mockReset()
	axios.post.mockReset()
	axios.delete.mockReset()
})

describe('the checked-mark calls', () => {
	it('reads, sets and clears the mark on the file\'s review endpoint', async () => {
		axios.get.mockResolvedValue({ data: { checked: false } })
		axios.post.mockResolvedValue({ data: { checked: true } })
		axios.delete.mockResolvedValue({ data: { checked: false, removed: true } })

		expect(await fetchReview(7)).toEqual({ checked: false })
		expect(await checkReview(7, 'p. 4')).toEqual({ checked: true })
		expect(await uncheckReview(7)).toEqual({ checked: false, removed: true })

		expect(axios.get).toHaveBeenCalledWith(expect.stringMatching(/\/apps\/filinq\/api\/review\/7$/))
		expect(axios.post).toHaveBeenCalledWith(expect.stringMatching(/\/apps\/filinq\/api\/review\/7\/check$/), { note: 'p. 4' })
		expect(axios.delete).toHaveBeenCalledWith(expect.stringMatching(/\/apps\/filinq\/api\/review\/7\/check$/))
	})

	it('names the state the card shows', () => {
		expect(reviewState({ checked: true, stale: false })).toBe('reviewed')
		expect(reviewState({ checked: false, stale: true })).toBe('stale')
		expect(reviewState({ checked: false, stale: false })).toBe('unchecked')
		expect(reviewState(null)).toBe('unknown')
	})
})

describe('the policy badges', () => {
	it('shows the prohibition, linked to the publish-never list', () => {
		expect(policyBadges({ prohibitionMatch: { ruleId: 'p-1', ruleName: 'Getuige' }, standingConsentMatch: null })).toEqual([
			{ kind: 'prohibition', ruleId: 'p-1', ruleName: 'Getuige', route: { name: 'Prohibitions', query: { rule: 'p-1' } } },
		])
	})

	it('shows the standing consent, linked to the publish-always list', () => {
		expect(policyBadges({ prohibitionMatch: null, standingConsentMatch: { ruleId: 'c-1', ruleName: 'Gemeente' } })).toEqual([
			{ kind: 'standingConsent', ruleId: 'c-1', ruleName: 'Gemeente', route: { name: 'StandingConsents', query: { rule: 'c-1' } } },
		])
	})

	it('shows only the prohibition when both are set, and nothing without a rule', () => {
		const both = policyBadges({ prohibitionMatch: { ruleId: 'p-1' }, standingConsentMatch: { ruleId: 'c-1' } })
		expect(both.map((b) => b.kind)).toEqual(['prohibition'])
		expect(policyBadges({})).toEqual([])
		expect(policyBadges(null)).toEqual([])
	})
})

describe('the proposed grondslag', () => {
	it('is proposed when a rule or the mapping filled it, until the reviewer confirms', () => {
		expect(basesAreProposed({ bases: ['a'], basesSource: 'rule' })).toBe(true)
		expect(basesAreProposed({ _decisionBases: ['a'], basesProposed: true })).toBe(true)
		expect(basesAreProposed({ _decisionBases: ['a'], basesSource: 'rule', _basesConfirmed: true })).toBe(false)
		expect(basesAreProposed({ _decisionBases: [], basesSource: 'rule' })).toBe(false)
		expect(basesAreProposed({ _decisionBases: ['a'] })).toBe(false)
	})

	it('is proposed when the bases are exactly what the mapping proposes for the type', () => {
		const mapping = { EMAIL: ['5.1.2.e'], BSN: '5.1.1.e' }
		expect(basesAreProposed({ type: 'EMAIL', bases: ['5.1.2.e'] }, mapping)).toBe(true)
		expect(basesAreProposed({ type: 'BSN', bases: ['5.1.1.e'] }, mapping)).toBe(true)
		expect(basesAreProposed({ type: 'EMAIL', bases: ['5.1.2.e'], _basesConfirmed: true }, mapping)).toBe(false)
		expect(basesAreProposed({ type: 'EMAIL', bases: ['5.1.2.e', '5.1.1.d'] }, mapping)).toBe(false)
		expect(basesAreProposed({ type: 'EMAIL', _decisionBases: ['5.1.1.d'], bases: ['5.1.2.e'] }, mapping)).toBe(false)
		expect(basesAreProposed({ type: 'PHONE', bases: ['5.1.2.e'] }, mapping)).toBe(false)
		expect(basesAreProposed({ type: 'EMAIL', bases: ['5.1.2.e'] }, null)).toBe(false)
	})

	it('reads the per-type mapping as a list', () => {
		const mapping = { BSN: 'woo-art-5-1-2-e', PERSON: ['woo-art-5-1-2-e', '', 3], EMAIL: '' }
		expect(proposedBasesFor(mapping, 'BSN')).toEqual(['woo-art-5-1-2-e'])
		expect(proposedBasesFor(mapping, 'PERSON')).toEqual(['woo-art-5-1-2-e'])
		expect(proposedBasesFor(mapping, 'EMAIL')).toEqual([])
		expect(proposedBasesFor(null, 'BSN')).toEqual([])
	})
})

describe('the selection pre-fill', () => {
	const pane = { contains: (node) => node === 'inside' }

	it('takes a selection inside the original pane, on one line', () => {
		const selection = { anchorNode: 'inside', focusNode: 'inside', toString: () => '  Pieter\n de  Vries ' }
		expect(selectedTextIn(selection, pane)).toBe('Pieter de Vries')
	})

	it('ignores a selection that leaves the pane, or none at all', () => {
		expect(selectedTextIn({ anchorNode: 'inside', focusNode: 'outside', toString: () => 'x' }, pane)).toBe('')
		expect(selectedTextIn(null, pane)).toBe('')
		expect(selectedTextIn({ anchorNode: 'inside', focusNode: 'inside', toString: () => 'x' }, null)).toBe('')
	})

	it('sends value, type and grondslagen, never offsets', () => {
		const payload = manualEntityPayload(' Pieter de Vries ', 'PERSON', ['woo-art-5-1-2-e'])
		expect(payload).toEqual({ value: 'Pieter de Vries', type: 'PERSON', bases: ['woo-art-5-1-2-e'] })
		expect(manualEntityPayload('x', 'BSN', null).bases).toEqual([])
	})
})

describe('the anonymised pane', () => {
	it('is the link\'s anonymised file, or pending without a link', () => {
		expect(anonymizedFileOf({ anonymizedFileId: 9, anonymizedFileName: 'a-gelakt.pdf', anonymizedFilePath: '/Filinq/a-gelakt.pdf' }))
			.toEqual({ fileName: 'a-gelakt.pdf', mimeType: '', path: '/Filinq/a-gelakt.pdf' })
		expect(anonymizedFileOf({ anonymizedFileId: 9, anonymizedFilePath: '/Filinq/b.docx' }).fileName).toBe('b.docx')
		expect(anonymizedFileOf(null)).toBeNull()
		expect(anonymizedFileOf({ sourceFileId: 1 })).toBeNull()
	})

	it('finds the link by source file, and none on a failed read', async () => {
		axios.get.mockResolvedValueOnce({ data: { results: [{ sourceFileId: '8' }, { sourceFileId: 7, anonymizedFileId: 9 }] } })
		expect(await fetchAnonymizationLink(7)).toEqual({ sourceFileId: 7, anonymizedFileId: 9 })
		expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('/apps/openregister/api/objects/filinq/anonymizationLink'), { params: { sourceFileId: 7 } })

		axios.get.mockRejectedValueOnce(new Error('down'))
		expect(await fetchAnonymizationLink(7)).toBeNull()
	})
})
