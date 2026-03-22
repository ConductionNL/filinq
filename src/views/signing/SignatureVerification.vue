<template>
	<div class="signature-verification">
		<h2>{{ t('docudesk', 'Signature Verification') }}</h2>
		<div class="verify-form">
			<input v-model="fileId" type="text" :placeholder="t('docudesk', 'Enter file ID')">
			<NcButton type="primary" :disabled="!fileId" @click="verify">
				{{ t('docudesk', 'Verify') }}
			</NcButton>
		</div>
		<div v-if="signingStore.verificationResult" class="results">
			<p><strong>{{ t('docudesk', 'File') }}:</strong> {{ signingStore.verificationResult.fileName }}</p>
			<p><strong>{{ t('docudesk', 'Valid') }}:</strong> {{ signingStore.verificationResult.isValid ? 'Yes' : 'No' }}</p>
			<p><strong>{{ t('docudesk', 'Signatures') }}:</strong> {{ signingStore.verificationResult.signatures.length }}</p>
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
	data() { return { fileId: '' } },
	computed: { signingStore() { return useSigningStore() } },
	methods: {
		t,
		async verify() { if (this.fileId) { await this.signingStore.verifyDocument(this.fileId) } },
	},
}
</script>

<style scoped>
.signature-verification { padding: 20px; }
.verify-form { display: flex; gap: 12px; margin-bottom: 20px; }
.verify-form input { padding: 8px; border: 1px solid var(--color-border); border-radius: var(--border-radius); min-width: 200px; }
.results { padding: 16px; border: 1px solid var(--color-border); border-radius: var(--border-radius); }
</style>
