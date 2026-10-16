/**
 * The leaf tabs on the document detail surface: which render, and when none do.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * 🔴 THIS ASSERTS THE CHOICE, AND THE E2E ASSERTS THE RENDER. The pair matters:
 * a test that only checks the selection cannot see a tab that never reaches a
 * page, and a test that only checks the page cannot say WHY a tab is missing.
 * `tests/e2e/workflows/document-detail-leaf-widgets.spec.ts` is the other half.
 *
 * 🔑 THE FIXTURES ARE REGISTRY DESCRIPTORS, NOT A SHAPE INVENTED HERE. `id`,
 * `label` and `available` are the fields `createIntegrationRegistry().list()`
 * returns; a fixture with a shape of its own would pass while the real snapshot
 * failed.
 *
 * @spec openspec/changes/document-detail-leaf-widgets/specs/document-register/spec.md
 */

import { describe, expect, it } from 'vitest'
import {
	DOCUMENT_LEAF_IDS,
	documentRecordIdFor,
	hasLeafTabs,
	visibleLeafTabs,
} from '../../src/services/documentLeafTabs.js'

/**
 * One registry descriptor, in the shape the registry hands back.
 *
 * @param {string}   id        The integration id.
 * @param {?boolean} available Availability hint; null means "not known yet".
 *
 * @return {object} The descriptor.
 */
function leaf(id, available = null) {
	return { id, label: id, icon: 'Link', available }
}

const RECORD = { objectId: 'doc-1' }

describe('visibleLeafTabs', () => {
	it('renders contacts, activity and shares when all three are enabled', () => {
		const tabs = visibleLeafTabs(
			[leaf('contacts'), leaf('activity'), leaf('shares')],
			RECORD,
		)

		expect(tabs.map((tab) => tab.id)).toEqual(['contacts', 'activity', 'shares'])
	})

	it('hides a leaf whose app is absent and keeps the rest', () => {
		const tabs = visibleLeafTabs(
			[leaf('contacts', false), leaf('activity', true), leaf('shares', true)],
			RECORD,
		)

		expect(tabs.map((tab) => tab.id)).toEqual(['activity', 'shares'])
	})

	it('hides a leaf that is not registered at all', () => {
		const tabs = visibleLeafTabs([leaf('shares', true)], RECORD)

		expect(tabs.map((tab) => tab.id)).toEqual(['shares'])
	})

	it('keeps a leaf whose availability is not known yet', () => {
		// null is "the capability read has not answered", not "absent". Folding
		// it into absent hides an installed app on every first render.
		const tabs = visibleLeafTabs([leaf('contacts', null)], RECORD)

		expect(tabs.map((tab) => tab.id)).toEqual(['contacts'])
	})

	it('renders nothing for a document with no record', () => {
		const tabs = visibleLeafTabs([leaf('contacts'), leaf('shares')], {
			objectId: '',
		})

		expect(tabs).toEqual([])
		expect(hasLeafTabs([leaf('contacts')], { objectId: '' })).toBe(false)
	})

	it('renders none of the twenty-odd integrations it was not asked for', () => {
		// The registry carries every integration OpenRegister installed. This
		// surface is three of them, and the allowlist is the contract.
		const tabs = visibleLeafTabs(
			[leaf('deck'), leaf('talk'), leaf('calendar'), leaf('contacts')],
			RECORD,
		)

		expect(tabs.map((tab) => tab.id)).toEqual(['contacts'])
		expect(DOCUMENT_LEAF_IDS).toEqual(['contacts', 'activity', 'shares'])
	})

	it('survives a registry that is empty or malformed', () => {
		expect(visibleLeafTabs([], RECORD)).toEqual([])
		expect(visibleLeafTabs(null, RECORD)).toEqual([])
		expect(visibleLeafTabs([null, {}, leaf('shares')], RECORD)).toHaveLength(1)
	})
})

describe('documentRecordIdFor', () => {
	const links = [
		{ id: 'link-7', sourceFileId: 7, anonymizedFileId: 70 },
		{ id: 'link-9', sourceFileId: '9', anonymizedFileId: 90 },
	]

	it('binds the tabs to the record for THIS source file', () => {
		expect(documentRecordIdFor(links, 7)).toBe('link-7')
		expect(documentRecordIdFor(links, '9')).toBe('link-9')
	})

	it('answers empty for a document that has no record yet', () => {
		expect(documentRecordIdFor(links, 11)).toBe('')
		expect(documentRecordIdFor([], 7)).toBe('')
		expect(documentRecordIdFor(links, undefined)).toBe('')
	})

	it('never binds one document to another document record', () => {
		// The failure this refuses: a match on anything but sourceFileId would
		// hang one document's contacts on another document's record, and every
		// layer downstream would report it as fact.
		expect(documentRecordIdFor(links, 70)).toBe('')
	})
})
