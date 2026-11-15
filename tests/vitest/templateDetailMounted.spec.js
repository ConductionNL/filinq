/**
 * Golden: what the template editor does when it mounts — which template it
 * loads, how the form is filled, the edit lock it takes and whether it finds
 * a wizard — recorded on the implementation where mounted() did it inline.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the template editor's mount
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

const world = vi.hoisted(() => ({ calls: [], store: {}, wizard: null, lock: null }))

vi.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
vi.mock('../../src/utils/sanitizeHtml.js', () => ({ sanitizeHtml: (html) => html }))
vi.mock('../../src/store/modules/template.js', () => ({ useTemplateStore: () => world.store }))
vi.mock('../../src/services/wizard.js', () => ({
	loadTemplateWizard: async (id) => {
		world.calls.push(['loadTemplateWizard', id])
		return world.wizard
	},
}))

const FULL = {
	id: 't1',
	name: 'Brief',
	namespace: 'zaken',
	description: 'Een brief',
	content: '<p>Hallo</p>',
	category: 'brieven',
	tags: ['a', 'b'],
	format: 'A5',
	orientation: 'L',
	lockedBy: 'bob',
}

const CASES = {
	'a new template': { routeId: undefined, held: null },
	'the new route': { routeId: 'new', held: null },
	'the held template': { routeId: 't1', held: FULL, lock: { lockedBy: 'anna' }, wizard: { ok: true, data: { wizard: { id: 'w' } } } },
	'no route id, a held template': { routeId: undefined, held: FULL, lock: { lockedBy: 'anna' }, wizard: { ok: true, data: {} } },
	'another template by URL': { routeId: 't2', held: FULL, fetched: { id: 't2', name: 'Factuur', tags: [] }, lock: null, wizard: { ok: false } },
	'a bare template': { routeId: 't3', held: null, fetched: { id: 't3' }, lock: {}, wizard: { ok: true, data: { wizard: null } } },
	'a lock answered with an owner of null': { routeId: 't1', held: { ...FULL, lockedBy: undefined }, lock: { lockedBy: null }, wizard: { ok: true, data: { wizard: {} } } },
}

describe('template editor mount golden', () => {
	for (const [name, c] of Object.entries(CASES)) {
		it(name, async () => {
			world.calls = []
			world.wizard = c.wizard ?? { ok: false }
			world.store = {
				templateItem: c.held,
				async fetchTemplate(id) {
					world.calls.push(['fetchTemplate', id])
					this.templateItem = c.fetched
				},
				async acquireLock(id) {
					world.calls.push(['acquireLock', id])
					return c.lock
				},
			}
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src/views/templates/TemplateDetail.vue'),
			)
			const vm = createInstance(options, { route: { params: { id: c.routeId }, query: {} } })
			await options.mounted.call(vm)
			golden('templateDetailMounted-' + name.replace(/\W+/g, '-'), {
				calls: world.calls,
				form: vm.form,
				editorHtml: vm.editorHtml,
				lockOwner: vm.lockOwner,
				hasWizard: vm.hasWizard,
			})
		})
	}
})
