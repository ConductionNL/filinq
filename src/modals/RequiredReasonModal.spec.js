/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The required-reason dialog: an empty reason is refused before anything is
 * sent, a refused submit keeps the dialog open, and an accepted one closes it.
 *
 * @spec openspec/changes/archiefwet-retention-engine/specs/archiefwet-retention-engine/spec.md#requirement-vernietigingslijst-review-and-approval-surface-req-ddare-004
 */

jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
const mockSetResult = jest.fn()
jest.mock('@conduction/nextcloud-vue', () => ({
	CnFormDialog: {
		name: 'CnFormDialog',
		methods: { setResult: (...args) => mockSetResult(...args) },
		render: () => null,
	},
}))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const RequiredReasonModal = require('./RequiredReasonModal.vue').default
const { reasonFormSchema, reasonOf } = require('./RequiredReasonModal.vue')

let rendered

afterEach(() => {
	rendered?.unmount()
	jest.resetAllMocks()
})

/**
 * Mount the dialog with the given submit.
 *
 * @param {Function} submit The submit.
 * @param {Function} onDone The done listener.
 * @return {Promise<object>} The rendered dialog.
 */
async function mountModal(submit, onDone = jest.fn()) {
	rendered = await renderStubbed(RequiredReasonModal, {
		props: {
			dialogTitle: 'Reject destruction list',
			label: 'Why?',
			submit,
			onDone,
		},
		keep: ['CnFormDialog'],
	})
	return rendered
}

describe('RequiredReasonModal', () => {
	it('asks for one required reason', () => {
		const schema = reasonFormSchema('Why?', 'Recorded with the approval.')

		expect(schema.required).toEqual(['reason'])
		expect(Object.keys(schema.properties)).toEqual(['reason'])
		expect(schema.properties.reason).toMatchObject({
			type: 'string',
			format: 'textarea',
			title: 'Why?',
			minLength: 1,
		})
		expect(reasonOf({ reason: '  Beroep loopt ' })).toBe('Beroep loopt')
		expect(reasonOf({})).toBe('')
	})

	it('refuses an empty reason without submitting', async () => {
		const submit = jest.fn()
		await mountModal(submit)

		await rendered.vm.submitReason({ reason: '   ' })

		expect(submit).not.toHaveBeenCalled()
		expect(mockSetResult).toHaveBeenCalledWith({
			error: 'A reason is required.',
		})
	})

	it('submits the reason and closes on success', async () => {
		const submit = jest
			.fn()
			.mockResolvedValue({ ok: true, data: { status: 'rejected' } })
		const onDone = jest.fn()
		await mountModal(submit, onDone)

		await rendered.vm.submitReason({ reason: ' Lijst onvolledig ' })

		expect(submit).toHaveBeenCalledWith('Lijst onvolledig')
		expect(mockSetResult).toHaveBeenCalledWith({ success: true })
		expect(onDone).toHaveBeenCalledWith(
			{ status: 'rejected' },
			'Lijst onvolledig',
		)
	})

	it('keeps the dialog open with the refusal', async () => {
		const onDone = jest.fn()
		await mountModal(
			jest.fn().mockResolvedValue({ ok: false, error: 'Onvoldoende rechten' }),
			onDone,
		)

		await rendered.vm.submitReason({ reason: 'Lijst onvolledig' })

		expect(onDone).not.toHaveBeenCalled()
		expect(mockSetResult).toHaveBeenCalledWith({ error: 'Onvoldoende rechten' })
	})

	it('closes without a reason', async () => {
		const onClose = jest.fn()
		rendered = await renderStubbed(RequiredReasonModal, {
			props: {
				dialogTitle: 'Keep',
				label: 'Why?',
				submit: jest.fn(),
				onClose,
			},
			keep: ['CnFormDialog'],
		})

		rendered.vm.close()

		expect(onClose).toHaveBeenCalled()
	})

	it('says it could not save when the refusal names nothing', async () => {
		await mountModal(jest.fn().mockResolvedValue({ ok: false, error: '' }))

		await rendered.vm.submitReason({ reason: 'Lijst onvolledig' })

		expect(mockSetResult).toHaveBeenCalledWith({
			error: 'This could not be saved. Try again later.',
		})
	})
})
