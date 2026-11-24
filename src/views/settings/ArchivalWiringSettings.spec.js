/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The Archiefbeheer section: it shows where OpenRegister's archival settings
 * point, writes nothing until the admin clicks and confirms, and then points
 * them at filinq's register and schemas.
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-or-archival-settings-are-wired-through-an-explicit-admin-action-req-ddare-002
 */

import axios from '@nextcloud/axios'
import ArchivalWiringSettings from './ArchivalWiringSettings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'

jest.mock('@nextcloud/axios', () => ({ __esModule: true, default: { get: jest.fn(), put: jest.fn() } }))
jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/{(\w+)}/g, (match, key) => (vars && key in vars ? vars[key] : match)),
}))
jest.mock('@nextcloud/router', () => ({ generateUrl: (url) => '/index.php' + url }))

const flush = () => new Promise((resolve) => setTimeout(resolve, 0))
const REGISTERS = [
	{ id: 3, slug: 'opencatalogi', title: 'OpenCatalogi', schemas: [] },
	{ id: 7, slug: 'filinq', title: 'Filinq', schemas: [{ id: 41, slug: 'selectielijstEntry' }, { id: 42, slug: 'destructionList' }] },
]
const WIRED = { selectielijstRegister: 7, selectielijstSchema: 41, destructionListRegister: 7, destructionListSchema: 42, archivalRegister: 7 }
let rendered

afterEach(() => {
	rendered?.unmount()
	rendered = null
	axios.get.mockReset()
	axios.put.mockReset()
})

/**
 * Mount the section over the given archival settings.
 *
 * @param {object|Error} settings What OpenRegister answers, or the failure.
 * @param {Array<object>} registers The registers the page lists.
 * @return {Promise<object>} The rendered section.
 */
async function mountSection(settings, registers = REGISTERS) {
	if (settings instanceof Error) {
		axios.get.mockRejectedValue(settings)
	} else {
		axios.get.mockResolvedValue({ data: settings })
	}
	rendered = await renderStubbed(ArchivalWiringSettings, { props: { registers } })
	await flush()
	await flush()
	return rendered
}

const text = (wrapper, id) => wrapper.vm.$el.querySelector(`[data-testid="${id}"]`)?.textContent.replace(/\s+/g, ' ').trim() ?? null

describe('ArchivalWiringSettings', () => {
	it('wires the unset settings only after the admin clicks and confirms', async () => {
		const wrapper = await mountSection({ destructionBatchSize: 50, selectielijstRegister: null })
		expect(text(wrapper, 'archival-wiring-state')).toContain('no archival register yet')
		expect(axios.get).toHaveBeenCalledWith('/index.php/apps/openregister/api/settings/archival')
		expect(axios.put).not.toHaveBeenCalled()

		wrapper.vm.confirming = true
		await flush()
		expect(text(wrapper, 'archival-wiring-confirm')).toContain('filinq\'s register')
		expect(axios.put).not.toHaveBeenCalled()

		axios.put.mockResolvedValue({ data: { destructionBatchSize: 50, ...WIRED } })
		await wrapper.vm.wire()
		await flush()

		expect(axios.put).toHaveBeenCalledWith('/index.php/apps/openregister/api/settings/archival', { destructionBatchSize: 50, ...WIRED })
		expect(text(wrapper, 'archival-wiring-state')).toContain('keeps its destruction lists in filinq')
		expect(text(wrapper, 'archival-wiring-link')).toBeNull()
	})

	it('names the register that owns the wiring and leaves it until the admin acts', async () => {
		const wrapper = await mountSection({ ...WIRED, selectielijstRegister: 3 })

		expect(text(wrapper, 'archival-wiring-state')).toContain('OpenCatalogi')
		expect(text(wrapper, 'archival-wiring-link')).toBe('Link the archive register')
		expect(axios.put).not.toHaveBeenCalled()
	})

	it('offers nothing to link when filinq\'s schemas are not imported', async () => {
		const wrapper = await mountSection({}, [{ id: 7, slug: 'filinq', schemas: [] }])

		expect(text(wrapper, 'archival-wiring-missing')).toContain('not imported')
		expect(text(wrapper, 'archival-wiring-link')).toBeNull()
		await wrapper.vm.wire()
		expect(axios.put).not.toHaveBeenCalled()
	})

	it('says so when the settings cannot be read, and keeps a refused write in view', async () => {
		const unread = await mountSection(new Error('503'))
		expect(text(unread, 'archival-wiring-unread')).toContain('cannot be read')
		unread.unmount()

		const wrapper = await mountSection({})
		axios.put.mockRejectedValue(Object.assign(new Error('403'), { response: { status: 403, data: { error: 'Admin only' } } }))
		wrapper.vm.confirming = true
		await wrapper.vm.wire()
		await flush()

		expect(text(wrapper, 'archival-wiring-error')).toBe('Admin only')
		expect(wrapper.vm.confirming).toBe(true)
	})
})
