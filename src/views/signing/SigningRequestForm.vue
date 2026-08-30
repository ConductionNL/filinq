<template>
	<div class="signing-request-form">
		<h2>{{ t('filinq', 'New Signing Request') }}</h2>
		<NcNoteCard type="info" class="signing-request-form__notice">
			{{
				t(
					'filinq',
					'This composes a draft signing request only. Sending for signature, provider binding and signer-identity assertion are not available yet — the signature level below records your intent but is not yet cryptographically enforced.',
				)
			}}
		</NcNoteCard>
		<div class="form-group">
			<label for="signing-request-document-file-id">{{
				t('filinq', 'Document File ID')
			}}</label>
			<input
				id="signing-request-document-file-id"
				v-model="form.documentFileId"
				type="text" />
		</div>
		<div class="form-group">
			<label for="signing-request-document-name">{{
				t('filinq', 'Document Name')
			}}</label>
			<input
				id="signing-request-document-name"
				v-model="form.documentName"
				type="text" />
		</div>
		<div class="form-group">
			<label>{{ t('filinq', 'Signature Level') }}</label>
			<select v-model="form.signatureLevel">
				<option value="SES">
					{{ t('filinq', 'SES - Simple') }}
				</option>
				<option value="AdES">
					{{ t('filinq', 'AdES - Advanced') }}
				</option>
				<option value="QES">
					{{ t('filinq', 'QES - Qualified') }}
				</option>
			</select>
		</div>
		<div class="form-group">
			<label>{{ t('filinq', 'Signing Mode') }}</label>
			<select v-model="form.signingMode">
				<option value="sequential">
					{{ t('filinq', 'Sequential') }}
				</option>
				<option value="parallel">
					{{ t('filinq', 'Parallel') }}
				</option>
			</select>
		</div>
		<NcButton
			variant="primary"
			:disabled="!form.documentFileId || !form.documentName"
			@click="submit">
			{{ t('filinq', 'Create Signing Request') }}
		</NcButton>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard } from '@nextcloud/vue'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningRequestForm',
	components: { NcButton, NcNoteCard },
	data() {
		return {
			form: {
				documentFileId: '',
				documentName: '',
				signatureLevel: 'SES',
				signingMode: 'sequential',
				signers: [],
			},
		}
	},

	methods: {
		t,
		/**
		 * Validate and submit the new signing request form. Drafts a
		 * signingRequest record only (POST signing#createRequest) — this
		 * does not send for signature, bind a provider, or assert signer
		 * identity, so it stays within the non-trust-bearing composition
		 * this restored page is scoped to (design.md D4).
		 *
		 * @spec openspec/changes/orphaned-surface-restoration/specs/orphaned-surface-restoration/spec.md#requirement-signing-authoring-and-verify-are-reachable-with-trust-actions-gated-req-ddosr-004
		 */
		async submit() {
			const signingStore = useSigningStore()
			const result = await signingStore.createSigningRequest(this.form)
			if (result) {
				showSuccess(t('filinq', 'Signing request created'))
				this.$router.push({ name: 'SigningRequests' })
			} else {
				showError(t('filinq', 'Failed to create signing request'))
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

.signing-request-form__notice {
	margin-bottom: 16px;
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
