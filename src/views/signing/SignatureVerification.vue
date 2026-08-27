<template>
	<div class="signature-verification">
		<h2>{{ t('filinq', 'Signature Verification') }}</h2>
		<div class="verify-form">
			<input
				v-model="verifyFileId"
				type="text"
				:aria-label="t('filinq', 'File ID to verify')"
				:placeholder="t('filinq', 'Enter file ID')" />
			<NcButton variant="primary" :disabled="!verifyFileId" @click="verify">
				{{ t('filinq', 'Verify') }}
			</NcButton>
		</div>
		<div v-if="signingStore.verificationResult" class="results">
			<p>
				<strong>{{ t('filinq', 'File') }}:</strong>
				{{ signingStore.verificationResult.fileName }}
			</p>
			<p>
				<strong>{{ t('filinq', 'Verdict') }}:</strong>
				<span class="verdict-badge" :class="'verdict-' + verdict">{{
					verdictLabel
				}}</span>
			</p>
			<p>
				<strong>{{ t('filinq', 'Signatures') }}:</strong>
				{{ signingStore.verificationResult.signatures.length }}
			</p>
			<ul
				v-if="signingStore.verificationResult.signatures.length"
				class="signature-list">
				<li
					v-for="(signature, index) in signingStore.verificationResult
						.signatures"
					:key="index">
					<span
						class="status-badge"
						:class="'status-' + signature.status"
						>{{ statusLabel(signature.status) }}</span
					>
					<span class="signer">{{ signature.signer }}</span>
					<span v-if="signature.reason" class="reason"
						>({{ reasonLabel(signature.reason) }})</span
					>
				</li>
			</ul>
			<p class="results__attribution">
				{{ t('filinq', 'Verification provided by the signing engine.') }}
			</p>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SignatureVerification',
	components: { NcButton },
	props: {
		/**
		 * Optional file id passed via the manifest route param
		 * (`/signing/verify/:fileId`); pre-fills and auto-runs the check
		 * when a request detail deep-links here. The field stays editable
		 * so a user can also verify an arbitrary file id directly.
		 */
		fileId: { type: String, default: '' },
	},

	data() {
		return { verifyFileId: this.fileId }
	},

	computed: {
		signingStore() {
			return useSigningStore()
		},

		/**
		 * Tri-state document verdict (signing-trust-rebuild REQ-DDSTR-005):
		 * verified | tampered | unverifiable | mixed. Falls back to the
		 * strict `isValid` boolean for a pre-rebuild verification result
		 * shape so an older cached result never crashes the view.
		 *
		 * @return {string} The verdict key.
		 */
		verdict() {
			const result = this.signingStore.verificationResult
			if (!result) {
				return 'unverifiable'
			}

			if (result.verdict) {
				return result.verdict
			}

			return result.isValid ? 'verified' : 'unverifiable'
		},

		/**
		 * Human-readable label for the current verdict.
		 *
		 * @return {string} The translated verdict label.
		 */
		verdictLabel() {
			return this.verdictLabel_(this.verdict)
		},
	},

	mounted() {
		if (this.verifyFileId) {
			this.verify()
		}
	},

	methods: {
		t,
		/**
		 * Verify the signatures of the entered file ID. Renders the
		 * `SigningController::verify()` result verbatim (see the
		 * `results__attribution` note in the template) — this component
		 * does not compute or assert its own signature validity
		 * (design.md D4 / REQ-DDOSR-004).
		 *
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-signing-authoring-and-verify-are-reachable-with-trust-actions-gated-req-ddosr-004
		 */
		async verify() {
			if (this.verifyFileId) {
				await this.signingStore.verifyDocument(this.verifyFileId)
			}
		},

		/**
		 * Human-readable label for the document-level tri-state verdict.
		 *
		 * @param {string} verdict The verdict key (verified|tampered|unverifiable|mixed).
		 * @return {string} The translated verdict label.
		 * @spec openspec/specs/document-signing/spec.md#requirement-verification-reports-three-honest-states-req-ddstr-005
		 */
		verdictLabel_(verdict) {
			const labels = {
				verified: t('filinq', 'Verified'),
				tampered: t('filinq', 'Tampered'),
				unverifiable: t('filinq', 'Unverifiable'),
				mixed: t('filinq', 'Mixed'),
			}
			return labels[verdict] ?? verdict
		},

		/**
		 * Human-readable label for a per-signature tri-state status.
		 *
		 * @param {string} status The signature status (verified|invalid|unverifiable).
		 * @return {string} The translated status label.
		 * @spec openspec/specs/document-signing/spec.md#requirement-verification-reports-three-honest-states-req-ddstr-005
		 */
		statusLabel(status) {
			const labels = {
				verified: t('filinq', 'Verified'),
				invalid: t('filinq', 'Invalid'),
				unverifiable: t('filinq', 'Unverifiable'),
			}
			return labels[status] ?? status
		},

		/**
		 * Human-readable label for a machine-readable verification reason.
		 *
		 * @param {string} reason The reason code.
		 * @return {string} The translated reason label.
		 * @spec openspec/specs/document-signing/spec.md#requirement-verification-reports-three-honest-states-req-ddstr-005
		 */
		reasonLabel(reason) {
			const labels = {
				'legacy-assertion-v1': t(
					'filinq',
					'Legacy signature format, cannot be re-verified',
				),

				'external-signature-unsupported': t(
					'filinq',
					'External signature, not yet supported',
				),

				'mac-mismatch': t(
					'filinq',
					'Signature no longer matches the document',
				),

				'signing-secret-not-configured': t(
					'filinq',
					'Server verification secret not configured',
				),
			}
			return labels[reason] ?? reason
		},
	},
}
</script>

<style scoped>
.signature-verification {
	padding: 20px;
}

.verify-form {
	display: flex;
	gap: 12px;
	margin-bottom: 20px;
}

.verify-form input {
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	min-width: 200px;
}

.results {
	padding: 16px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
}

.signature-list {
	list-style: none;
	margin: 8px 0 0;
	padding: 0;
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.signature-list li {
	display: flex;
	align-items: center;
	gap: 8px;
}

.reason {
	color: var(--color-text-maxcontrast);
}

/*
 * Tri-state badges use NL Design System / Nextcloud CSS variables only —
 * no hardcoded colors (signing-trust-rebuild task 1.3).
 */
.verdict-badge,
.status-badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: var(--border-radius-pill, 16px);
	font-weight: bold;
	font-size: 0.9em;
}

.verdict-verified,
.status-verified {
	background-color: var(--color-success, var(--color-main-text));
	color: var(--color-primary-element-text, #fff);
}

.verdict-tampered,
.status-invalid {
	background-color: var(--color-error);
	color: var(--color-primary-element-text, #fff);
}

.verdict-unverifiable,
.verdict-mixed,
.status-unverifiable {
	background-color: var(--color-warning, var(--color-border-dark));
	color: var(--color-main-text);
}
</style>
