<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Final documents by record state')"
		:description="
			t(
				'filinq',
				'Declare which states of a record make its documents final. When the app that owns the record reports one of these states, its documents get a frozen version.',
			)
		">
		<div class="finality-rule__fields">
			<NcTextField
				v-model="form.declaringApp"
				:label="t('filinq', 'App that owns the record')"
				:helperText="t('filinq', 'The app id, for example dossiq.')" />
			<NcTextField
				v-model="form.typeReference"
				:label="t('filinq', 'Record type')"
				:helperText="t('filinq', 'The record type as that app names it, such as a case type.')" />
			<NcTextField
				v-model="form.finalStates"
				:label="t('filinq', 'States that make a document final')"
				:helperText="t('filinq', 'Separate states with commas.')" />
			<NcTextField
				v-model="form.documentRole"
				:label="t('filinq', 'Document role')"
				:helperText="t('filinq', 'Leave empty to cover every document of the record.')" />
			<NcTextField
				v-model="form.reasonTemplate"
				:label="t('filinq', 'Reason on the frozen version')"
				:helperText="t('filinq', 'Leave empty to name the state change.')" />
		</div>

		<NcButton variant="primary" :disabled="saving || complete === false" @click="save">
			{{ t('filinq', 'Declare rule') }}
		</NcButton>

		<p v-if="stored" class="finality-rule__stored" role="status">
			{{
				t('filinq', 'Stored: {app} {type} becomes final in {states}.', {
					app: stored.declaringApp,
					type: stored.typeReference,
					states: (stored.finalStates || []).join(', '),
				})
			}}
		</p>
	</NcSettingsSection>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcSettingsSection, NcTextField } from '@nextcloud/vue'
import { declareFinalityRule } from '../../services/finalDocumentService.js'

/**
 * The states typed in the field, trimmed, without empty ones or repeats.
 *
 * @param {string} text The comma-separated states.
 * @return {Array<string>} The states.
 *
 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-3.2
 */
export function parseStates(text) {
	return [
		...new Set(
			String(text || '')
				.split(',')
				.map((state) => state.trim())
				.filter((state) => state !== ''),
		),
	]
}

// The caller of POST /api/document-finality-rules (row wk-final-rule,
// REQ-FDF-04): an admin declares for a consuming app which of its record
// states make that record's documents final. The server checks the
// final-document administrator right and stores or updates the rule.
export default {
	name: 'DocumentFinalityRuleSettings',
	components: {
		NcButton,
		NcSettingsSection,
		NcTextField,
	},

	data() {
		return {
			saving: false,
			stored: null,
			form: {
				declaringApp: '',
				typeReference: '',
				finalStates: '',
				documentRole: '',
				reasonTemplate: '',
			},
		}
	},

	computed: {
		/**
		 * Whether the form names an app, a record type and at least one state.
		 *
		 * @return {boolean}
		 *
		 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-3.2
		 */
		complete() {
			return (
				this.form.declaringApp.trim() !== ''
				&& this.form.typeReference.trim() !== ''
				&& parseStates(this.form.finalStates).length > 0
			)
		},
	},

	methods: {
		t,

		/**
		 * Declare the rule; the server's own message is shown when it refuses.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/finish-half-built-capabilities/tasks.md#task-3.2
		 */
		async save() {
			this.saving = true
			try {
				const data = await declareFinalityRule({
					declaringApp: this.form.declaringApp.trim(),
					typeReference: this.form.typeReference.trim(),
					finalStates: parseStates(this.form.finalStates),
					documentRole: this.form.documentRole.trim(),
					reasonTemplate: this.form.reasonTemplate.trim(),
				})
				this.stored = data.rule || null
				showSuccess(t('filinq', 'Finality rule stored'))
			} catch (error) {
				showError(
					error?.response?.data?.error
						|| t('filinq', 'Could not store the declaration'),
				)
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.finality-rule__fields {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-bottom: calc(var(--default-grid-baseline) * 4);
	max-width: 640px;
}

.finality-rule__stored {
	margin-top: calc(var(--default-grid-baseline) * 2);
	color: var(--color-text-maxcontrast);
}
</style>
