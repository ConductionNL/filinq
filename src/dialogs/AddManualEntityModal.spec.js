/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * AddManualEntityModal: pre-filled from the selection, grondslag proposed per
 * type until the reviewer picks one, zero matches answered with a notice.
 *
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-4.3
 */

import AddManualEntityModal from './AddManualEntityModal.vue'

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => text,
}))
jest.mock('@nextcloud/vue', () => ({
	NcButton: { name: 'NcButton', render: () => null },
	NcDialog: { name: 'NcDialog', render: () => null },
	NcSelect: { name: 'NcSelect', render: () => null },
	NcTextField: { name: 'NcTextField', render: () => null },
}))

/**
 * The component's options bound to a plain state object, as Vue would.
 *
 * @param {object} props The props.
 * @return {object} The instance stand-in.
 */
function instance(props) {
	const vm = { ...props, emitted: [] }
	vm.$emit = (...args) => vm.emitted.push(args)
	Object.assign(vm, AddManualEntityModal.data.call(vm))
	for (const [name, fn] of Object.entries(AddManualEntityModal.methods)) {
		vm[name] = fn.bind(vm)
	}
	for (const [name, fn] of Object.entries(AddManualEntityModal.computed)) {
		Object.defineProperty(vm, name, { get: fn.bind(vm) })
	}
	return vm
}

const mapping = { PERSON: ['woo-art-5-1-2-e'], BSN: 'woo-art-5-1-2-e' }

describe('AddManualEntityModal', () => {
	it('starts from the selection and the proposal for PERSON', () => {
		const vm = instance({ selectedText: 'Pieter de Vries', proposalMapping: mapping, save: jest.fn() })
		expect(vm.value).toBe('Pieter de Vries')
		expect(vm.type).toBe('PERSON')
		expect(vm.bases).toEqual(['woo-art-5-1-2-e'])
		expect(vm.basesProposed).toBe(true)
		expect(vm.canSubmit).toBe(true)
	})

	it('re-proposes on a type change, until the reviewer picks grondslagen', () => {
		const vm = instance({ selectedText: 'x', proposalMapping: mapping, save: jest.fn() })
		vm.onTypeChange('EMAIL')
		expect(vm.bases).toEqual([])
		vm.onBasesChange(['own-pick'])
		vm.onTypeChange('BSN')
		expect(vm.bases).toEqual(['own-pick'])
		expect(vm.basesProposed).toBe(false)
	})

	it('saves value, type and grondslagen and closes', async () => {
		const save = jest.fn().mockResolvedValue(2)
		const vm = instance({ selectedText: ' Pieter de Vries ', proposalMapping: mapping, save })
		await vm.submit()
		expect(save).toHaveBeenCalledWith({ value: 'Pieter de Vries', type: 'PERSON', bases: ['woo-art-5-1-2-e'] })
		expect(vm.emitted).toEqual([['added', 2], ['update:open', false]])
	})

	it('stays open with a notice when the text is found nowhere', async () => {
		const vm = instance({ selectedText: 'nergens', proposalMapping: {}, save: jest.fn().mockResolvedValue(0) })
		await vm.submit()
		expect(vm.notice).toContain('not found')
		expect(vm.emitted).toEqual([])
	})

	it('shows the failure and does not close', async () => {
		const vm = instance({ selectedText: 'x', proposalMapping: {}, save: jest.fn().mockRejectedValue(new Error('boom')) })
		await vm.submit()
		expect(vm.error).toBe('boom')
		expect(vm.emitted).toEqual([])
		expect(vm.saving).toBe(false)
	})

	it('does not save an empty value', async () => {
		const save = jest.fn()
		const vm = instance({ selectedText: '   ', proposalMapping: {}, save })
		await vm.submit()
		expect(save).not.toHaveBeenCalled()
	})
})
