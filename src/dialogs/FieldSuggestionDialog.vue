<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The values a text model suggested for a record from one of its files, one
field at a time: the value the record holds now, the suggestion and the
passage it came from (or that it was not found in the text), and the
clerk's decision: accept, change or reject. Nothing is written until the
clerk has decided every field (extraction-model-filled-fields REQ-EMF-003).

@spec openspec/changes/extraction-model-filled-fields/specs/extraction-model-filled-fields/spec.md
-->

<template>
	<NcDialog
		:name="t('filinq', 'Fill fields from document')"
		size="normal"
		@closing="$emit('cancel')">
		<template #default>
			<p class="field-suggestion__intro">
				{{ intro }}
			</p>
			<NcNoteCard v-if="suggestion.truncated" type="info">
				{{ t('filinq', 'The document is long: the model read only its first part.') }}
			</NcNoteCard>
			<p v-if="fields.length === 0" data-testid="field-suggestion-none">
				{{ t('filinq', 'The model found no value for the declared fields.') }}
			</p>
			<fieldset
				v-for="field in fields"
				:key="field.name"
				class="field-suggestion__field"
				:data-testid="'field-suggestion-' + field.name">
				<legend>{{ field.name }}</legend>
				<p v-if="field.current !== ''" class="field-suggestion__current">
					{{ t('filinq', 'Now: {value}', { value: field.current }) }}
				</p>
				<p class="field-suggestion__value">
					{{ t('filinq', 'Suggested: {value}', { value: field.value }) }}
				</p>
				<p class="field-suggestion__source">
					{{ field.passage || t('filinq', 'Not found in the document text') }}
				</p>
				<div class="field-suggestion__choices">
					<NcCheckboxRadioSwitch
						v-for="choice in choices"
						:key="choice.value"
						type="radio"
						:name="'decision-' + field.name"
						:value="choice.value"
						:modelValue="decisions[field.name] || ''"
						@update:modelValue="choose(field.name, choice.value)">
						{{ choice.label }}
					</NcCheckboxRadioSwitch>
				</div>
				<NcTextField
					v-if="decisions[field.name] === 'change'"
					v-model="changed[field.name]"
					:label="t('filinq', 'Value for {field}', { field: field.name })" />
			</fieldset>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
		</template>
		<template #actions>
			<CnDialogActions
				:canSubmit="canSubmit"
				:saving="saving"
				:confirmLabel="t('filinq', 'Save decisions')"
				@cancel="$emit('cancel')"
				@submit="submit" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcCheckboxRadioSwitch, NcDialog, NcNoteCard, NcTextField } from '@nextcloud/vue'

export default {
	name: 'FieldSuggestionDialog',
	components: { CnDialogActions, NcCheckboxRadioSwitch, NcDialog, NcNoteCard, NcTextField },

	props: {
		// The stored suggestion: suggestions per field, currentValues, truncated, provider.
		suggestion: { type: Object, required: true },
		// Whether the parent is writing the decisions.
		saving: { type: Boolean, default: false },
		// Why the last write was refused.
		error: { type: String, default: '' },
	},

	emits: ['confirm', 'cancel'],

	data() {
		return { decisions: {}, changed: {} }
	},

	computed: {
		/**
		 * The suggested fields with what the record holds now.
		 *
		 * @return {Array<{name: string, value: string, current: string, passage: string}>}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/extraction-model-filled-fields/spec.md
		 */
		fields() {
			const current = this.suggestion.currentValues || {}
			return Object.entries(this.suggestion.suggestions || {}).map(([name, entry]) => ({
				name,
				value: shown(entry?.value),
				current: shown(current[name]),
				passage: entry?.foundInText ? String(entry.passage || '') : '',
			}))
		},

		choices() {
			return [
				{ value: 'accept', label: t('filinq', 'Accept') },
				{ value: 'change', label: t('filinq', 'Change') },
				{ value: 'reject', label: t('filinq', 'Reject') },
			]
		},

		intro() {
			const provider = this.suggestion.provider || t('filinq', 'the text model')
			return t('filinq', 'Read by {provider}. Decide each field: nothing is written to the record before you save.', { provider })
		},

		/**
		 * Every field decided, and a changed field has a value.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/extraction-model-filled-fields/spec.md
		 */
		canSubmit() {
			return this.fields.length > 0 && this.fields.every((field) => {
				const decision = this.decisions[field.name]
				if (decision === 'change') {
					return String(this.changed[field.name] ?? '').trim() !== ''
				}
				return decision === 'accept' || decision === 'reject'
			})
		},
	},

	methods: {
		t,

		/**
		 * Record a decision; a change starts from the suggested value.
		 *
		 * @param {string} name The field.
		 * @param {string} decision accept, change or reject.
		 * @return {void}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/extraction-model-filled-fields/spec.md
		 */
		choose(name, decision) {
			this.decisions = { ...this.decisions, [name]: decision }
			if (decision === 'change' && this.changed[name] === undefined) {
				const field = this.fields.find((f) => f.name === name)
				this.changed = { ...this.changed, [name]: field ? field.value : '' }
			}
		},

		/**
		 * Emit the decisions as the decide route reads them.
		 *
		 * @return {void}
		 * @spec openspec/changes/extraction-model-filled-fields/specs/extraction-model-filled-fields/spec.md
		 */
		submit() {
			if (!this.canSubmit) {
				return
			}
			const decisions = {}
			for (const field of this.fields) {
				const decision = this.decisions[field.name]
				decisions[field.name] = decision === 'change'
					? { decision, value: String(this.changed[field.name]).trim() }
					: { decision }
			}
			this.$emit('confirm', decisions)
		},
	},
}

/**
 * A value as the clerk reads it.
 *
 * @param {*} value The value.
 * @return {string}
 */
function shown(value) {
	if (value === null || value === undefined) {
		return ''
	}
	return typeof value === 'object' ? JSON.stringify(value) : String(value)
}
</script>

<style scoped>
.field-suggestion__field {
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 8px 12px;
	margin: 8px 0;
}

.field-suggestion__current,
.field-suggestion__source {
	color: var(--color-text-maxcontrast);
}

.field-suggestion__choices {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}
</style>
