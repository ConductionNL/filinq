<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcDialog
		:name="t('filinq', 'Redact pages or the whole document')"
		:noClose="busy"
		@closing="$emit('close')">
		<template #default>
			<div class="scope-redaction-dialog" data-testid="scope-redaction-dialog">
				<fieldset class="scope-redaction-dialog__scopes">
					<legend>{{ t('filinq', 'What to redact') }}</legend>
					<NcCheckboxRadioSwitch
						v-model="scope"
						type="radio"
						value="page"
						name="scope"
						data-testid="scope-page">
						{{ t('filinq', 'One page') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						v-model="scope"
						type="radio"
						value="pageRange"
						name="scope"
						data-testid="scope-page-range">
						{{ t('filinq', 'A range of pages') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						v-model="scope"
						type="radio"
						value="document"
						name="scope"
						data-testid="scope-document">
						{{ t('filinq', 'The whole document') }}
					</NcCheckboxRadioSwitch>
				</fieldset>

				<div v-if="scope !== 'document'" class="scope-redaction-dialog__pages">
					<NcTextField
						v-model="fromPage"
						type="number"
						min="1"
						:max="pageCount || undefined"
						:label="scope === 'page' ? t('filinq', 'Page') : t('filinq', 'From page')"
						data-testid="scope-from-page" />
					<NcTextField
						v-if="scope === 'pageRange'"
						v-model="toPage"
						type="number"
						min="1"
						:max="pageCount || undefined"
						:label="t('filinq', 'To page')"
						data-testid="scope-to-page" />
				</div>
				<p v-if="scope !== 'document' && pageCount > 0" class="scope-redaction-dialog__hint">
					{{ n('filinq', 'This document has %n page.', 'This document has %n pages.', pageCount) }}
				</p>

				<NcSelect
					v-model="bases"
					data-testid="scope-bases"
					multiple
					:inputLabel="t('filinq', 'Legal bases')"
					:options="basesOptions"
					:reduce="(o) => o.value"
					label="label" />

				<NcNoteCard v-if="scope === 'document'" type="info">
					{{
						t(
							'filinq',
							'A document withheld in full gets no redacted copy, and it is not offered for publication.',
						)
					}}
				</NcNoteCard>
				<NcNoteCard v-else-if="!pageScopesAvailable" type="warning">
					{{
						t(
							'filinq',
							"Page redaction needs OpenRegister's image seam, which this instance does not have. You can still withhold the whole document.",
						)
					}}
				</NcNoteCard>
				<NcNoteCard v-if="error" type="error" data-testid="scope-error">
					{{ error }}
				</NcNoteCard>
			</div>
		</template>
		<template #actions>
			<NcButton :disabled="busy" @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="busy || !canSave"
				data-testid="scope-save"
				@click="onSave">
				<template v-if="busy" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ scope === 'document' ? t('filinq', 'Withhold document') : t('filinq', 'Redact pages') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { NcButton, NcCheckboxRadioSwitch, NcDialog, NcLoadingIcon, NcNoteCard, NcSelect, NcTextField } from '@nextcloud/vue'
import { decideScope } from '../services/imageRedaction.js'

/**
 * Redact one page, a range of pages or the whole document in one action.
 *
 * One save stores one decision with its grounds and commits it: the pages
 * are burned by OpenRegister's image seam, a whole document is withheld with
 * no output. The server validates; the dialog shows its refusal as is.
 * Lives in src/dialogs/ per ADR-004.
 *
 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
 */
export default {
	name: 'ScopeRedactionDialog',
	components: { NcButton, NcCheckboxRadioSwitch, NcDialog, NcLoadingIcon, NcNoteCard, NcSelect, NcTextField },

	props: {
		/** The document. */
		fileId: {
			type: [Number, String],
			required: true,
		},

		/** The document's page count, 0 when not known. */
		pageCount: {
			type: Number,
			default: 0,
		},

		/** Whether OpenRegister's image seam can burn pages here. */
		pageScopesAvailable: {
			type: Boolean,
			default: false,
		},

		/** The grounds to pick from, as the entity review offers them. */
		basesOptions: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['close', 'decided'],
	data() {
		return { scope: 'pageRange', fromPage: '1', toPage: '1', bases: [], error: '', busy: false }
	},

	computed: {
		/**
		 * Whether the decision is complete enough to send.
		 *
		 * @return {boolean} True with at least one ground and, for pages, a start.
		 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
		 */
		canSave() {
			if (this.bases.length === 0) {
				return false
			}
			return this.scope === 'document' || String(this.fromPage).trim() !== ''
		},
	},

	methods: {
		n,
		t,
		/**
		 * Store and commit the decision.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/image-redaction/tasks.md#task-6.3
		 */
		async onSave() {
			this.error = ''
			this.busy = true
			const decision = { scope: this.scope, fromPage: this.fromPage, toPage: this.toPage, bases: this.bases }
			const answer = await decideScope(this.fileId, decision, true)
			this.busy = false
			if (answer.ok) {
				this.$emit('decided', answer)
			} else {
				this.error = answer.error
			}
		},
	},
}
</script>

<style scoped>
.scope-redaction-dialog {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}

.scope-redaction-dialog__scopes {
	border: none;
	padding: 0;
	margin: 0;
}

.scope-redaction-dialog__pages {
	display: flex;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}

.scope-redaction-dialog__hint {
	color: var(--color-text-maxcontrast);
	margin: 0;
}
</style>
