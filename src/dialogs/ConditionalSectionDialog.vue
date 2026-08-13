<template>
	<NcDialog
		:name="t('docudesk', 'Insert conditional section')"
		@closing="$emit('close')">
		<template #default>
			<NcTextField
				v-model="condField"
				:label="t('docudesk', 'Field name')"
				:placeholder="t('docudesk', 'e.g. zaaktype')" />
			<NcSelect
				v-model="condOp"
				:options="opOptions"
				:input-label="t('docudesk', 'Operator')" />
			<NcTextField
				v-if="needsValue"
				v-model="condValue"
				:label="t('docudesk', 'Value')"
				:placeholder="t('docudesk', 'e.g. omgevingsvergunning')" />
			<p class="conditional-dialog__hint">
				{{ preview }}
			</p>
		</template>
		<template #actions>
			<NcButton @click="$emit('close')">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" @click="confirm">
				{{ t('docudesk', 'Insert') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcSelect, NcTextField } from '@conduction/nextcloud-vue'

export default {
	name: 'ConditionalSectionDialog',
	components: { NcButton, NcDialog, NcSelect, NcTextField },
	emits: ['close', 'insert'],
	data() {
		return {
			condField: '',
			condOp: { label: 'is not empty', value: 'is_not_empty' },
			condValue: '',
		}
	},
	computed: {
		/**
		 * Conditional operator options for the dialog dropdown.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		opOptions() {
			return [
				{ label: t('docudesk', 'equals'), value: 'equals' },
				{ label: t('docudesk', 'not equals'), value: 'not_equals' },
				{ label: t('docudesk', 'contains'), value: 'contains' },
				{ label: t('docudesk', 'is empty'), value: 'is_empty' },
				{ label: t('docudesk', 'is not empty'), value: 'is_not_empty' },
			]
		},
		/**
		 * Whether the selected operator requires a comparison value.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		needsValue() {
			const op = this.condOp?.value || this.condOp
			return op !== 'is_empty' && op !== 'is_not_empty'
		},
		/**
		 * Live Twig-syntax preview of the conditional section.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		preview() {
			const field = this.condField || 'field'
			const op = this.condOp?.value || this.condOp || 'is_not_empty'
			const val = this.condValue
			const labels = {
				equals: `== "${val}"`,
				not_equals: `!= "${val}"`,
				contains: `contains "${val}"`,
				is_empty: 'is empty',
				is_not_empty: 'is not empty',
			}
			return `{% if ${field} ${labels[op] || op} %}…{% endif %}`
		},
	},
	methods: {
		t,
		/**
		 * Emit the configured conditional section to the parent editor.
		 *
		 * @spec openspec/changes/advanced-template-management/tasks.md#task-7
		 */
		confirm() {
			const field = this.condField || 'field'
			const op = this.condOp?.value || this.condOp || 'is_not_empty'
			this.$emit('insert', { field, op, value: this.condValue })
			this.condField = ''
			this.condValue = ''
		},
	},
}
</script>

<style scoped>
.conditional-dialog__hint {
	font-size: 13px;
	color: var(--color-text-lighter);
	font-family: monospace;
	background: var(--color-background-dark);
	padding: 8px;
	border-radius: 4px;
	margin-top: 8px;
}
</style>
