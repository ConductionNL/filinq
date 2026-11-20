/**
 * The review card says a colleague must anonymise (redaction-four-eyes-setting
 * REQ-RWB-09, task 5): the anonymise button is disabled for the person who
 * checked the document when the setting asks for four eyes, and a same_person
 * refusal from the server keeps the check mark instead of sending the document
 * back to review.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-20-redaction-four-eyes-setting/specs/redaction-output-guarantee/spec.md#requirement-the-person-who-anonymises-differs-from-the-person-who-checked-req-rwb-09
 */

import { readFileSync } from 'node:fs'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ANONYMISER_UNKNOWN, colleagueHint, colleagueReason, fourEyesApplies, SAME_PERSON } from '../../src/services/fourEyes.js'
import { reviewSignature } from '../../src/services/reviewMark.js'
import { useAnonymizationStore } from '../../src/store/modules/anonymization.js'

let refusal = null
vi.mock('@nextcloud/axios', () => {
	const axios = vi.fn(async () => ({ data: {} }))
	axios.patch = vi.fn(async () => ({ status: 200, data: {} }))
	axios.post = vi.fn(async () => {
		if (refusal) {
			throw refusal
		}
		return { data: {} }
	})
	return { default: axios }
})

const t = (app, text) => text
const MARK = { checkedBy: 'fenna', checkedAt: '2026-11-14T10:00:00+00:00' }

describe('who may anonymise', () => {
	it('asks for four eyes always, never when off, and on a Woo run by default', () => {
		expect(fourEyesApplies('always', false)).toBe(true)
		expect(fourEyesApplies('off', true)).toBe(false)
		expect(fourEyesApplies('woo', true)).toBe(true)
		expect(fourEyesApplies('woo', false)).toBe(false)
		expect(fourEyesApplies('sometimes', true)).toBe(true)
	})

	it('names the checker as the same person when the setting applies', () => {
		const entry = { reviewMark: MARK }
		expect(colleagueReason(entry, { mode: 'always', uid: 'fenna' })).toBe(SAME_PERSON)
		expect(colleagueReason(entry, { mode: 'always', uid: 'daan' })).toBe('')
		expect(colleagueReason(entry, { mode: 'woo', wooRun: false, uid: 'fenna' })).toBe('')
		expect(colleagueReason(entry, { mode: 'woo', wooRun: true, uid: 'fenna' })).toBe(SAME_PERSON)
		expect(colleagueReason(entry, { mode: 'off', uid: 'fenna' })).toBe('')
		expect(colleagueReason(entry, { mode: 'always', uid: '' })).toBe(ANONYMISER_UNKNOWN)
		expect(colleagueReason({}, { mode: 'always', uid: 'fenna' })).toBe('')
	})

	it('keeps a refusal the server sent, whatever the setting reads here', () => {
		expect(colleagueReason({ reviewMark: MARK, fourEyes: SAME_PERSON }, { mode: 'off', uid: 'daan' })).toBe(SAME_PERSON)
	})

	it('says a colleague must anonymise, or to sign in', () => {
		expect(colleagueHint(SAME_PERSON, t)).toBe('A colleague must anonymise this document')
		expect(colleagueHint(ANONYMISER_UNKNOWN, t)).toBe('Sign in to anonymise this document')
		expect(colleagueHint('', t)).toBe('')
		const nl = JSON.parse(readFileSync('l10n/nl.json', 'utf8')).translations
		expect(nl['A colleague must anonymise this document']).toBe('Een collega moet dit document anonimiseren')
	})
})

describe('a same_person refusal', () => {
	beforeEach(() => {
		setActivePinia(createPinia())
		refusal = null
		vi.spyOn(console, 'error').mockImplementation(() => {})
	})

	it('keeps the check mark and the document ready, with the server sentence', async () => {
		const store = useAnonymizationStore()
		const entry = { name: 'besluit.pdf', fileId: 7, status: 'extracted', entities: [], reviewMark: { ...MARK, signature: reviewSignature([]) } }
		const err = new Error('HTTP 409')
		err.response = { status: 409, data: { error: 'You checked the findings of this document yourself. Four eyes are required: a colleague must anonymise it.', reviewRequired: SAME_PERSON } }
		refusal = err

		await store.anonymiseEntry(entry)

		expect(entry.status).toBe('extracted')
		expect(entry.fourEyes).toBe(SAME_PERSON)
		expect(entry.reviewMark.checkedBy).toBe('fenna')
		expect(entry.reviewRequired).toBe(false)
		expect(entry.error).toContain('a colleague must anonymise it')
	})

	it('still sends a never_checked refusal back to review', async () => {
		const store = useAnonymizationStore()
		const entry = { name: 'besluit.pdf', fileId: 7, status: 'extracted', entities: [], reviewMark: { ...MARK, signature: reviewSignature([]) } }
		const err = new Error('HTTP 409')
		err.response = { status: 409, data: { error: 'Check the detected data first', reviewRequired: 'never_checked' } }
		refusal = err

		await store.anonymiseEntry(entry)

		expect(entry.reviewMark).toBeNull()
		expect(entry.reviewRequired).toBe(true)
		expect(entry.fourEyes).toBeUndefined()
	})
})

describe('the review card and the sidebar', () => {
	const card = readFileSync('src/components/ReviewMarkCard.vue', 'utf8')
	const sidebar = readFileSync('src/sidebars/FileViewerSidebar.vue', 'utf8')

	it('shows the colleague sentence on a checked document', () => {
		expect(card).toMatch(/v-if="checked && colleagueHint"[\s\S]*?data-testid="review-mark-colleague"[\s\S]*?\{\{ colleagueHint \}\}/)
	})

	it('disables both anonymise buttons while a colleague is required', () => {
		expect(sidebar.match(/:disabled="includedCount === 0 \|\| isAnonymising \|\| !entryChecked \|\| colleagueRequired"/g)).toHaveLength(2)
		expect(sidebar.match(/:colleagueHint="colleagueHintText"/g)).toHaveLength(2)
	})
})
