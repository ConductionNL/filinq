/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * RecordLeafSidebar hosts the other apps' leaves on Filinq's record pages
 * through the library's registry-driven CnObjectSidebar (leaf-integrations
 * task 2.1 / 3.4): it renders only for a record a page published into the
 * shared sidebar state, hands the sidebar that record's identity with the
 * registry switched on, and writes the sidebar's open/close back into the
 * state. CnObjectSidebar is replaced by a marker that echoes its props;
 * which leaves it lists is the library's registry, not Filinq's.
 *
 * @spec openspec/changes/leaf-integrations/specs/document-register/spec.md
 */

import { createApp, h, nextTick, reactive } from 'vue'
import RecordLeafSidebar from './RecordLeafSidebar.vue'

jest.mock('@conduction/nextcloud-vue', () => {
	const { h: render } = require('vue')
	return {
		CnObjectSidebar: {
			name: 'CnObjectSidebar',
			props: ['open', 'useRegistry', 'objectType', 'objectId', 'objectData', 'register', 'schema', 'title', 'subtitle', 'hiddenTabs', 'requestedTab'],
			emits: ['update:open'],
			render() {
				return render('aside', {
					class: 'sidebar',
					'data-open': String(this.open),
					'data-registry': String(this.useRegistry),
					'data-type': this.objectType,
					'data-id': this.objectId,
					'data-register': this.register,
					'data-schema': this.schema,
					'data-title': this.title,
					'data-hidden': (this.hiddenTabs || []).join(','),
					'data-requested': String(this.requestedTab),
					onClick: () => this.$emit('update:open', !this.open),
				})
			},
		},
	}
})

/**
 * Mount the sidebar under a parent that provides `state` (or nothing).
 *
 * @param {object|null} state The shared sidebar state.
 * @return {HTMLElement} The mount element.
 */
function mount(state) {
	const el = document.createElement('div')
	document.body.appendChild(el)
	const provide = state === null ? {} : { objectSidebarState: state }
	createApp({ provide, render: () => h(RecordLeafSidebar) }).mount(el)
	return el
}

/**
 * A dossier a page published, as publishRecord() writes it.
 *
 * @param {object} overrides Fields to change.
 * @return {object} The reactive state.
 */
function published(overrides = {}) {
	return reactive({
		active: true,
		open: false,
		objectType: 'filinq-dossier',
		objectId: '42',
		register: 'filinq',
		schema: 'dossier',
		title: 'Dossier Lindelaan',
		subtitle: '',
		object: { id: '42' },
		requestedTab: null,
		...overrides,
	})
}

afterEach(() => {
	document.body.innerHTML = ''
})

describe('RecordLeafSidebar', () => {
	it('hands the registry sidebar the published record', () => {
		const sidebar = mount(published()).querySelector('.sidebar')
		expect(sidebar).not.toBeNull()
		expect(sidebar.dataset).toMatchObject({
			registry: 'true',
			type: 'filinq-dossier',
			id: '42',
			register: 'filinq',
			schema: 'dossier',
			title: 'Dossier Lindelaan',
			open: 'false',
			hidden: '',
			requested: 'null',
		})
	})

	it('passes the tabs a page hides on to the sidebar', () => {
		const sidebar = mount(published({ hiddenTabs: ['deck'] })).querySelector('.sidebar')
		expect(sidebar.dataset.hidden).toBe('deck')
	})

	it('renders nothing without a provided state', () => {
		expect(mount(null).querySelector('.sidebar')).toBeNull()
	})

	it('renders nothing for a record that is not published, or published without an id', () => {
		expect(mount(published({ active: false })).querySelector('.sidebar')).toBeNull()
		expect(mount(published({ objectId: '' })).querySelector('.sidebar')).toBeNull()
		expect(mount(published({ objectType: '' })).querySelector('.sidebar')).toBeNull()
	})

	it('writes the sidebar opening and closing back into the shared state', async () => {
		const state = published()
		const el = mount(state)
		el.querySelector('.sidebar').click()
		expect(state.open).toBe(true)
		await nextTick()
		expect(el.querySelector('.sidebar').dataset.open).toBe('true')
		el.querySelector('.sidebar').click()
		expect(state.open).toBe(false)
	})

	it('goes away when the page withdraws its record', async () => {
		const state = published()
		const el = mount(state)
		state.active = false
		await nextTick()
		expect(el.querySelector('.sidebar')).toBeNull()
	})
})
