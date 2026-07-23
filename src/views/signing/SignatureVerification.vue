<template>
	<div class="signature-verification">
		<h2>{{ t('docudesk', 'Signature Verification') }}</h2>
		<div class="verify-form">
			<input v-model="verifyFileId" type="text" :placeholder="t('docudesk', 'Enter file ID')">
			<NcButton type="primary" :disabled="!verifyFileId" @click="verify">
				{{ t('docudesk', 'Verify') }}
			</NcButton>
		</div>
		<div v-if="signingStore.verificationResult" class="results">
			<p><strong>{{ t('docudesk', 'File') }}:</strong> {{ signingStore.verificationResult.fileName }}</p>
			<p><strong>{{ t('docudesk', 'Valid') }}:</strong> {{ signingStore.verificationResult.isValid ? t('docudesk', 'Yes') : t('docudesk', 'No') }}</p>
			<p><strong>{{ t('docudesk', 'Signatures') }}:</strong> {{ signingStore.verificationResult.signatures.length }}</p>
			<p class="results__attribution">
				{{ t('docudesk', 'Verification provided by the signing engine.') }}
			</p>
		</div>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
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
	computed: { signingStore() { return useSigningStore() } },
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
		async verify() { if (this.verifyFileId) { await this.signingStore.verifyDocument(this.verifyFileId) } },
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
</style>
