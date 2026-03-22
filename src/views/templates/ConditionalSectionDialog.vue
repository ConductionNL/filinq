<template>
	<div class="conditional-dialog">
		<div class="conditional-dialog__backdrop" @click="$emit('close')" />
		<div class="conditional-dialog__panel">
			<h3>{{ t('docudesk', 'Conditional section') }}</h3>
			<p class="conditional-dialog__description">
				{{ t('docudesk', 'Define when the selected content should be shown.') }}
			</p>

			<div class="conditional-dialog__form">
				<NcTextField :value.sync="field"
					:label="t('docudesk', 'Field name')"
					:placeholder="t('docudesk', 'e.g. zaaktype')" />

				<NcSelect v-model="selectedOperator"
					:options="operatorOptions"
					:label="t('docudesk', 'Condition')" />

				<NcTextField v-if="showValueField"
					:value.sync="value"
					:label="t('docudesk', 'Value')"
					:placeholder="t('docudesk', 'e.g. omgevingsvergunning')" />
			</div>

			<div class="conditional-dialog__preview">
				<em>{{ previewText }}</em>
			</div>

			<div class="conditional-dialog__actions">
				<NcButton type="primary"
					:disabled="!isValid"
					@click="onApply">
					{{ t('docudesk', 'Apply condition') }}
				</NcButton>
				<NcButton type="tertiary" @click="$emit('close')">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcSelect, NcTextField } from '@nextcloud/vue'

export default {
	name: 'ConditionalSectionDialog',
	components: {
		NcButton,
		NcSelect,
		NcTextField,
	},
	data() {
		return {
			field: '',
			value: '',
			selectedOperator: null,
			operatorOptions: [
				{ id: 'equals', label: t('docudesk', 'equals') },
				{ id: 'not_equals', label: t('docudesk', 'does not equal') },
				{ id: 'contains', label: t('docudesk', 'contains') },
				{ id: 'is_empty', label: t('docudesk', 'is empty') },
				{ id: 'is_not_empty', label: t('docudesk', 'is not empty') },
			],
		}
	},
	computed: {
		showValueField() {
			const op = this.selectedOperator?.id
			return op && op !== 'is_empty' && op !== 'is_not_empty'
		},
		isValid() {
			if (!this.field || !this.selectedOperator) return false
			if (this.showValueField && !this.value) return false
			return true
		},
		previewText() {
			if (!this.field || !this.selectedOperator) {
				return t('docudesk', 'Select a field and condition')
			}
			const op = this.selectedOperator.label
			if (this.showValueField) {
				return t('docudesk', 'Show when {field} {op} "{value}"', {
					field: this.field,
					op,
					value: this.value || '...',
				})
			}
			return t('docudesk', 'Show when {field} {op}', {
				field: this.field,
				op,
			})
		},
	},
	methods: {
		t,
		onApply() {
			this.$emit('apply', {
				field: this.field,
				operator: this.selectedOperator.id,
				value: this.value,
			})
		},
	},
}
</script>

<style scoped lang="scss">
.conditional-dialog {
	position: fixed;
	inset: 0;
	z-index: 1000;
	display: flex;
	align-items: center;
	justify-content: center;

	&__backdrop {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.3);
	}

	&__panel {
		position: relative;
		background: var(--color-main-background);
		border-radius: var(--border-radius-large);
		padding: 20px;
		max-width: 450px;
		width: 100%;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);

		h3 {
			margin: 0 0 8px;
		}
	}

	&__description {
		color: var(--color-text-maxcontrast);
		margin-bottom: 16px;
	}

	&__form {
		display: flex;
		flex-direction: column;
		gap: 12px;
	}

	&__preview {
		margin: 16px 0;
		padding: 8px 12px;
		background: var(--color-background-dark);
		border-radius: var(--border-radius);
	}

	&__actions {
		display: flex;
		gap: 8px;
		justify-content: flex-end;
	}
}
</style>
