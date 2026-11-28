<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md#requirement-bulk-import-migrates-existing-template-estates-req-ddota-005
-->

<template>
	<div class="field-map-editor">
		<p v-if="rows.length === 0" class="field-map-editor__empty">
			{{ t('filinq', 'Every merge tag is known; nothing to map.') }}
		</p>
		<template v-else>
			<p class="field-map-editor__help">
				{{ t('filinq', 'For each unknown merge tag, enter the field it should be filled from, such as applicant.name. Leave it empty to fill it from data of the same name.') }}
			</p>
			<table class="field-map-editor__table">
				<thead>
					<tr>
						<th scope="col">
							{{ t('filinq', 'Merge tag') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Filled from') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="row in rows" :key="row.tag">
						<td><code>{{ '${' + row.tag + '}' }}</code></td>
						<td>
							<NcTextField
								v-model="row.path"
								:label="t('filinq', 'Field for {tag}', { tag: row.tag })"
								:disabled="readOnly"
								placeholder="aanvrager.naam" />
						</td>
					</tr>
				</tbody>
			</table>
			<NcNoteCard v-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<NcButton
				variant="primary"
				:disabled="readOnly || saving"
				@click="save">
				{{ t('filinq', 'Save field mapping') }}
			</NcButton>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { fieldMapFrom, saveFieldMap, unknownTags } from '../../services/officeTemplates.js'

export default {
	name: 'FieldMapEditor',
	components: { NcButton, NcNoteCard, NcTextField },
	props: {
		template: { type: Object, required: true },
		readOnly: { type: Boolean, default: false },
	},

	emits: ['saved'],
	data() {
		return {
			rows: unknownTags(this.template),
			saving: false,
			error: '',
		}
	},

	watch: {
		/**
		 * template.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		template(next) {
			this.rows = unknownTags(next)
		},
	},

	methods: {
		t,
		/**
		 * save.
		 *
		 * @spec openspec/changes/archive/2026-11-25-office-template-authoring/specs/office-template-authoring/spec.md
		 */
		async save() {
			this.saving = true
			this.error = ''
			const result = await saveFieldMap(this.template.id, fieldMapFrom(this.rows, this.template.fieldMap))
			this.saving = false
			if (!result.ok) {
				this.error = result.error || t('filinq', 'The field mapping could not be saved.')
				return
			}
			this.$emit('saved', result.data)
		},
	},
}
</script>

<style scoped>
.field-map-editor__table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 12px;
}

.field-map-editor__table th,
.field-map-editor__table td {
	text-align: start;
	padding: 4px 8px;
	border-bottom: 1px solid var(--color-border);
}

.field-map-editor__help,
.field-map-editor__empty {
	color: var(--color-text-maxcontrast);
}
</style>
