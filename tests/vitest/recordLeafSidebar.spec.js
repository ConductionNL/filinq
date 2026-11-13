/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Hosting the registry's leaves on Filinq's hand-built record pages
 * (leaf-integrations 2.1): a page publishes its record into CnAppRoot's shared
 * object-sidebar state when the record loads, re-publishes when it changes,
 * withdraws it when the page leaves (but never another page's record), and
 * toggles the sidebar only for a published record.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

import { describe, expect, it } from 'vitest'
import {
	publishRecord,
	recordLeafSidebarMixin,
	toggleRecordSidebar,
	withdrawRecord,
} from '../../src/sidebars/recordSidebar.js'

/**
 * The shared state as CnAppRoot provides it.
 *
 * @return {object} The state.
 */
function sidebarState() {
	return { active: false, open: false, objectType: '', objectId: '', title: '', subtitle: '', register: '', schema: '', object: null, requestedTab: 'notes' }
}

/**
 * A bare component instance running the mixin, with a settable record.
 *
 * @param {object|null} state The injected state.
 * @return {object} The instance, with `load(record)` to change the record.
 */
function page(state) {
	const mixin = recordLeafSidebarMixin({ schema: 'dossier', record: (vm) => vm.record, title: (dossier) => dossier.name })
	const watchers = []
	const vm = {
		objectSidebarState: state,
		record: null,
		...mixin.data(),
		$watch(read, handler, options) {
			watchers.push([read, handler])
			if (options?.immediate) {
				handler(read(vm))
			}
		},
		load(record) {
			vm.record = record
			for (const [read, handler] of watchers) {
				handler(read(vm))
			}
		},
	}
	for (const [name, fn] of Object.entries(mixin.methods)) {
		vm[name] = fn.bind(vm)
	}
	Object.defineProperty(vm, 'leafSidebarAvailable', { get: () => mixin.computed.leafSidebarAvailable.call(vm) })
	vm.unmount = () => mixin.beforeUnmount.call(vm)
	mixin.created.call(vm)
	return vm
}

describe('publishing a record', () => {
	it('fills the shared state with the record as a filinq object, clearing a requested tab', () => {
		const state = sidebarState()
		const object = { id: 'd-1', name: 'Woo 2025-017' }

		expect(publishRecord(state, { schema: 'dossier', id: 'd-1', title: 'Woo 2025-017', object })).toBe(true)
		expect(state).toMatchObject({ active: true, objectType: 'filinq-dossier', objectId: 'd-1', register: 'filinq', schema: 'dossier', title: 'Woo 2025-017', object, requestedTab: null, open: false })
	})

	it('publishes nothing outside CnAppRoot or without an id', () => {
		const state = sidebarState()
		expect(publishRecord(null, { schema: 'dossier', id: 'd-1' })).toBe(false)
		expect(publishRecord(state, { schema: 'dossier', id: '' })).toBe(false)
		expect(state.active).toBe(false)
	})

	it('withdraws only the record it published, and closes the sidebar', () => {
		const state = sidebarState()
		publishRecord(state, { schema: 'dossier', id: 'd-2' })
		state.open = true
		withdrawRecord(state, 'd-1')
		expect([state.active, state.objectId]).toEqual([true, 'd-2'])

		withdrawRecord(state, 'd-2')
		expect(state).toMatchObject({ active: false, open: false, objectType: '', objectId: '', object: null })
		expect(() => withdrawRecord(null, 'd-2')).not.toThrow()
	})

	it('toggles the sidebar only for a published record', () => {
		const state = sidebarState()
		toggleRecordSidebar(state)
		expect(state.open).toBe(false)
		publishRecord(state, { schema: 'dossier', id: 'd-1' })
		toggleRecordSidebar(state)
		expect(state.open).toBe(true)
		toggleRecordSidebar(state)
		expect(state.open).toBe(false)
		expect(() => toggleRecordSidebar(null)).not.toThrow()
	})
})

describe('the record page mixin', () => {
	it('publishes when the record loads and again when it changes, then withdraws on leaving', () => {
		const state = sidebarState()
		const vm = page(state)
		expect([vm.leafSidebarAvailable, state.active]).toEqual([false, false])

		vm.load({ id: 'd-1', name: 'Eerste' })
		expect([vm.leafSidebarAvailable, state.objectId, state.title]).toEqual([true, 'd-1', 'Eerste'])

		vm.toggleLeafSidebar()
		expect(state.open).toBe(true)

		vm.load({ uuid: 'd-2', name: 'Tweede' })
		expect([state.objectId, state.title, vm.publishedRecordId]).toEqual(['d-2', 'Tweede', 'd-2'])

		vm.unmount()
		expect([state.active, state.open, state.objectId]).toEqual([false, false, ''])
	})

	it('leaves the state alone when another page published after it', () => {
		const state = sidebarState()
		const vm = page(state)
		vm.load({ id: 'd-1', name: 'Eerste' })
		publishRecord(state, { schema: 'signingRequest', id: 's-9' })

		vm.unmount()
		expect([state.active, state.objectId]).toEqual([true, 's-9'])
	})

	it('offers no toggle outside CnAppRoot', () => {
		const vm = page(null)
		vm.load({ id: 'd-1', name: 'Eerste' })
		expect(vm.leafSidebarAvailable).toBe(false)
		expect(() => vm.toggleLeafSidebar()).not.toThrow()
	})
})
