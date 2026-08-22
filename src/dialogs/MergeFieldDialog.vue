<template>
	<NcDialog :name="t('filinq', 'Insert merge field')" @closing="$emit('close')">
		<template #default>
			<NcTextField
				v-model="fieldName"
				:label="t('filinq', 'Field name')"
				:placeholder="t('filinq', 'e.g. name, address, date')" />
			<p class="merge-field-dialog__hint">
				{{ hintText }}
			</p>
		</template>
		<template #actions>
			<NcButton @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" :disabled="!fieldName" @click="confirm">
				{{ t('filinq', 'Insert') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog, NcTextField } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'MergeFieldDialog',
	components: { NcButton, NcDialog, NcTextField },
	emits: ['close', 'insert'],
	data() {
		return { fieldName: '' }
	},

	computed: {
		/**
		 * Hint text showing the merge-field placeholder that will be inserted.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		hintText() {
			const name = this.fieldName || 'field'
			return t('filinq', 'This inserts {placeholder} into the template.', {
				placeholder: '{{ ' + name + ' }}',
			})
		},
	},

	methods: {
		t,
		/**
		 * Emit the chosen merge-field name to the parent editor.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		confirm() {
			if (!this.fieldName) return
			this.$emit('insert', this.fieldName)
			this.fieldName = ''
		},
	},
}
</script>

<style scoped>
.merge-field-dialog__hint {
	font-size: 13px;
	color: var(--color-text-lighter);
	font-family: monospace;
	background: var(--color-background-dark);
	padding: 8px;
	border-radius: 4px;
	margin-top: 8px;
}
</style>
