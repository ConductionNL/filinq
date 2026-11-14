<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcDialog
		v-if="open"
		:name="t('filinq', 'Add as entity')"
		:open="open"
		size="normal"
		@update:open="$emit('update:open', $event)">
		<div class="add-manual-entity" data-testid="add-manual-entity">
			<NcTextField
				:modelValue="value"
				:label="t('filinq', 'Selected text')"
				:required="true"
				@update:modelValue="value = $event" />
			<NcSelect
				:modelValue="type"
				:options="typeOptions"
				label="label"
				:reduce="(o) => o.value"
				:inputLabel="t('filinq', 'Entity type')"
				:clearable="false"
				@update:modelValue="onTypeChange" />
			<NcSelect
				:modelValue="bases"
				:options="basesOptions"
				label="label"
				:reduce="(o) => o.value"
				:multiple="true"
				:inputLabel="t('filinq', 'Legal bases')"
				:placeholder="t('filinq', 'Pick grondslagen…')"
				@update:modelValue="onBasesChange" />
			<p v-if="basesProposed" class="add-manual-entity__proposed">
				{{ t('filinq', 'Proposed for this entity type; change it if another ground applies.') }}
			</p>
			<p v-if="notice" class="add-manual-entity__notice" role="status">
				{{ notice }}
			</p>
			<p v-if="error" class="add-manual-entity__error" role="alert">
				{{ error }}
			</p>
		</div>
		<template #actions>
			<NcButton variant="tertiary" @click="$emit('update:open', false)">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!canSubmit || saving"
				@click="submit">
				{{ t('filinq', 'Add as entity') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
/**
 * Pre-filled form for a manual entity created from a text selection in the
 * review workbench. Submits value, type and grondslagen only: OpenRegister's
 * matcher finds every occurrence itself, so no offsets leave the client.
 */
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcSelect, NcTextField } from '@nextcloud/vue'
import { manualEntityPayload, proposedBasesFor } from '../services/documentReview.js'
import { ENTITY_TYPES, entityTypeLabel } from '../services/entityTypes.js'

export default {
	name: 'AddManualEntityModal',

	components: { NcButton, NcDialog, NcSelect, NcTextField },

	props: {
		open: { type: Boolean, default: false },
		/** The text the reviewer selected. */
		selectedText: { type: String, default: '' },
		/** `{ label, value }` grondslag options. */
		basesOptions: { type: Array, default: () => [] },
		/** The admin's entity type → grondslag proposal mapping. */
		proposalMapping: { type: Object, default: () => ({}) },
		/**
		 * Saves the entity; resolves to the number of occurrences found.
		 * Throws on failure.
		 */
		save: { type: Function, required: true },
	},

	emits: ['update:open', 'added'],

	data() {
		return {
			value: this.selectedText,
			type: 'PERSON',
			bases: proposedBasesFor(this.proposalMapping, 'PERSON'),
			basesTouched: false,
			saving: false,
			notice: '',
			error: '',
		}
	},

	computed: {
		/**
		 * The entity types to pick from.
		 *
		 * @return {Array<{label: string, value: string}>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		typeOptions() {
			return ENTITY_TYPES.map((value) => ({ value, label: entityTypeLabel(value) }))
		},

		/**
		 * Whether the bases shown are the type's proposal, untouched.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		basesProposed() {
			return !this.basesTouched && this.bases.length > 0
		},

		/**
		 * Whether there is enough to save.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		canSubmit() {
			return this.value.trim() !== '' && this.type !== ''
		},
	},

	watch: {
		/**
		 * A new selection replaces the value and clears the last outcome.
		 *
		 * @param {string} text The selected text.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		selectedText(text) {
			this.value = text
			this.notice = ''
			this.error = ''
		},
	},

	methods: {
		t,

		/**
		 * A new type re-proposes its grondslagen unless the reviewer chose some.
		 *
		 * @param {string} type The chosen type.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		onTypeChange(type) {
			this.type = type || ''
			if (!this.basesTouched) {
				this.bases = proposedBasesFor(this.proposalMapping, this.type)
			}
		},

		/**
		 * The reviewer's own choice of grondslagen always wins.
		 *
		 * @param {Array<string>} bases The chosen grondslagen.
		 * @return {void}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		onBasesChange(bases) {
			this.bases = Array.isArray(bases) ? bases : []
			this.basesTouched = true
		},

		/**
		 * Save, then close; a selection found nowhere stays open with a notice.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-3.2
		 */
		async submit() {
			if (!this.canSubmit || this.saving) {
				return
			}
			this.saving = true
			this.notice = ''
			this.error = ''
			try {
				const found = await this.save(manualEntityPayload(this.value, this.type, this.bases))
				if (found === 0) {
					this.notice = t('filinq', 'This text was not found in the document, so nothing was added.')
					return
				}
				this.$emit('added', found)
				this.$emit('update:open', false)
			} catch (err) {
				this.error = err?.message || t('filinq', 'Failed to add the selected text')
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style scoped>
.add-manual-entity {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.add-manual-entity__proposed,
.add-manual-entity__notice {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.add-manual-entity__error {
	margin: 0;
	color: var(--color-error-text);
}
</style>
