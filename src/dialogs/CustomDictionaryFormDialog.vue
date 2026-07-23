<!--
	SPDX-License-Identifier: EUPL-1.2
	SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

	Create/edit form for a customDictionary record (label, description,
	colour, match mode, active). Used by CustomDictionaryIndex (create) and
	CustomDictionaryDetail (edit) — ADR-004 gate-13, own file, not inline.
-->
<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcSelect,
	NcTextField,
} from '@conduction/nextcloud-vue'

const blankForm = () => ({
	label: '',
	description: '',
	colour: '#0082C9',
	matchMode: 'caseInsensitive',
	active: true,
})

export default {
	name: 'CustomDictionaryFormDialog',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcSelect,
		NcTextField,
	},
	props: {
		editingRecord: { type: Object, default: null },
		saving: { type: Boolean, default: false },
		formError: { type: String, default: '' },
	},
	emits: ['submit', 'cancel'],
	data() {
		return {
			form: blankForm(),
			// Mirrors CustomDictionaryMatchService::VALID_MATCH_MODES. Labelled
			// {value, label} objects (not raw strings) so the select shows a
			// translated label while `form.matchMode` stays the plain schema
			// value (same {value, label} + reduce convention as
			// EntityReviewTable's grondslagen NcSelect).
			matchModeOptions: [
				{ value: 'exact', label: t('docudesk', 'Exact (case-sensitive)') },
				{ value: 'caseInsensitive', label: t('docudesk', 'Case-insensitive') },
				{ value: 'wordBoundary', label: t('docudesk', 'Word boundary') },
			],
		}
	},
	computed: {
		editing() {
			return this.editingRecord !== null
		},
		dialogTitle() {
			return this.editing
				? t('docudesk', 'Edit dictionary')
				: t('docudesk', 'Add custom dictionary')
		},
		canSubmit() {
			return this.form.label.trim() !== ''
		},
	},
	created() {
		this.resetForm()
	},
	methods: {
		t,
		resetForm() {
			this.form = this.editingRecord
				? { ...blankForm(), ...this.editingRecord }
				: blankForm()
		},
		submit() {
			if (!this.canSubmit) return
			this.$emit('submit', { ...this.form })
		},
		onCancel() {
			this.$emit('cancel')
		},
	},
}
</script>

<template>
	<NcDialog
		:name="dialogTitle"
		:open="true"
		size="normal"
		@update:open="onCancel">
		<div class="custom-dictionary-form">
			<NcTextField
				:value.sync="form.label"
				:label="t('docudesk', 'Label')"
				required />
			<NcTextField
				:value.sync="form.description"
				:label="t('docudesk', 'Description (optional)')" />
			<div class="custom-dictionary-form__colour-row">
				<label class="custom-dictionary-form__colour-label" for="custom-dictionary-colour">
					{{ t('docudesk', 'Colour') }}
				</label>
				<input
					id="custom-dictionary-colour"
					v-model="form.colour"
					type="color"
					class="custom-dictionary-form__colour-input">
				<NcTextField
					:value.sync="form.colour"
					:label="t('docudesk', 'Hex value')"
					class="custom-dictionary-form__colour-hex" />
			</div>
			<NcSelect
				:value="form.matchMode"
				:options="matchModeOptions"
				label="label"
				:reduce="(o) => o.value"
				:clearable="false"
				:input-label="t('docudesk', 'Match mode')"
				required
				@input="form.matchMode = $event" />
			<p class="custom-dictionary-form__hint">
				{{ t('docudesk', 'Exact matches case-sensitively; case-insensitive (default) folds case; word boundary is case-insensitive but never matches inside a longer word.') }}
			</p>
			<NcCheckboxRadioSwitch
				v-model="form.active"
				type="switch">
				{{ t('docudesk', 'Active (used in automatic detection)') }}
			</NcCheckboxRadioSwitch>

			<div v-if="formError" class="custom-dictionary-form__error">
				{{ formError }}
			</div>
		</div>

		<template #actions>
			<NcButton type="tertiary" @click="onCancel">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" :disabled="saving || !canSubmit" @click="submit">
				<template v-if="saving" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ editing ? t('docudesk', 'Save') : t('docudesk', 'Create') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style scoped>
.custom-dictionary-form {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 8px;
}

.custom-dictionary-form__colour-row {
	display: flex;
	align-items: flex-end;
	gap: 8px;
}

.custom-dictionary-form__colour-label {
	font-size: 13px;
	font-weight: bold;
	color: var(--color-text-maxcontrast);
	margin-bottom: 4px;
}

.custom-dictionary-form__colour-input {
	width: 44px;
	height: 34px;
	padding: 2px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	cursor: pointer;
}

.custom-dictionary-form__colour-hex {
	flex: 1;
}

.custom-dictionary-form__hint {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.custom-dictionary-form__error {
	background: var(--color-error, #ffd1d1);
	color: var(--color-text-maxcontrast, #333);
	padding: 8px 12px;
	border-radius: var(--border-radius);
	font-size: 13px;
}
</style>
