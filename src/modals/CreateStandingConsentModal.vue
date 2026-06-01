<template>
	<NcModal
		:show="show"
		:title="t('docudesk', 'Create Standing Consent')"
		@close="$emit('close')">
		<div class="create-standing-consent-modal">
			<form @submit.prevent="handleSubmit">
				<!-- Entity Text -->
				<NcTextField
					:value.sync="form.entityText"
					:label="t('docudesk', 'Entity Name')"
					:placeholder="t('docudesk', 'e.g. Acme Corp')" />

				<!-- Entity Type -->
				<div class="form-field">
					<label class="form-label">{{ t('docudesk', 'Entity Type') }}</label>
					<NcSelect
						v-model="form.entityType"
						:options="entityTypeOptions"
						:placeholder="t('docudesk', 'Select entity type')"
						label="label"
						track-by="value" />
				</div>

				<!-- Consent Method (required) -->
				<div class="form-field">
					<label class="form-label">
						{{ t('docudesk', 'Consent Method') }}
						<span class="required">*</span>
					</label>
					<NcSelect
						v-model="form.consentMethod"
						:options="consentMethodOptions"
						:placeholder="t('docudesk', 'Select consent method')"
						label="label"
						track-by="value" />
					<span v-if="errors.consentMethod" class="field-error">
						{{ errors.consentMethod }}
					</span>
				</div>

				<!-- Valid From -->
				<NcTextField
					:value.sync="form.validFrom"
					:label="t('docudesk', 'Valid From')"
					type="date" />

				<!-- Valid Until -->
				<NcTextField
					:value.sync="form.validUntil"
					:label="t('docudesk', 'Valid Until')"
					type="date" />

				<!-- Warning when validUntil is blank -->
				<NcNoteCard v-if="!form.validUntil" type="warning">
					{{ t('docudesk', 'No expiry date set — this consent will remain active indefinitely unless explicitly revoked.') }}
				</NcNoteCard>

				<!-- Match Rules -->
				<div class="form-field">
					<label class="form-label">{{ t('docudesk', 'Match Rules (one per line)') }}</label>
					<textarea
						v-model="matchRulesText"
						class="match-rules-input"
						:placeholder="t('docudesk', 'e.g. acme.nl')"
						rows="3" />
				</div>

				<!-- Actions -->
				<div class="modal-actions">
					<NcButton @click="$emit('close')">
						{{ t('docudesk', 'Cancel') }}
					</NcButton>
					<NcButton
						type="primary"
						native-type="submit"
						:disabled="saving">
						{{ saving ? t('docudesk', 'Saving…') : t('docudesk', 'Create') }}
					</NcButton>
				</div>
			</form>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcModal, NcTextField, NcSelect, NcNoteCard, NcButton } from '@nextcloud/vue'

export default {
	name: 'CreateStandingConsentModal',
	components: {
		NcModal,
		NcTextField,
		NcSelect,
		NcNoteCard,
		NcButton,
	},
	props: {
		show: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['close', 'created'],
	data() {
		return {
			saving: false,
			matchRulesText: '',
			form: {
				entityText: '',
				entityType: null,
				consentMethod: null,
				validFrom: '',
				validUntil: '',
			},
			errors: {
				consentMethod: '',
			},
			entityTypeOptions: [
				{ label: t('docudesk', 'Person'), value: 'person' },
				{ label: t('docudesk', 'Organization'), value: 'organization' },
			],
			consentMethodOptions: [
				{ label: t('docudesk', 'Written'), value: 'written' },
				{ label: t('docudesk', 'Verbal'), value: 'verbal' },
				{ label: t('docudesk', 'Digital'), value: 'digital' },
				{ label: t('docudesk', 'Implicit'), value: 'implicit' },
			],
		}
	},
	methods: {
		/**
		 * Validate and submit the create-standing-consent form.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		handleSubmit() {
			this.errors.consentMethod = ''

			if (this.form.consentMethod === null || this.form.consentMethod === '') {
				this.errors.consentMethod = t('docudesk', 'Consent method is required.')
				return
			}

			const matchRules = this.matchRulesText
				.split('\n')
				.map(line => line.trim())
				.filter(line => line.length > 0)

			const payload = {
				entityText: this.form.entityText,
				entityType: this.form.entityType ? this.form.entityType.value : '',
				consentMethod: this.form.consentMethod ? this.form.consentMethod.value : '',
				matchRules,
				validFrom: this.form.validFrom || null,
				validUntil: this.form.validUntil || null,
				scope: 'entity',
				active: true,
			}

			this.$emit('created', payload)
		},
		/**
		 * Reset the form to its initial state.
		 *
		 * @spec openspec/changes/publication-consent-policy-fields/tasks.md#task-11
		 */
		resetForm() {
			this.form = {
				entityText: '',
				entityType: null,
				consentMethod: null,
				validFrom: '',
				validUntil: '',
			}
			this.matchRulesText = ''
			this.errors.consentMethod = ''
			this.saving = false
		},
	},
}
</script>

<style scoped>
.create-standing-consent-modal {
	padding: 16px;
}

.form-field {
	margin-bottom: 12px;
}

.form-label {
	display: block;
	font-weight: 600;
	margin-bottom: 4px;
	font-size: 0.875rem;
}

.required {
	color: var(--color-error);
	margin-left: 2px;
}

.field-error {
	color: var(--color-error);
	font-size: 0.75rem;
	display: block;
	margin-top: 4px;
}

.match-rules-input {
	width: 100%;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	padding: 8px;
	resize: vertical;
	font-family: inherit;
	font-size: inherit;
}

.modal-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 16px;
}
</style>
