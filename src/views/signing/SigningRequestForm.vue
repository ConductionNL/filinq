<template>
	<div class="signing-request-form">
		<h2>{{ t('docudesk', 'New Signing Request') }}</h2>
		<div class="form-group">
			<label>{{ t('docudesk', 'Document File ID') }}</label>
			<input v-model="form.documentFileId" type="text">
		</div>
		<div class="form-group">
			<label>{{ t('docudesk', 'Document Name') }}</label>
			<input v-model="form.documentName" type="text">
		</div>
		<div class="form-group">
			<label>{{ t('docudesk', 'Signature Level') }}</label>
			<select v-model="form.signatureLevel">
				<option value="SES">
					{{ t('docudesk', 'SES - Simple') }}
				</option>
				<option value="AdES">
					{{ t('docudesk', 'AdES - Advanced') }}
				</option>
				<option value="QES">
					{{ t('docudesk', 'QES - Qualified') }}
				</option>
			</select>
		</div>
		<div class="form-group">
			<label>{{ t('docudesk', 'Signing Mode') }}</label>
			<select v-model="form.signingMode">
				<option value="sequential">
					{{ t('docudesk', 'Sequential') }}
				</option>
				<option value="parallel">
					{{ t('docudesk', 'Parallel') }}
				</option>
			</select>
		</div>
		<NcButton type="primary" :disabled="!form.documentFileId || !form.documentName" @click="submit">
			{{ t('docudesk', 'Create Signing Request') }}
		</NcButton>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { useSigningStore } from '../../store/modules/signing.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'SigningRequestForm',
	components: { NcButton },
	data() {
		return {
			form: { documentFileId: '', documentName: '', signatureLevel: 'SES', signingMode: 'sequential', signers: [] },
		}
	},
	methods: {
		t,
		/**
		 * Validate and submit the new signing request form.
		 *
		 * @spec openspec/changes/digital-signing-integration/tasks.md#8-4
		 */
		async submit() {
			const signingStore = useSigningStore()
			const result = await signingStore.createSigningRequest(this.form)
			if (result) {
				showSuccess(t('docudesk', 'Signing request created'))
				this.$router.push({ name: 'Signing' })
			} else {
				showError(t('docudesk', 'Failed to create signing request'))
			}
		},
	},
}
</script>

<style scoped>
.signing-request-form {
	padding: 20px;
	max-width: 600px;
}

.form-group {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin-bottom: 16px;
}

.form-group label {
	font-weight: bold;
	color: var(--color-main-text);
}

.form-group input,
.form-group select {
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
}
</style>
