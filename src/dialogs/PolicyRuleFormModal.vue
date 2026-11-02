<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import { matchRuleForm } from '../mixins/matchRuleForm.js'

/**
 * What differs between the two policy surfaces' forms. Texts and fields are
 * functions so each `t()` call stays literal for the translation extractor.
 * A field with `options` names the data list it picks from.
 *
 * @type {Record<string, object>}
 */
const FORMS = {
	prohibition: {
		formClass: 'prohibition-form',
		blankForm: () => ({
			primaryName: '',
			entityType: 'PERSON',
			reason: '',
			legalAuthority: '',
			caseReference: '',
			severity: 'medium', // matches the publicationProhibition.severity default in filinq_register.json
			jurisdiction: '',
			validUntil: '',
			active: true,
			matchRules: [],
		}),
		// Canonical set per the publicationProhibition schema's severity enum
		// in filinq_register.json. Keep in lock-step with the register;
		// widening here without widening the schema would silently fail the
		// OR write-time validation.
		choices: { severityOptions: ['high', 'medium', 'low'] },
		isComplete: (form) =>
			form.primaryName.trim() !== '' && form.reason.trim() !== '',
		texts: () => ({
			add: t('filinq', 'Add publish-never rule'),
			edit: t('filinq', 'Edit publish-never rule'),
			noRules: t(
				'filinq',
				'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches — names alone produce false positives.',
			),
			onlyNames: t(
				'filinq',
				'Warning: only name-based rules are present. Names alone often produce false positives — consider adding a BSN or KvK match.',
			),
		}),
		fields: () => [
			{
				key: 'primaryName',
				label: t('filinq', 'Primary name (Dutch)'),
				required: true,
			},
			{
				key: 'entityType',
				label: t('filinq', 'Entity type'),
				options: 'entityTypeOptions',
				required: true,
			},
			{
				key: 'reason',
				label: t('filinq', 'Reason (markdown allowed)'),
				required: true,
			},
			{
				key: 'legalAuthority',
				label: t('filinq', 'Legal authority (court order, statute, …)'),
			},
			{
				key: 'caseReference',
				label: t('filinq', 'Case reference (optional)'),
			},
			{
				key: 'severity',
				label: t('filinq', 'Severity'),
				options: 'severityOptions',
			},
			{ key: 'jurisdiction', label: t('filinq', 'Jurisdiction (optional)') },
			{
				key: 'validUntil',
				label: t('filinq', 'Valid until (ISO 8601, optional)'),
			},
		],
	},
	standingConsent: {
		formClass: 'standing-consent-form',
		blankForm: () => ({
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
		}),
		choices: {
			consentMethodOptions: [
				'paper',
				'digital_signature',
				'verbal_recorded',
				'opt_in_form',
			],
		},
		isComplete: (form) =>
			form.entityText.trim() !== '' && form.consentMethod !== '',
		texts: () => ({
			add: t('filinq', 'Add standing consent'),
			edit: t('filinq', 'Edit standing consent'),
			noExpiry: t(
				'filinq',
				'No expiry set — this standing consent will remain in force indefinitely. Consider setting a "Valid until" date.',
			),
			noRules: t(
				'filinq',
				'Add at least one match rule. Prefer stable identifiers (BSN/KvK) over name-only matches.',
			),
		}),
		fields: () => [
			{
				key: 'entityText',
				label: t('filinq', 'Entity text (display name)'),
				required: true,
			},
			{
				key: 'entityType',
				label: t('filinq', 'Entity type'),
				options: 'entityTypeOptions',
				required: true,
			},
			{
				key: 'consentMethod',
				label: t('filinq', 'Consent method'),
				options: 'consentMethodOptions',
				required: true,
			},
			{
				key: 'consentDocument',
				label: t('filinq', 'Consent document (file id or URL)'),
			},
			{
				key: 'consentScope',
				label: t(
					'filinq',
					"Consent scope (e.g. '2024-2025 municipal decisions')",
				),
			},
			{ key: 'legalBasis', label: t('filinq', 'Legal basis') },
			{
				key: 'validFrom',
				label: t('filinq', 'Valid from (ISO 8601, optional)'),
			},
			{
				key: 'validUntil',
				label: t('filinq', 'Valid until (ISO 8601, optional)'),
			},
		],
	},
}

export default {
	name: 'PolicyRuleFormModal',
	components: {
		CnDialogActions,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcSelect,
		NcTextField,
		Delete,
	},

	mixins: [matchRuleForm],

	props: {
		/** The policy surface this form edits: `prohibition` or `standingConsent`. */
		surface: {
			type: String,
			required: true,
			validator: (value) => value in FORMS,
		},
	},

	emits: ['update:open', 'submit', 'cancel'],

	data() {
		return {
			entityTypeOptions: ['PERSON', 'ORGANIZATION', 'OTHER'],
			...FORMS[this.surface].choices,
		}
	},

	computed: {
		/**
		 * The surface's form settings.
		 *
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		config() {
			return FORMS[this.surface]
		},

		/**
		 * The surface's texts.
		 *
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		texts() {
			return this.config.texts()
		},

		/**
		 * The surface's fields, in order, with their option lists.
		 *
		 * @return {Array<object>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		fields() {
			return this.config.fields().map((field) => ({
				...field,
				options: field.options ? this.$data[field.options] : null,
			}))
		},

		/**
		 * Modal heading: "Edit" when an existing rule was handed in, "Add" for
		 * a create.
		 *
		 * @return {string}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		dialogTitle() {
			return this.editing ? this.texts.edit : this.texts.add
		},

		/**
		 * Whether the form holds enough to save.
		 *
		 * @return {boolean}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		canSubmit() {
			return this.config.isComplete(this.form) && this.matchRulesComplete
		},

		/**
		 * Whether every match rule matches on a name only, on a surface that
		 * warns about it.
		 *
		 * @return {boolean}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		onlyNameRules() {
			if (!this.texts.onlyNames || this.form.matchRules.length === 0) {
				return false
			}
			return this.form.matchRules.every(
				(r) => r.type === 'exact' || r.type === 'normalized',
			)
		},

		/**
		 * The notes below the match rules: the name-only warning and the
		 * save error, each when it applies.
		 *
		 * @return {Array<{className: string, text: string}>}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		closingNotes() {
			const notes = []
			if (this.onlyNameRules) {
				notes.push({ className: 'form-warning', text: this.texts.onlyNames })
			}
			if (this.formError) {
				notes.push({ className: 'form-error', text: this.formError })
			}
			return notes
		},
	},

	methods: {
		t,

		/**
		 * A fresh empty form for the surface.
		 *
		 * @return {object}
		 * @spec openspec/specs/entity-publication-policies/spec.md#requirement-three-separate-admin-surfaces-must-exist
		 */
		blankForm() {
			return FORMS[this.surface].blankForm()
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
		<div :class="config.formClass">
			<template v-for="field in fields" :key="field.key">
				<NcSelect
					v-if="field.options"
					v-model="form[field.key]"
					:options="field.options"
					:inputLabel="field.label"
					:label="field.label"
					:required="field.required" />
				<NcTextField
					v-else
					v-model="form[field.key]"
					:label="field.label"
					:required="field.required" />
			</template>
			<NcCheckboxRadioSwitch v-model="form.active" type="switch">
				{{ t('filinq', 'Active') }}
			</NcCheckboxRadioSwitch>

			<div v-if="texts.noExpiry && !form.validUntil" class="form-warning">
				{{ texts.noExpiry }}
			</div>

			<h4>{{ t('filinq', 'Match rules') }}</h4>
			<div v-if="!form.matchRules?.length" class="form-warning">
				{{ texts.noRules }}
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

			<div
				v-for="note in closingNotes"
				:key="note.className"
				:class="note.className">
				{{ note.text }}
			</div>
		</div>

		<template #actions>
			<CnDialogActions
				v-bind="formActions"
				@cancel="onCancel"
				@submit="submit" />
		</template>
	</NcDialog>
</template>
