/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Withdrawing a signing request from its detail page: the action shows only
 * to the creator of an open request, a refusal shows the server's message
 * (which names the creator), and a withdrawal reloads the request.
 *
 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
 */

let mockCurrentUser = { uid: 'alice' }

jest.mock('@nextcloud/auth', () => ({ getCurrentUser: () => mockCurrentUser }))
jest.mock('@nextcloud/l10n', () => ({ translate: (app, text) => text }))
jest.mock('@nextcloud/vue', () => ({ NcButton: {}, NcLoadingIcon: {}, NcNoteCard: {}, NcDialog: {} }))
jest.mock('@conduction/nextcloud-vue', () => ({ CnDialogActions: {} }))
jest.mock('../../modals/SignerStepUpModal.vue', () => ({ default: {} }))
jest.mock('./SigningEnvelopePanel.vue', () => ({ default: {} }))
jest.mock('../../dialogs/CancelSigningRequestDialog.vue', () => ({ default: {} }))
jest.mock('../../services/signerStepUp.js', () => ({ stepUpReturn: () => ({ status: '', signerId: '' }) }))
jest.mock('../../store/modules/signing.js', () => ({ useSigningStore: () => ({}) }))

const SigningRequestDetail = require('./SigningRequestDetail.vue').default

/**
 * A bare page carrying the component's computed and methods over a store.
 *
 * @param {object} store The signing store stand-in.
 * @return {object} The page.
 */
function page(store) {
	const self = { id: 'req-1', signingStore: store, ...SigningRequestDetail.data() }
	for (const [name, method] of Object.entries(SigningRequestDetail.methods)) {
		self[name] = method.bind(self)
	}
	Object.defineProperty(self, 'canWithdraw', { get: () => SigningRequestDetail.computed.canWithdraw.call(self) })
	return self
}

describe('withdrawing a signing request', () => {
	beforeEach(() => {
		mockCurrentUser = { uid: 'alice' }
	})

	it('offers the action only to the creator of an open request', () => {
		expect(page({ signingRequest: { initiatorUserId: 'alice', status: 'PENDING' } }).canWithdraw).toBe(true)
		expect(page({ signingRequest: { initiatorUserId: 'alice', status: 'in_progress' } }).canWithdraw).toBe(true)
		expect(page({ signingRequest: { initiatorUserId: 'bob', status: 'PENDING' } }).canWithdraw).toBe(false)
		expect(page({ signingRequest: { initiatorUserId: 'alice', status: 'COMPLETED' } }).canWithdraw).toBe(false)
		expect(page({ signingRequest: { initiatorUserId: 'alice', status: 'CANCELLED' } }).canWithdraw).toBe(false)
		mockCurrentUser = null
		expect(page({ signingRequest: { initiatorUserId: '', status: 'PENDING' } }).canWithdraw).toBe(false)
	})

	it('shows the server refusal, which names the creator, and does not reload', async () => {
		const store = {
			signingRequest: { initiatorUserId: 'alice', status: 'PENDING' },
			error: 'Only the person who created this signing request can withdraw it: alice.',
			cancelRequest: jest.fn(async () => false),
			fetchSigningRequest: jest.fn(),
			fetchAuditTrail: jest.fn(),
		}
		const self = page(store)
		self.confirmingWithdraw = true

		await self.withdraw()

		expect(store.cancelRequest).toHaveBeenCalledWith('req-1')
		expect(self.withdrawError).toContain('alice')
		expect(self.withdrawn).toBe(false)
		expect(self.confirmingWithdraw).toBe(false)
		expect(store.fetchSigningRequest).not.toHaveBeenCalled()
	})

	it('reloads the request after a withdrawal so it reads cancelled', async () => {
		const store = {
			signingRequest: { initiatorUserId: 'alice', status: 'PENDING' },
			error: null,
			cancelRequest: jest.fn(async () => true),
			fetchSigningRequest: jest.fn(async () => undefined),
			fetchAuditTrail: jest.fn(async () => undefined),
		}
		const self = page(store)

		await self.withdraw()

		expect(self.withdrawn).toBe(true)
		expect(self.withdrawError).toBe('')
		expect(store.fetchSigningRequest).toHaveBeenCalledWith('req-1')
		expect(store.fetchAuditTrail).toHaveBeenCalledWith('req-1')
	})
})
