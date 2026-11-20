/**
 * Signer-filled text fields: the settings the placement editor sets and
 * sends, and what the "Fill in and sign" dialog sends and reads back.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-3.1
 * @spec openspec/changes/signing-field-validation/tasks.md#task-3.2
 */

import { describe, expect, it } from 'vitest'
import {
	FIELD_CHECKS,
	MAX_LABEL,
	toRequestPlacements,
	updateTextSettings,
} from '../../src/views/signing/fieldPlacement.js'
import {
	fieldErrorsOf,
	hasFieldsToFillIn,
	signBody,
	signErrorOf,
} from '../../src/views/signing/fillInAndSign.js'

function box (type) {
  return {
	signerIndex: 0,
	page: 1,
	type,
	x: 0.1,
	y: 0.2,
	width: 0.3,
	height: 0.04,
}
}

describe('text field settings', () => {
	it('offers the two IBAN checks the server runs', () => {
		expect(FIELD_CHECKS).toEqual(['iban-nl', 'iban'])
	})

	it('sets the label, required flag and check of a text field', () => {
		const [field] = updateTextSettings([box('text')], 0, {
			label: 'Account for the payment',
			required: true,
			validation: 'iban-nl',
		})
		expect(field).toMatchObject({
			label: 'Account for the payment',
			required: true,
			validation: 'iban-nl',
		})
	})

	it('removes the check when "no check" is chosen', () => {
		const list = updateTextSettings([box('text')], 0, { validation: 'iban' })
		const [field] = updateTextSettings(list, 0, { validation: 'none' })
		expect('validation' in field).toBe(false)
	})

	it('leaves other field types and other boxes as they are', () => {
		const list = [box('signature'), box('text')]
		const next = updateTextSettings(list, 0, { required: true })
		expect(next[0]).toBe(list[0])
		expect(next[1]).toBe(list[1])
	})

	it('cuts a label to the longest the server accepts', () => {
		const [field] = updateTextSettings([box('text')], 0, {
			label: 'x'.repeat(MAX_LABEL + 5),
		})
		expect(field.label).toHaveLength(MAX_LABEL)
	})

	it('sends the settings of a text field and nothing extra for others', () => {
		const list = updateTextSettings([box('text'), box('date')], 0, {
			label: '  Account for the payment  ',
			required: true,
			validation: 'iban-nl',
		})
		list[1] = { ...list[1], label: 'ignored', required: true }
		const [text, date] = toRequestPlacements(list)
		expect(text.label).toBe('Account for the payment')
		expect(text.required).toBe(true)
		expect(text.validation).toBe('iban-nl')
		expect(Object.keys(date).sort()).toEqual([
			'height',
			'page',
			'signerIndex',
			'type',
			'width',
			'x',
			'y',
		])
	})

	it('sends no empty label, no false required and no unknown check', () => {
		const [sent] = toRequestPlacements([
			{ ...box('text'), label: '   ', required: false, validation: 'bsn' },
		])
		expect('label' in sent).toBe(false)
		expect('required' in sent).toBe(false)
		expect('validation' in sent).toBe(false)
	})
})

describe('fill in and sign', () => {
	const fields = [
		{ placement: 2, label: 'Account', required: true, validation: 'iban-nl' },
		{ placement: 5, label: '', required: false, validation: null },
	]

	it('knows which folder entries have fields to fill in', () => {
		expect(hasFieldsToFillIn({ fields })).toBe(true)
		expect(hasFieldsToFillIn({ fields: [] })).toBe(false)
		expect(hasFieldsToFillIn({})).toBe(false)
		expect(hasFieldsToFillIn(null)).toBe(false)
	})

	it('sends every field by placement position, empty ones as empty text', () => {
		expect(signBody('signer-1', fields, { 2: 'NL91 ABNA 0417 1643 00', 9: 'x' })).toEqual({
			signerId: 'signer-1',
			fields: { 2: 'NL91 ABNA 0417 1643 00', 5: '' },
		})
	})

	it('reads per-field messages from a 422, as an object or a list', () => {
		const asObject = { response: { status: 422, data: { fieldErrors: { 2: 'Type a valid Dutch IBAN' } } } }
		const asList = { response: { status: 422, data: { fieldErrors: ['Fill this in'] } } }
		expect(fieldErrorsOf(asObject)).toEqual({ 2: 'Type a valid Dutch IBAN' })
		expect(fieldErrorsOf(asList)).toEqual({ 0: 'Fill this in' })
	})

	it('has no field messages for any other failure', () => {
		expect(fieldErrorsOf({ response: { status: 400, data: { fieldErrors: { 2: 'x' } } } })).toEqual({})
		expect(fieldErrorsOf({ response: { status: 422, data: { error: 'x' } } })).toEqual({})
		expect(fieldErrorsOf(new Error('network'))).toEqual({})
	})

	it("shows the server's message, or the fallback when it gave none", () => {
		expect(signErrorOf({ response: { data: { error: 'Failed to sign document' } } }, 'fallback')).toBe('Failed to sign document')
		expect(signErrorOf(new Error('network'), 'fallback')).toBe('fallback')
	})
})
