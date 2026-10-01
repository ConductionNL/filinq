<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/guided-document-wizard/tasks.md#4-1
-->

<template>
	<NcDialog
		:name="question ? t('filinq', 'Edit question') : t('filinq', 'New question')"
		size="normal"
		@closing="$emit('close')">
		<template #default>
			<div class="wizard-question">
				<NcTextField
					v-model="form.label"
					:label="t('filinq', 'Question')"
					:required="true"
					data-testid="wizard-question-label" />
				<NcTextField
					v-model="form.key"
					:label="t('filinq', 'Key')"
					:helperText="
						t(
							'filinq',
							'A short name for the question: letters, digits, - or _.',
						)
					"
					:required="true"
					data-testid="wizard-question-key" />
				<NcTextArea
					v-model="form.helpText"
					:label="t('filinq', 'Help text (optional)')" />
				<NcSelect
					v-model="form.type"
					:inputLabel="t('filinq', 'Answer type')"
					:options="typeOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id"
					data-testid="wizard-question-type" />
				<NcCheckboxRadioSwitch v-model="form.required">
					{{ t('filinq', 'An answer is required') }}
				</NcCheckboxRadioSwitch>

				<NcTextArea
					v-if="form.type === 'choice'"
					v-model="form.choicesText"
					:label="t('filinq', 'Choices, one per line as value=label')"
					:helperText="t('filinq', 'For example granted=Granted')"
					data-testid="wizard-question-choices" />

				<template v-if="form.type === 'registerObject'">
					<NcTextField
						v-model="form.register"
						:label="t('filinq', 'Register')"
						:required="true" />
					<NcTextField
						v-model="form.schema"
						:label="t('filinq', 'Schema')"
						:required="true" />
				</template>
				<NcTextField
					v-else
					v-model="form.mapsTo"
					:label="t('filinq', 'Data path in the template (optional)')"
					:helperText="
						t('filinq', 'Names joined by dots, such as applicant.name')
					"
					data-testid="wizard-question-maps-to" />

				<NcSelect
					v-model="form.conditionKey"
					:inputLabel="t('filinq', 'Ask only when this earlier question…')"
					:options="earlierOptions"
					label="label"
					:reduce="(option) => option.id"
					data-testid="wizard-question-condition" />
				<template v-if="form.conditionKey">
					<NcSelect
						v-model="form.conditionOperator"
						:inputLabel="t('filinq', '…is')"
						:options="operatorOptions"
						:clearable="false"
						label="label"
						:reduce="(option) => option.id" />
					<NcTextField
						v-if="form.conditionOperator !== 'answered'"
						v-model="form.conditionValue"
						:label="t('filinq', 'Answer to compare with')" />
				</template>

				<NcNoteCard v-if="problem" type="error">
					{{ problem }}
				</NcNoteCard>
			</div>
		</template>
		<template #actions>
			<NcButton @click="$emit('close')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="problem !== ''"
				data-testid="wizard-question-save"
				@click="onSave">
				{{ t('filinq', 'Keep question') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcNoteCard,
	NcSelect,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import {
	formToQuestion,
	questionProblem,
	questionToForm,
} from '../services/wizard.js'

/**
 * Add or change one wizard question: its text, key, answer type, the
 * choices or the register to pick from, where the answer goes in the
 * template data, and the earlier answer it depends on.
 *
 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
 */
export default {
	name: 'WizardQuestionDialog',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcNoteCard,
		NcSelect,
		NcTextArea,
		NcTextField,
	},

	props: {
		question: {
			type: Object,
			default: null,
		},

		earlier: {
			type: Array,
			default: () => [],
		},

		usedKeys: {
			type: Array,
			default: () => [],
		},
	},

	emits: ['close', 'save'],

	data() {
		return {
			form: questionToForm(this.question),
		}
	},

	computed: {
		/**
		 * The answer types.
		 *
		 * @return {Array<object>} The options.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		typeOptions() {
			return [
				{ id: 'text', label: t('filinq', 'Text') },
				{ id: 'choice', label: t('filinq', 'Choice') },
				{ id: 'date', label: t('filinq', 'Date') },
				{ id: 'registerObject', label: t('filinq', 'Register object') },
			]
		},

		/**
		 * The earlier questions a condition may name.
		 *
		 * @return {Array<object>} The options.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		earlierOptions() {
			return this.earlier.map((question) => ({
				id: question.key,
				label: question.label,
			}))
		},

		/**
		 * The condition operators.
		 *
		 * @return {Array<object>} The options.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		operatorOptions() {
			return [
				{ id: 'equals', label: t('filinq', 'equal to') },
				{ id: 'notEquals', label: t('filinq', 'not equal to') },
				{ id: 'answered', label: t('filinq', 'answered') },
			]
		},

		/**
		 * What keeps the question from being kept, or ''.
		 *
		 * @return {string} The problem.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		problem() {
			return questionProblem(this.form, this.usedKeys)
		},
	},

	methods: {
		t,

		/**
		 * Hand the question back.
		 *
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		onSave() {
			this.$emit('save', formToQuestion(this.form))
		},
	},
}
</script>

<style scoped>
.wizard-question {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
}
</style>
