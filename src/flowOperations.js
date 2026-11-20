/**
 * Filinq's operations in the Nextcloud Flow rule builder (flow-operations
 * REQ-DDFLO-001, task 3.1).
 *
 * Loaded on the Flow settings page through LoadSettingsScriptsEvent. The four
 * operations are registered by their PHP class name; the two with options get
 * a small custom element the rule builder renders under the operation. The
 * element holds the rule's options as the JSON string the server validates
 * (validateOperation()) and reports every change as `update:model-value`.
 *
 * No Vue and no shared chunks: this script lands on a page Filinq does not own.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-3.1
 */
import { translate as t } from '@nextcloud/l10n'

const NAMESPACE = 'OCA\\Filinq\\Flow\\Operation\\'

/**
 * Read a rule's stored options; anything but a JSON object reads as none.
 *
 * @param {string} value The stored options.
 * @return {object} The options.
 */
export function decodeOptions(value) {
	try {
		const decoded = JSON.parse(value || '{}')
		if (decoded !== null && typeof decoded === 'object' && !Array.isArray(decoded)) {
			return decoded
		}
	} catch {
		// A malformed value reads as no options; the server refuses to save one.
	}
	return {}
}

/**
 * Write one option back: an empty value removes it, no options at all is ''.
 *
 * @param {string} value The current stored options.
 * @param {string} key The option.
 * @param {string} input What was typed.
 * @return {string} The stored options.
 */
export function encodeOption(value, key, input) {
	const options = decodeOptions(value)
	const trimmed = String(input ?? '').trim()
	if (trimmed === '') {
		delete options[key]
	} else {
		options[key] = trimmed
	}
	return Object.keys(options).length === 0 ? '' : JSON.stringify(options)
}

/**
 * The single-field option editors: tag name, option key and texts.
 *
 * @return {Array<object>} The editors.
 */
export function optionEditors() {
	return [
		{
			operation: 'ConvertToPdfaOperation',
			tag: 'oca-filinq-flow-pdfa-options',
			key: 'targetFolder',
			label: t('filinq', 'Target folder'),
			hint: t('filinq', 'A folder in your own files for the PDF/A copy, created when missing. Leave empty to save the copy next to the original.'),
		},
		{
			operation: 'RunValidationOperation',
			tag: 'oca-filinq-flow-validate-options',
			key: 'documentType',
			label: t('filinq', 'Document type'),
			hint: t('filinq', 'The document type whose validation profile is checked. Leave empty for the default profile.'),
		},
	]
}

/**
 * The operators to register: all four, the two with options pointing at their element.
 *
 * @return {Array<object>} The operator descriptors.
 */
export function filinqOperators() {
	const elements = Object.fromEntries(optionEditors().map((editor) => [editor.operation, editor.tag]))
	return ['AnonymizeDocumentOperation', 'RunOcrOperation', 'ConvertToPdfaOperation', 'RunValidationOperation'].map((name) => {
		const operator = { id: NAMESPACE + name, operation: '', color: 'var(--color-primary-element)' }
		if (elements[name] !== undefined) {
			operator.element = elements[name]
		}
		return operator
	})
}

/**
 * Build the custom element class for one option editor.
 *
 * @param {object} editor The editor descriptor.
 * @param {typeof HTMLElement} Base The HTMLElement constructor.
 * @return {typeof HTMLElement} The element class.
 */
export function optionElement(editor, Base) {
	return class FilinqFlowOption extends Base {

		constructor() {
			super()
			this.stored = ''
			this.input = null
		}

		get modelValue() {
			return this.stored
		}

		set modelValue(value) {
			this.stored = typeof value === 'string' ? value : ''
			if (this.input !== null) {
				this.input.value = String(decodeOptions(this.stored)[editor.key] ?? '')
			}
		}

		connectedCallback() {
			if (this.input !== null) {
				return
			}
			const id = editor.tag + '-' + Math.random().toString(36).slice(2, 10)
			const label = this.ownerDocument.createElement('label')
			label.htmlFor = id
			label.textContent = editor.label
			label.style.cssText = 'display:block;font-weight:bold;color:var(--color-main-text);'
			const hint = this.ownerDocument.createElement('p')
			hint.id = id + '-hint'
			hint.textContent = editor.hint
			hint.style.cssText = 'margin:2px 0 6px;color:var(--color-text-maxcontrast);'
			this.input = this.ownerDocument.createElement('input')
			this.input.type = 'text'
			this.input.id = id
			this.input.setAttribute('aria-describedby', hint.id)
			this.input.style.cssText = 'width:100%;max-width:320px;'
			this.input.value = String(decodeOptions(this.stored)[editor.key] ?? '')
			this.input.addEventListener('input', () => {
				this.stored = encodeOption(this.stored, editor.key, this.input.value)
				this.dispatchEvent(new CustomEvent('update:model-value', { detail: [this.stored] }))
			})
			this.append(label, hint, this.input)
		}

	}
}

/**
 * Register Filinq's operators with the Flow engine, defining the option elements first.
 *
 * @param {object|undefined} engine window.OCA.WorkflowEngine.
 * @param {CustomElementRegistry|undefined} registry window.customElements.
 * @param {typeof HTMLElement|undefined} Base window.HTMLElement.
 * @return {boolean} Whether the engine was there to register with.
 */
export function registerFilinqOperators(engine, registry, Base) {
	if (engine === undefined || typeof engine.registerOperator !== 'function') {
		return false
	}
	if (registry !== undefined && Base !== undefined) {
		for (const editor of optionEditors()) {
			if (registry.get(editor.tag) === undefined) {
				registry.define(editor.tag, optionElement(editor, Base))
			}
		}
	}
	for (const operator of filinqOperators()) {
		engine.registerOperator(operator)
	}
	return true
}

if (typeof window !== 'undefined') {
	registerFilinqOperators(window.OCA?.WorkflowEngine, window.customElements, window.HTMLElement)
}
