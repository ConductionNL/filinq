/**
 * DocumentLeafTabs mounted: the leaves a record surface renders, and the ones it hides.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * 🔑 THE REGISTRY IS THE INPUT, SO IT IS THE ONLY THING REPLACED. The
 * component, its selection rules and the schema's linked types are the real
 * ones; the registry snapshot stands in for what OpenRegister's bootstrap
 * installs, and the two library hosts are replaced by markers so the test can
 * see which render path each leaf took.
 *
 * @spec openspec/changes/leaf-integrations/tasks.md#3-4
 */

import { createApp, h, nextTick, ref } from 'vue'
import DocumentLeafTabs from './DocumentLeafTabs.vue'
import { leafIdsForSchema } from '../services/documentLeafTabs.js'

const mockRegistry = ref([])

jest.mock('@conduction/nextcloud-vue', () => {
	const { h: render } = jest.requireActual('vue')
	return {
		useIntegrationRegistry: () => ({
			integrations: {
				get value() {
					return mockRegistry.value
				},
			},
			resolveTab: () => null,
		}),
		CnIntegrationTab: {
			name: 'CnIntegrationTab',
			props: [
				'integrationId',
				'register',
				'schema',
				'objectId',
				'emptyLabel',
				'unavailableLabel',
			],
			render() {
				return render(
					'div',
					{ 'data-generic': this.integrationId },
					this.emptyLabel,
				)
			},
		},
		CnLeafMountHost: {
			name: 'CnLeafMountHost',
			props: ['provider', 'mountProps'],
			render() {
				return render('div', { 'data-mounted': this.provider.id })
			},
		},
	}
})

/**
 * Mount the component with the given props into a fresh element.
 *
 * @param {object} props The component props.
 *
 * @return {Promise<HTMLElement>} The element it rendered into.
 */
async function mount(props) {
	const el = document.createElement('div')
	createApp({ render: () => h(DocumentLeafTabs, props) }).mount(el)
	await nextTick()
	return el
}

/**
 * The leaf ids rendered, in order.
 *
 * @param {HTMLElement} el The mounted root.
 *
 * @return {string[]} The data-leaf values.
 */
function rendered(el) {
	return [...el.querySelectorAll('[data-leaf]')].map((node) =>
		node.getAttribute('data-leaf'),
	)
}

const RECORD = { register: 'filinq', schema: 'signingRequest', objectId: 'sr-1' }

describe('DocumentLeafTabs on a record surface', () => {
	it('renders the mail and calendar leaves of a signing request when both apps are on', async () => {
		mockRegistry.value = [
			{ id: 'email', label: 'Mail', available: true },
			{ id: 'calendar', label: 'Calendar', available: true },
			{ id: 'contacts', label: 'Contacts', available: true },
		]

		const el = await mount({
			...RECORD,
			leafIds: leafIdsForSchema('signingRequest'),
		})

		expect(rendered(el)).toEqual(['email', 'calendar'])
		expect(el.querySelector('[data-generic="email"]')).not.toBeNull()
	})

	it('hides Deck on a dossier when Deck is absent and still renders the files leaf', async () => {
		mockRegistry.value = [
			{ id: 'files', label: 'Files', available: true },
			{ id: 'deck', label: 'Deck', available: false },
		]

		const el = await mount({
			register: 'filinq',
			schema: 'dossier',
			objectId: 'd-1',
			leafIds: leafIdsForSchema('dossier'),
		})

		expect(rendered(el)).toEqual(['files'])
	})

	it('renders no section at all when none of the linked apps is installed', async () => {
		mockRegistry.value = [
			{ id: 'email', label: 'Mail', available: false },
			{ id: 'calendar', label: 'Calendar', available: false },
		]

		const el = await mount({
			...RECORD,
			leafIds: leafIdsForSchema('signingRequest'),
		})

		expect(el.querySelector('.document-leaf-tabs')).toBeNull()
	})

	it('hands a mount-mode leaf to the mount host', async () => {
		const noop = () => {}
		mockRegistry.value = [
			{
				id: 'calendar',
				label: 'Calendar',
				available: true,
				renderMode: 'mount',
				mount: noop,
				unmount: noop,
			},
		]

		const el = await mount({
			...RECORD,
			leafIds: leafIdsForSchema('signingRequest'),
		})

		expect(el.querySelector('[data-mounted="calendar"]')).not.toBeNull()
		expect(el.querySelector('[data-generic="calendar"]')).toBeNull()
	})

	it("shows the surface's own empty text in the generic host", async () => {
		mockRegistry.value = [{ id: 'files', label: 'Files', available: true }]

		const el = await mount({
			register: 'filinq',
			schema: 'dossier',
			objectId: 'd-1',
			leafIds: leafIdsForSchema('dossier'),
			emptyLabel: 'Nothing linked here yet',
		})

		expect(el.querySelector('[data-generic="files"]').textContent).toBe(
			'Nothing linked here yet',
		)
	})
})
