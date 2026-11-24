<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="signing-request-detail">
		<NcLoadingIcon
			v-if="signingStore.loading && !signingStore.signingRequest"
			:size="44" />
		<template v-else-if="signingStore.signingRequest">
			<h2>{{ signingStore.signingRequest.documentName }}</h2>
			<NcButton
				v-if="leafSidebarAvailable"
				variant="tertiary"
				@click="toggleLeafSidebar">
				{{ t('filinq', 'Linked apps') }}
			</NcButton>
			<div class="detail-grid">
				<div>
					<strong>{{ t('filinq', 'Status') }}</strong
					>: {{ signingStore.signingRequest.status }}
				</div>
				<div>
					<strong>{{ t('filinq', 'Level') }}</strong
					>: {{ signingStore.signingRequest.signatureLevel }}
				</div>
				<div>
					<strong>{{ t('filinq', 'Mode') }}</strong
					>: {{ signingStore.signingRequest.signingMode }}
				</div>
				<div>
					<strong>{{ t('filinq', 'Provider') }}</strong
					>: {{ signingStore.signingRequest.provider }}
				</div>
			</div>
			<h3>{{ t('filinq', 'Recipients') }}</h3>
			<table v-if="recipients.length > 0" class="audit-table">
				<thead>
					<tr>
						<th scope="col">{{ t('filinq', 'Name') }}</th>
						<th scope="col">{{ t('filinq', 'Role') }}</th>
						<th scope="col">{{ t('filinq', 'State') }}</th>
						<th scope="col">{{ t('filinq', 'Signed or accepted at') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="recipient in recipients" :key="recipient.id">
						<td>{{ recipient.name }}</td>
						<td>{{ recipient.role }}</td>
						<td>{{ recipient.status }}</td>
						<td>{{ recipient.at }}</td>
					</tr>
				</tbody>
			</table>
			<p v-else>
				{{ t('filinq', 'No recipients yet.') }}
			</p>
			<SigningEnvelopePanel
				v-if="signingStore.signingRequest.envelopeRef"
				:envelopeId="signingStore.signingRequest.envelopeRef"
				@changed="reload" />
			<NcNoteCard v-if="stepUpDone && !signed" type="success">
				{{
					t(
						'filinq',
						'Your identity is confirmed. Sign now; the confirmation counts for 15 minutes.',
					)
				}}
				<NcButton
					variant="primary"
					:disabled="signingStore.loading"
					@click="signNow">
					{{ t('filinq', 'Sign now') }}
				</NcButton>
			</NcNoteCard>
			<NcNoteCard v-if="signed" type="success">
				{{ t('filinq', 'You signed this document.') }}
			</NcNoteCard>
			<SignerStepUpModal
				v-if="signingStore.stepUp"
				:show="true"
				:requestId="id"
				:signerId="returned.signerId"
				:requiredAssurance="signingStore.stepUp.requiredAssurance"
				@close="signingStore.stepUp = null"
				@ready="signNow" />
			<NcButton
				v-if="signingStore.signingRequest.documentFileId"
				variant="secondary"
				@click="openVerify">
				{{ t('filinq', 'Verify') }}
			</NcButton>
			<SigningEvidenceCard
				:requestId="id"
				:status="signingStore.signingRequest.status || ''" />
			<NcButton
				v-if="canWithdraw"
				variant="error"
				data-testid="signing-request-withdraw"
				:disabled="signingStore.loading"
				@click="confirmingWithdraw = true">
				{{ t('filinq', 'Withdraw request') }}
			</NcButton>
			<CancelSigningRequestDialog
				v-if="confirmingWithdraw"
				:busy="signingStore.loading"
				@cancel="confirmingWithdraw = false"
				@confirm="withdraw" />
			<NcNoteCard v-if="withdrawn" type="success">
				{{ t('filinq', 'The signing request was withdrawn.') }}
			</NcNoteCard>
			<NcNoteCard
				v-if="withdrawError"
				type="error"
				data-testid="signing-request-withdraw-error">
				{{ withdrawError }}
			</NcNoteCard>
			<h3>{{ t('filinq', 'Audit Trail') }}</h3>
			<table v-if="signingStore.auditTrail.length > 0" class="audit-table">
				<thead>
					<tr>
						<th scope="col">{{ t('filinq', 'Action') }}</th>
						<th scope="col">{{ t('filinq', 'Actor') }}</th>
						<th scope="col">{{ t('filinq', 'Timestamp') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="entry in signingStore.auditTrail"
						:key="entry.id || entry.uuid">
						<td>{{ entry.action }}</td>
						<td>{{ entry.actorDisplayName }}</td>
						<td>
							{{
								entry.timestamp
									? new Date(entry.timestamp).toLocaleString()
									: '-'
							}}
						</td>
					</tr>
				</tbody>
			</table>
			<p v-else>
				{{ t('filinq', 'No audit entries yet.') }}
			</p>
		</template>
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import CancelSigningRequestDialog from '../../dialogs/CancelSigningRequestDialog.vue'
import SignerStepUpModal from '../../modals/SignerStepUpModal.vue'
import SigningEnvelopePanel from './SigningEnvelopePanel.vue'
import SigningEvidenceCard from './SigningEvidenceCard.vue'
import { stepUpReturn } from '../../services/signerStepUp.js'
import { recordLeafSidebarMixin } from '../../sidebars/recordSidebar.js'
import { useSigningStore } from '../../store/modules/signing.js'
import { recipientRows } from './recipientList.js'

/**
 * The states a signing request can still be withdrawn from.
 */
const OPEN_STATUSES = ['DRAFT', 'PENDING', 'IN_PROGRESS']

export default {
	name: 'SigningRequestDetail',
	components: {
		CancelSigningRequestDialog,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		SignerStepUpModal,
		SigningEnvelopePanel,
		SigningEvidenceCard,
	},

	// leaf-integrations: the registry's leaves (Mail, Calendar) for this request.
	mixins: [recordLeafSidebarMixin({ schema: 'signingRequest', record: (vm) => vm.signingStore.signingRequest, title: (request) => request.documentName })],

	props: {
		/**
		 * The signing request to show.
		 *
		 * MUST be named `id`, because that is the name of the route
		 * parameter. src/main.js builds this route with `props: true` for
		 * any path containing a `:`, and vue-router's `props: true` passes
		 * `route.params` through BY NAME. The manifest route is
		 * `/signing/:id`, so a prop called anything else is simply never
		 * supplied — this was declared `requestId` and arrived `undefined`,
		 * which made the page fetch `/api/signing/requests/undefined` and
		 * render blank for every request. Its sibling
		 * SignatureVerification has always worked because its route is
		 * `/signing/verify/:fileId` and its prop is `fileId` — the names
		 * match there by accident of naming, not by design.
		 */
		id: { type: String, required: true },
	},

	/**
	 * Load the signing request and its audit trail on mount.
	 *
	 * @param props
	 * @spec openspec/changes/digital-signing-integration/tasks.md#8-3
	 */
	setup(props) {
		const signingStore = useSigningStore()
		signingStore.fetchSigningRequest(props.id)
		signingStore.fetchAuditTrail(props.id)
		return { signingStore, t }
	},

	data() {
		return { signed: false, confirmingWithdraw: false, withdrawn: false, withdrawError: '' }
	},

	computed: {
		/**
		 * Whether the viewer may withdraw this request: only its creator, and
		 * only while it is still open. The server decides; this only hides a
		 * button that would be refused.
		 *
		 * @return {boolean} True for the creator of an open request.
		 *
		 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
		 */
		canWithdraw() {
			const request = this.signingStore.signingRequest || {}
			const uid = getCurrentUser()?.uid || ''
			return (
				uid !== ''
				&& request.initiatorUserId === uid
				&& OPEN_STATUSES.includes(String(request.status || '').toUpperCase())
			)
		},

		/**
		 * What the identity broker left in the query when it sent the signer back.
		 *
		 * @return {object} `{ status, signerId }`.
		 *
		 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		returned() {
			return stepUpReturn(this.$route?.query)
		},

		/**
		 * Did the signer come back from a confirmed step-up for a signer record.
		 *
		 * @return {boolean} True when the signature can be tried again.
		 *
		 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		stepUpDone() {
			return this.returned.status === 'done' && this.returned.signerId !== ''
		},

		/**
		 * Who signs and who only accepts, with their state and time (REQ-SAO-001..003).
		 *
		 * @return {Array<object>} The recipient rows.
		 *
		 * @spec openspec/changes/signing-accept-only-recipient/tasks.md#4-screens
		 */
		recipients() {
			return recipientRows(this.signingStore.signingRequest?.recipients)
		},
	},

	methods: {
		/**
		 * Try the signature again after a step-up (REQ-DDSIR-003). A refusal
		 * that still needs a stronger identity opens the step-up dialog.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/archive/2026-11-24-signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		async signNow() {
			const result = await this.signingStore.signDocument(
				this.id,
				this.returned.signerId,
			)
			if (result) {
				this.signed = true
				await this.signingStore.fetchSigningRequest(this.id)
				await this.signingStore.fetchAuditTrail(this.id)
			}
		},

		/**
		 * Withdraw the request after the confirmation. A refusal shows the
		 * server's message, which names the creator; success reloads the
		 * request so its status reads cancelled.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
		 */
		async withdraw() {
			this.withdrawError = ''
			const done = await this.signingStore.cancelRequest(this.id)
			this.confirmingWithdraw = false
			if (done === false) {
				this.withdrawError = String(this.signingStore.error || '')
				return
			}
			this.withdrawn = true
			await this.reload()
		},

		/**
		 * Read the request and its audit trail again after the envelope changed them.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-batch-and-envelope-surfaces-are-first-class-ui-req-ddbsf-005
		 */
		async reload() {
			await this.signingStore.fetchSigningRequest(this.id)
			await this.signingStore.fetchAuditTrail(this.id)
		},

		/**
		 * Navigate to the restored SignatureVerification page for this
		 * request's document file id.
		 *
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-signing-authoring-and-verify-are-reachable-with-trust-actions-gated-req-ddosr-004
		 */
		openVerify() {
			this.$router.push({
				name: 'SignatureVerification',
				params: {
					fileId: String(this.signingStore.signingRequest.documentFileId),
				},
			})
		},
	},
}
</script>

<style scoped>
.signing-request-detail {
	padding: 20px;
}

.detail-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 12px;
	margin-bottom: 20px;
}

.audit-table {
	width: 100%;
	border-collapse: collapse;
}

.audit-table th,
.audit-table td {
	padding: 10px;
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}
</style>
