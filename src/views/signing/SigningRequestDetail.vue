<template>
	<div class="signing-request-detail">
		<NcLoadingIcon
			v-if="signingStore.loading && !signingStore.signingRequest"
			:size="44" />
		<template v-else-if="signingStore.signingRequest">
			<h2>{{ signingStore.signingRequest.documentName }}</h2>
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
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import SignerStepUpModal from '../../modals/SignerStepUpModal.vue'
import SigningEnvelopePanel from './SigningEnvelopePanel.vue'
import { stepUpReturn } from '../../services/signerStepUp.js'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningRequestDetail',
	components: {
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		SignerStepUpModal,
		SigningEnvelopePanel,
	},

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
		return { signed: false }
	},

	computed: {
		/**
		 * What the identity broker left in the query when it sent the signer back.
		 *
		 * @return {object} `{ status, signerId }`.
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		returned() {
			return stepUpReturn(this.$route?.query)
		},

		/**
		 * Did the signer come back from a confirmed step-up for a signer record.
		 *
		 * @return {boolean} True when the signature can be tried again.
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
		 */
		stepUpDone() {
			return this.returned.status === 'done' && this.returned.signerId !== ''
		},
	},

	methods: {
		/**
		 * Try the signature again after a step-up (REQ-DDSIR-003). A refusal
		 * that still needs a stronger identity opens the step-up dialog.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
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
