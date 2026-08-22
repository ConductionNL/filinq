<template>
	<NcModal
		:show="show"
		:title="t('filinq', 'Create Standing Consent')"
		@close="$emit('close')">
		<div class="create-standing-consent-modal">
			<form @submit.prevent="handleSubmit">
				<!-- Entity Text -->
				<NcTextField
					v-model="form.entityText"
					:label="t('filinq', 'Entity Name')"
					:placeholder="t('filinq', 'e.g. Acme Corp')" />

				<!-- Entity Type -->
				<div class="form-field">
					<label class="form-label">{{
						t('filinq', 'Entity Type')
					}}</label>
					<NcSelect
						v-model="form.entityType"
						:options="entityTypeOptions"
						:placeholder="t('filinq', 'Select entity type')"
						:inputLabel="t('filinq', 'Entity Type')"
						label="label"
						trackBy="value" />
				</div>

				<!-- Consent Method (required) -->
				<div class="form-field">
					<label class="form-label">
						{{ t('filinq', 'Consent Method') }}
						<span class="required">*</span>
					</label>
					<NcSelect
						v-model="form.consentMethod"
						:options="consentMethodOptions"
						:placeholder="t('filinq', 'Select consent method')"
						:inputLabel="t('filinq', 'Consent Method')"
						label="label"
						trackBy="value" />
					<span v-if="errors.consentMethod" class="field-error">
						{{ errors.consentMethod }}
					</span>
				</div>

				<!-- Valid From -->
				<NcTextField
					v-model="form.validFrom"
					:label="t('filinq', 'Valid From')"
					type="date" />

				<!-- Valid Until -->
				<NcTextField
					v-model="form.validUntil"
					:label="t('filinq', 'Valid Until')"
					type="date" />

				<!-- Warning when validUntil is blank -->
				<NcNoteCard v-if="!form.validUntil" type="warning">
					{{
						t(
							'filinq',
							'No expiry date set — this consent will remain active indefinitely unless explicitly revoked.',
						)
					}}
				</NcNoteCard>

				<!-- Match Rules -->
				<!-- NcTextArea (instead of a raw <textarea>) so the
				     component participates in the NC theme + a11y wiring;
				     `label` plus `aria-describedby` ship the screen-reader
				     hooks the raw element silently lacked. -->
				<div class="form-field">
					<NcTextArea
						v-model="matchRulesText"
						:label="t('filinq', 'Match Rules (one per line)')"
						:placeholder="t('filinq', 'e.g. acme.nl')"
						:rows="3" />
				</div>

				<!-- Actions -->
				<div class="modal-actions">
					<NcButton @click="$emit('close')">
						{{ t('filinq', 'Cancel') }}
					</NcButton>
					<NcButton variant="primary" type="submit" :disabled="saving">
						{{
							saving
								? t('filinq', 'Saving…')
								: t('filinq', 'Create')
						}}
					</NcButton>
				</div>
			</form>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcModal,
	NcNoteCard,
	NcSelect,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'

export default {
	name: 'CreateStandingConsentModal',
	components: {
		NcModal,
		NcTextField,
		NcTextArea,
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

			// Match the publicationConsent schema enum exactly. The legacy
			// lowercase / synonym values (`person`, `written`, `verbal`,
			// `digital`, `implicit`) silently passed storage validation
			// but produced records that no audit/reporting code could
			// recognise. The schema enums are uppercase entity types and
			// the four canonical consent-method values.
			entityTypeOptions: [
				{ label: t('filinq', 'Person'), value: 'PERSON' },
				{ label: t('filinq', 'Organization'), value: 'ORGANIZATION' },
			],

			consentMethodOptions: [
				{ label: t('filinq', 'Paper'), value: 'paper' },
				{
					label: t('filinq', 'Digital signature'),
					value: 'digital_signature',
				},
				{
					label: t('filinq', 'Verbal (recorded)'),
					value: 'verbal_recorded',
				},
				{ label: t('filinq', 'Opt-in form'), value: 'opt_in_form' },
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
				this.errors.consentMethod = t(
					'filinq',
					'Consent method is required.',
				)
				return
			}

			const matchRules = this.matchRulesText
				.split('\n')
				.map((line) => line.trim())
				.filter((line) => line.length > 0)

			const payload = {
				entityText: this.form.entityText,
				entityType: this.form.entityType ? this.form.entityType.value : '',
				consentMethod: this.form.consentMethod
					? this.form.consentMethod.value
					: '',

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
