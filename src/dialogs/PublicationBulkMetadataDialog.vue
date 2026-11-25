<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Sets one Woo metadata value on a selection of publications: the
information category from the TOOI list, the document type or the
publisher. Records already handed off are skipped by the server.

@spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
-->

<template>
	<NcDialog :name="t('filinq', 'Set metadata')" @closing="$emit('cancel')">
		<template #default>
			<div class="bulk-metadata">
				<label for="bulk-metadata-field">{{ t('filinq', 'Field') }}</label>
				<select
					id="bulk-metadata-field"
					v-model="field"
					data-testid="bulk-metadata-field"
					@change="value = ''">
					<option v-for="option in fields" :key="option.key" :value="option.key">
						{{ option.label }}
					</option>
				</select>
				<template v-if="field === 'wooCategory'">
					<label for="bulk-metadata-category">{{ t('filinq', 'Information category') }}</label>
					<select
						id="bulk-metadata-category"
						v-model="value"
						data-testid="bulk-metadata-category">
						<option value="">
							{{ t('filinq', 'Choose a category') }}
						</option>
						<option
							v-for="category in categories"
							:key="category.code"
							:value="category.code">
							{{ category.label }}
						</option>
					</select>
				</template>
				<NcTextField
					v-else
					v-model="value"
					:label="fieldLabel"
					data-testid="bulk-metadata-value" />
			</div>
		</template>
		<template #actions>
			<CnDialogActions
				:canSubmit="!busy && value.trim() !== ''"
				:confirmLabel="t('filinq', 'Set metadata')"
				@cancel="$emit('cancel')"
				@submit="$emit('confirm', values())" />
		</template>
	</NcDialog>
</template>

<script>
import { CnDialogActions } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcDialog, NcTextField } from '@nextcloud/vue'

export default {
	name: 'PublicationBulkMetadataDialog',
	components: { CnDialogActions, NcDialog, NcTextField },

	props: {
		// The Woo information categories, from OpenCatalogi's TOOI list.
		categories: { type: Array, default: () => [] },
		// True while the change is on its way, so it cannot be sent twice.
		busy: { type: Boolean, default: false },
	},

	emits: ['confirm', 'cancel'],

	data() {
		return { field: 'wooCategory', value: '' }
	},

	computed: {
		/**
		 * The fields a selection can be given one value for (the server's BULK_FIELDS).
		 *
		 * @return {Array<{key: string, label: string}>} The fields.
		 * @spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		fields() {
			return [
				{ key: 'wooCategory', label: t('filinq', 'Information category') },
				{ key: 'documentsoort', label: t('filinq', 'Document type') },
				{ key: 'publisher', label: t('filinq', 'Publisher (TOOI identifier)') },
			]
		},

		/**
		 * The label of the chosen field.
		 *
		 * @return {string} The label.
		 * @spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		fieldLabel() {
			return (this.fields.find((f) => f.key === this.field) || {}).label || t('filinq', 'Value')
		},
	},

	methods: {
		t,

		/**
		 * The values to set: the one chosen field.
		 *
		 * @return {object} `{ [field]: value }`.
		 * @spec openspec/changes/archive/2026-11-25-woo-metadata-worklist/specs/woo-metadata-worklist/spec.md#requirement-a-coordinator-fills-one-value-on-a-selection-req-wmw-002
		 */
		values() {
			return { [this.field]: this.value.trim() }
		},
	},
}
</script>

<style scoped>
.bulk-metadata {
	display: flex;
	flex-direction: column;
	gap: 8px;
}
</style>
