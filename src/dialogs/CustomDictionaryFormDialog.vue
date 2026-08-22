<!--
	SPDX-License-Identifier: EUPL-1.2
	SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

	Create/edit form for a customDictionary record (label, description,
	colour, match mode, active). Used by CustomDictionaryIndex (create) and
	CustomDictionaryDetail (edit) — ADR-004 gate-13, own file, not inline.
-->
<script>
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcLoadingIcon,
	NcSelect,
	NcTextField,
} from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

/**
 *
 */
function blankForm() {
	return {
		label: '',
		description: '',
		colour: '#0082C9',
		matchMode: 'caseInsensitive',
		active: true,
	}
}

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
				{ value: 'exact', label: t('filinq', 'Exact (case-sensitive)') },
				{
					value: 'caseInsensitive',
					label: t('filinq', 'Case-insensitive'),
				},
				{ value: 'wordBoundary', label: t('filinq', 'Word boundary') },
			],
		}
	},

	computed: {
		editing() {
			return this.editingRecord !== null
		},

		dialogTitle() {
			return this.editing
				? t('filinq', 'Edit dictionary')
				: t('filinq', 'Add custom dictionary')
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
	<NcDialog :name="dialogTitle" :open="true" size="normal" @update:open="onCancel">
		<div class="custom-dictionary-form">
			<NcTextField
				v-model="form.label"
				:label="t('filinq', 'Label')"
				required />
			<NcTextField
				v-model="form.description"
				:label="t('filinq', 'Description (optional)')" />
			<div class="custom-dictionary-form__colour-row">
				<label
					class="custom-dictionary-form__colour-label"
					for="custom-dictionary-colour">
					{{ t('filinq', 'Colour') }}
				</label>
				<input
					id="custom-dictionary-colour"
					v-model="form.colour"
					type="color"
					class="custom-dictionary-form__colour-input" />
				<NcTextField
					v-model="form.colour"
					:label="t('filinq', 'Hex value')"
					class="custom-dictionary-form__colour-hex" />
			</div>
			<!--
				`v-model`, NOT `:value` + `@input`.

				This was a Vue 2 binding left behind by the Vue 3 migration, and
				it made the control INERT rather than merely mis-styled.
				`@nextcloud/vue` 9.9.0's NcSelect declares a `modelValue` prop,
				declares NO `value` prop at all, and its only declared emit is
				`update:modelValue` (verified in the installed
				dist/chunks/NcSelect-*.mjs). So `:value` bound nothing — the
				select rendered with no `.vs__selected` at all — and `@input`
				never fired, so picking a match mode did not change
				`form.matchMode`. Every dictionary created or edited through this
				dialog was written with the `caseInsensitive` default no matter
				what the user chose, silently.

				The sibling select in StandingConsentFormModal.vue already uses
				`v-model` and works; this was the only `:value`-bound NcSelect
				left in src/ (swept 2026-08-11).
			-->
			<NcSelect
				v-model="form.matchMode"
				:options="matchModeOptions"
				label="label"
				:reduce="(o) => o.value"
				:clearable="false"
				:inputLabel="t('filinq', 'Match mode')"
				required />
			<p class="custom-dictionary-form__hint">
				{{
					t(
						'filinq',
						'Exact matches case-sensitively; case-insensitive (default) folds case; word boundary is case-insensitive but never matches inside a longer word.',
					)
				}}
			</p>
			<NcCheckboxRadioSwitch v-model="form.active" type="switch">
				{{ t('filinq', 'Active (used in automatic detection)') }}
			</NcCheckboxRadioSwitch>

			<div v-if="formError" class="custom-dictionary-form__error">
				{{ formError }}
			</div>
		</div>

		<template #actions>
			<NcButton variant="tertiary" @click="onCancel">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="saving || !canSubmit"
				@click="submit">
				<template v-if="saving" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ editing ? t('filinq', 'Save') : t('filinq', 'Create') }}
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
