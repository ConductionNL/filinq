/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The entity type selector: one switch per selectable type, a hint that says
 * whether every type is detected, and every toggle emitting a fresh list in
 * option order without touching the prop.
 */

import EntityTypeSelector from './EntityTypeSelector.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'
import { entityTypeLabel } from '../../services/entityTypes.js'

jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/vue', () => ({ NcCheckboxRadioSwitch: {} }))

const OPTIONS = ['PERSON', 'EMAIL', 'IBAN_CODE']

/**
 * Render the selector with the given props.
 *
 * @param {object} props The props.
 * @return {Promise<object>} The render.
 */
function render(props) {
	return renderStubbed(EntityTypeSelector, { props })
}

test('without options it says none are available and renders no switches', async () => {
	const page = await render({})

	expect(page.vm.hint).toBe('No entity types are available.')
	expect(page.stubs).toHaveLength(0)
	expect(page.html()).not.toContain('entity-type-selector__grid')
	page.unmount()
})

test('with every type enabled it says all are detected and every switch is on', async () => {
	const page = await render({ options: OPTIONS, value: [...OPTIONS] })

	expect(page.vm.allEnabled).toBe(true)
	expect(page.vm.hint).toContain('All entity types are detected.')
	expect(page.stubs.map((stub) => stub.attrs.modelValue)).toEqual([true, true, true])
	expect(page.stubs.map((stub) => stub.attrs.type)).toEqual(['switch', 'switch', 'switch'])
	expect(page.html()).toContain(entityTypeLabel('EMAIL'))
	page.unmount()
})

test('with a subset enabled it says only the enabled types are detected', async () => {
	const page = await render({ options: OPTIONS, value: ['EMAIL'] })

	expect(page.vm.allEnabled).toBe(false)
	expect(page.vm.hint).toContain('Only the enabled types are detected automatically.')
	expect(page.stubs.map((stub) => stub.attrs.modelValue)).toEqual([false, true, false])
	page.unmount()
})

test('switching a type on emits the new list in option order', async () => {
	const onInput = jest.fn()
	const value = ['IBAN_CODE']
	const page = await render({ options: OPTIONS, value, onInput })

	page.stubs[0].attrs['onUpdate:modelValue'](true)

	expect(onInput).toHaveBeenCalledWith(['PERSON', 'IBAN_CODE'])
	expect(value).toEqual(['IBAN_CODE'])
	page.unmount()
})

test('switching a type off drops it and keeps the others', async () => {
	const onInput = jest.fn()
	const page = await render({ options: OPTIONS, value: [...OPTIONS], onInput })

	page.stubs[1].attrs['onUpdate:modelValue'](false)

	expect(onInput).toHaveBeenCalledWith(['PERSON', 'IBAN_CODE'])
	page.unmount()
})

test('an enabled value outside the options is not carried into the emitted list', async () => {
	const onInput = jest.fn()
	const page = await render({ options: OPTIONS, value: ['LEGACY', 'EMAIL'], onInput })

	page.stubs[2].attrs['onUpdate:modelValue'](true)

	expect(onInput).toHaveBeenCalledWith(['EMAIL', 'IBAN_CODE'])
	page.unmount()
})
