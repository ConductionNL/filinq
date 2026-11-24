// @vitest-environment jsdom
/**
 * The Flow rule-builder script: all four operations are registered by their
 * PHP class, the two with options get an accessible editor that stores the
 * JSON the server validates (flow-operations REQ-DDFLO-001, task 3.1).
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-3.1
 */
import { describe, expect, it, vi } from 'vitest'
import { decodeOptions, encodeOption, filinqOperators, registerFilinqOperators } from '../../src/flowOperations.js'

describe('the Filinq Flow operators', () => {
	it('register all four operations by their PHP class, the optioned two with an element', () => {
		expect(filinqOperators().map((o) => [o.id, o.element])).toEqual([
			['OCA\\Filinq\\Flow\\Operation\\AnonymizeDocumentOperation', undefined],
			['OCA\\Filinq\\Flow\\Operation\\RunOcrOperation', undefined],
			['OCA\\Filinq\\Flow\\Operation\\ConvertToPdfaOperation', 'oca-filinq-flow-pdfa-options'],
			['OCA\\Filinq\\Flow\\Operation\\RunValidationOperation', 'oca-filinq-flow-validate-options'],
		])
		expect(filinqOperators().every((o) => o.operation === '')).toBe(true)
	})

	it('store options as a JSON object and no options as empty', () => {
		expect(decodeOptions('')).toEqual({})
		expect(decodeOptions('not json')).toEqual({})
		expect(decodeOptions('[1]')).toEqual({})
		expect(decodeOptions('{"documentType":"besluit"}')).toEqual({ documentType: 'besluit' })
		expect(encodeOption('', 'documentType', ' besluit ')).toBe('{"documentType":"besluit"}')
		expect(encodeOption('{"documentType":"besluit"}', 'documentType', '')).toBe('')
	})

	it('register nothing without the Flow engine', () => {
		expect(registerFilinqOperators(undefined, window.customElements, window.HTMLElement)).toBe(false)
		expect(registerFilinqOperators({}, window.customElements, window.HTMLElement)).toBe(false)
	})

	it('render a labelled editor that reports each change as update:model-value', () => {
		const engine = { registerOperator: vi.fn() }
		expect(registerFilinqOperators(engine, window.customElements, window.HTMLElement)).toBe(true)
		expect(engine.registerOperator).toHaveBeenCalledTimes(4)

		const element = document.createElement('oca-filinq-flow-pdfa-options')
		element.modelValue = '{"targetFolder":"Archief"}'
		document.body.append(element)

		const input = element.querySelector('input')
		const label = element.querySelector('label')
		expect(label.htmlFor).toBe(input.id)
		expect(input.getAttribute('aria-describedby')).toBe(element.querySelector('p').id)
		expect(input.value).toBe('Archief')

		const changes = []
		element.addEventListener('update:model-value', (event) => changes.push(event.detail[0]))
		input.value = 'Archief/2026'
		input.dispatchEvent(new Event('input'))
		input.value = ''
		input.dispatchEvent(new Event('input'))

		expect(changes).toEqual(['{"targetFolder":"Archief/2026"}', ''])
		expect(element.modelValue).toBe('')
	})

	it('define each element once when registered again', () => {
		const engine = { registerOperator: vi.fn() }
		expect(() => registerFilinqOperators(engine, window.customElements, window.HTMLElement)).not.toThrow()
		expect(window.customElements.get('oca-filinq-flow-validate-options')).toBeDefined()
	})
})
