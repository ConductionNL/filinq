<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'

/**
 *
 */
function blankForm() {
	return {
		entityText: '',
		entityType: 'PERSON',
		consentMethod: '',
		consentDocument: '',
		consentScope: '',
		legalBasis: '',
		validFrom: '',
		validUntil: '',
		active: true,
		matchRules: [],
		consentStatus: 'consent_given',
		publicationDecision: 'publish_with_consent',
		notificationStatus: 'skipped',
	}
}

export default {
	name: 'StandingConsentFormModal',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcSelect,
		NcTextField,
		Delete,
	},

	props: {
		open: { type: Boolean, required: true },
		editingRecord: { type: Object, default: null },
		saving: { type: Boolean, default: false },
		formError: { type: String, default: '' },
	},

	emits: ['update:open', 'submit', 'cancel'],
	data() {
		return {
			form: blankForm(),
			entityTypeOptions: ['PERSON', 'ORGANIZATION', 'OTHER'],
			consentMethodOptions: [
				'paper',
				'digital_signature',
				'verbal_recorded',
				'opt_in_form',
			],

			matchTypeOptions: ['exact', 'normalized', 'bsn', 'kvk'],
		}
	},

	computed: {
		editing() {
			return this.editingRecord !== null
		},

		/**
		 * Modal heading — "Edit" when an existing standing consent was handed
		 * in, "Add" for a create on the Standing Publication Consents surface.
		 *
		 * @return {string}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		dialogTitle() {
			return this.editing
				? t('filinq', 'Edit standing consent')
				: t('filinq', 'Add standing consent')
		},

		canSubmit() {
			return (
				this.form.entityText.trim() !== ''
				&& this.form.consentMethod !== ''
				&& this.form.matchRules.length > 0
				&& this.form.matchRules.every((r) => r.type && r.value !== '')
			)
		},
	},

	watch: {
		open(now) {
			if (now) {
				this.resetForm()
			}
		},
	},

	methods: {
		t,
		resetForm() {
			if (this.editingRecord) {
				this.form = {
					...blankForm(),
					...this.editingRecord,
					matchRules: Array.isArray(this.editingRecord.matchRules)
						? this.editingRecord.matchRules.map((r) => ({ ...r }))
						: [],
				}
			} else {
				this.form = blankForm()
			}
		},

		addRule() {
			this.form.matchRules.push({ type: 'exact', value: '' })
		},

		removeRule(idx) {
			this.form.matchRules.splice(idx, 1)
		},

		submit() {
			this.$emit('submit', {
				...this.form,
				matchRules: this.form.matchRules.map((r) => ({ ...r })),
			})
		},

		onCancel() {
			this.$emit('update:open', false)
			this.$emit('cancel')
		},
	},
}
</script>

<template>
	<NcDialog
		v-if="open"
		:name="dialogTitle"
		:open="open"
		size="normal"
		@update:open="$emit('update:open', $event)">
		<div class="standing-consent-form">
			<NcTextField
				v-model="form.entityText"
				:label="t('filinq', 'Entity text (display name)')"
				required />
			<NcSelect
				v-model="form.entityType"
				:options="entityTypeOptions"
				:inputLabel="t('filinq', 'Entity type')"
				:label="t('filinq', 'Entity type')"
				required />
			<NcSelect
				v-model="form.consentMethod"
				:options="consentMethodOptions"
				:inputLabel="t('filinq', 'Consent method')"
				:label="t('filinq', 'Consent method')"
				required />
			<NcTextField
				v-model="form.consentDocument"
				:label="t('filinq', 'Consent document (file id or URL)')" />
			<NcTextField
				v-model="form.consentScope"
				:label="
					t(
						'filinq',
						'Consent scope (e.g. \'2024-2025 municipal decisions\')',
					)
				" />
			<NcTextField
				v-model="form.legalBasis"
				:label="t('filinq', 'Legal basis')" />
			<NcTextField
				v-model="form.validFrom"
				:label="t('filinq', 'Valid from (ISO 8601, optional)')" />
			<NcTextField
				v-model="form.validUntil"
				:label="t('filinq', 'Valid until (ISO 8601, optional)')" />
			<NcCheckboxRadioSwitch v-model="form.active" type="switch">
				{{ t('filinq', 'Active') }}
			</NcCheckboxRadioSwitch>

			<div v-if="!form.validUntil" class="form-warning">
				{{
					t(
						'filinq',
						'No expiry set — this standing consent will remain in force indefinitely. Consider setting a "Valid until" date.',
					)
				}}
			</div>

			<h4>{{ t('filinq', 'Match rules') }}</h4>
			<div v-if="!form.matchRules?.length" class="form-warning">
				{{
					t(
						'filinq',
						'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches.',
					)
				}}
			</div>
			<div
				v-for="(rule, idx) in form.matchRules"
				:key="idx"
				class="match-rule-row">
				<NcSelect
					v-model="rule.type"
					:options="matchTypeOptions"
					:inputLabel="t('filinq', 'Match type')"
					:label="t('filinq', 'Match type')" />
				<NcTextField
					v-model="rule.value"
					:label="t('filinq', 'Match value')" />
				<!--
					Icon-only, so it needs its own name. The row index is part of
					it: every row renders the same icon, and "Remove match rule"
					repeated N times tells a screen-reader user nothing about
					which row focus is on (WCAG 4.1.2).
				-->
				<NcButton
					variant="tertiary"
					:aria-label="
						t('filinq', 'Remove match rule {number}', {
							number: idx + 1,
						})
					"
					@click="removeRule(idx)">
					<template #icon>
						<Delete :size="20" />
					</template>
				</NcButton>
			</div>
			<NcButton variant="secondary" @click="addRule">
				{{ t('filinq', 'Add match rule') }}
			</NcButton>

			<div v-if="formError" class="form-error">
				{{ formError }}
			</div>
		</div>

		<template #actions>
			<NcButton variant="tertiary" @click="onCancel">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="saving || !canSubmit"
				@click="submit">
				<template v-if="saving" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ editing ? t('filinq', 'Save') : t('filinq', 'Create') }}
			</NcButton>
		</template>
	</NcDialog>
</template>
