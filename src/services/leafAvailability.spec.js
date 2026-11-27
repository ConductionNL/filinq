/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * A record page hides the leaves whose app is absent (leaf-integrations task
 * 2.2): Mail, Calendar, Contacts and Deck register in the library whether or
 * not their app is enabled, so the host names the ones to hide.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

import { absentLeafIds, enabledApps, hiddenLeafTabs } from './leafAvailability.js'

const REGISTRY = [
	{ id: 'files', requiredApp: null, available: null },
	{ id: 'email', requiredApp: 'mail', available: null },
	{ id: 'calendar', requiredApp: 'calendar', available: null },
	{ id: 'contacts', requiredApp: 'contacts', available: null },
	{ id: 'deck', requiredApp: 'deck', available: null },
]

describe('enabledApps', () => {
	it('reads the apps the page lists as enabled for the user', () => {
		const apps = enabledApps({ OC: { appswebroots: { files: '/apps/files', mail: '/apps/mail' } } })
		expect([...apps].sort()).toEqual(['files', 'mail'])
	})

	it('says nothing when the page does not list them', () => {
		expect(enabledApps({})).toBeNull()
		expect(enabledApps({ OC: {} })).toBeNull()
		expect(enabledApps({ OC: { appswebroots: null } })).toBeNull()
		expect(enabledApps(undefined)).toBeNull()
	})
})

describe('absentLeafIds', () => {
	it('hides Mail, Calendar, Contacts and Deck when none of their apps is enabled', () => {
		expect(absentLeafIds(REGISTRY, new Set(['files']))).toEqual(['email', 'calendar', 'contacts', 'deck'])
	})

	it('keeps a leaf whose app is enabled and a leaf that needs no app', () => {
		expect(absentLeafIds(REGISTRY, new Set(['mail', 'calendar', 'contacts', 'deck']))).toEqual([])
	})

	it('hides nothing on an unknown app list, unless the leaf itself says it is unavailable', () => {
		expect(absentLeafIds(REGISTRY, null)).toEqual([])
		expect(absentLeafIds([...REGISTRY, { id: 'xwiki', available: false }], null)).toEqual(['xwiki'])
	})

	it('skips entries that are not leaves', () => {
		expect(absentLeafIds([null, 'mail', { requiredApp: 'mail' }], new Set())).toEqual([])
		expect(absentLeafIds(undefined, new Set())).toEqual([])
	})
})

describe('hiddenLeafTabs', () => {
	it('adds the absent leaves to what the page hides, each once', () => {
		expect(hiddenLeafTabs(['deck', 'notes'], REGISTRY, new Set(['files', 'mail']))).toEqual([
			'deck',
			'notes',
			'calendar',
			'contacts',
		])
	})

	it('reads a page without hidden tabs as hiding none', () => {
		expect(hiddenLeafTabs(undefined, REGISTRY, new Set(['mail', 'calendar', 'contacts']))).toEqual(['deck'])
		expect(hiddenLeafTabs([1, 'notes'], [], null)).toEqual(['notes'])
	})
})
