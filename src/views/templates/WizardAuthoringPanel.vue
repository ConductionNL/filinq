<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The Wizard tab of a template: the ordered questions of the template's
wizard, moved up and down, added, changed and removed, and saved through
api/wizards. The server refuses a wizard that cannot run (per question) and
warns when a question does not fit the template's bound schema. A question
is edited in WizardQuestionDialog.

@spec openspec/changes/guided-document-wizard/tasks.md#4-1
-->

<template>
	<div class="wizard-authoring" data-testid="wizard-authoring">
		<NcLoadingIcon v-if="loading" />
		<template v-else>
			<p class="wizard-authoring__intro">
				{{
					t(
						'filinq',
						'A wizard asks clerks questions one at a time and fills this template with the answers. Questions can depend on earlier answers.',
					)
				}}
			</p>

			<div class="wizard-authoring__meta">
				<NcTextField
					v-model="wizard.name"
					:label="t('filinq', 'Wizard name')"
					:disabled="readOnly"
					data-testid="wizard-name" />
				<NcCheckboxRadioSwitch
					v-model="wizard.active"
					:disabled="readOnly"
					type="switch">
					{{ t('filinq', 'Offer this wizard to clerks') }}
				</NcCheckboxRadioSwitch>
			</div>

			<NcEmptyContent
				v-if="!wizard.questions.length"
				:name="t('filinq', 'No questions yet')"
				:description="
					t('filinq', 'Add the first question the clerk answers.')
				" />

			<ol v-else class="wizard-authoring__questions">
				<li
					v-for="(question, index) in wizard.questions"
					:key="question.key"
					class="wizard-authoring__question"
					:data-testid="'wizard-question-' + question.key">
					<div class="wizard-authoring__question-text">
						<strong>{{ question.label }}</strong>
						<span class="wizard-authoring__muted">
							{{ typeLabel(question.type)
							}}{{
								question.required
									? ', ' + t('filinq', 'required')
									: ''
							}}
						</span>
						<span
							v-if="question.condition"
							class="wizard-authoring__muted">
							{{ conditionText(question.condition) }}
						</span>
						<span
							v-if="errors[question.key]"
							class="wizard-authoring__error">
							{{ errors[question.key] }}
						</span>
					</div>
					<div class="wizard-authoring__question-actions">
						<NcButton
							variant="tertiary"
							:disabled="readOnly || index === 0"
							:aria-label="t('filinq', 'Move up')"
							@click="move(index, -1)">
							{{ t('filinq', 'Up') }}
						</NcButton>
						<NcButton
							variant="tertiary"
							:disabled="
								readOnly || index === wizard.questions.length - 1
							"
							:aria-label="t('filinq', 'Move down')"
							@click="move(index, 1)">
							{{ t('filinq', 'Down') }}
						</NcButton>
						<NcButton
							variant="tertiary"
							:disabled="readOnly"
							@click="edit(index)">
							{{ t('filinq', 'Edit') }}
						</NcButton>
						<NcButton
							variant="tertiary"
							:disabled="readOnly"
							@click="remove(index)">
							{{ t('filinq', 'Remove') }}
						</NcButton>
					</div>
				</li>
			</ol>

			<NcNoteCard v-for="warning in warnings" :key="warning" type="warning">
				{{ warning }}
			</NcNoteCard>
			<NcNoteCard v-if="formError" type="error">
				{{ formError }}
			</NcNoteCard>

			<div class="wizard-authoring__actions">
				<NcButton
					:disabled="readOnly"
					data-testid="wizard-add-question"
					@click="edit(-1)">
					{{ t('filinq', 'Add a question') }}
				</NcButton>
				<NcButton
					variant="primary"
					:disabled="readOnly || saving || !wizard.questions.length"
					data-testid="wizard-save"
					@click="save">
					{{
						saving ? t('filinq', 'Saving…') : t('filinq', 'Save wizard')
					}}
				</NcButton>
				<NcButton
					v-if="wizard.uuid"
					variant="tertiary"
					:disabled="readOnly"
					@click="removeWizard">
					{{ t('filinq', 'Delete wizard') }}
				</NcButton>
			</div>
		</template>

		<WizardQuestionDialog
			v-if="editing !== null"
			:question="editing >= 0 ? wizard.questions[editing] : null"
			:earlier="
				editing >= 0 ? wizard.questions.slice(0, editing) : wizard.questions
			"
			:usedKeys="usedKeys"
			@close="editing = null"
			@save="storeQuestion" />
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcEmptyContent,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import WizardQuestionDialog from '../../dialogs/WizardQuestionDialog.vue'
import {
	deleteWizard,
	loadTemplateWizard,
	saveWizard,
} from '../../services/wizard.js'

export default {
	name: 'WizardAuthoringPanel',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		WizardQuestionDialog,
	},

	props: {
		templateId: {
			type: String,
			required: true,
		},

		templateName: {
			type: String,
			default: '',
		},

		readOnly: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['saved'],

	data() {
		return {
			loading: true,
			saving: false,
			wizard: this.blank(),
			editing: null,
			errors: {},
			warnings: [],
			formError: '',
		}
	},

	computed: {
		/**
		 * Used keys.
		 *
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		usedKeys() {
			return this.wizard.questions
				.filter((question, index) => index !== this.editing)
				.map((question) => question.key)
		},
	},

	/**
	 * Mounted.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
	 */
	async mounted() {
		const result = await loadTemplateWizard(this.templateId)
		this.loading = false
		if (result.ok && result.data.wizard) {
			this.wizard = { ...this.blank(), ...result.data.wizard }
		}
	},

	methods: {
		t,

		/**
		 * Blank.
		 *
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		blank() {
			return {
				name: this.templateName
					? t('filinq', '{name} wizard', { name: this.templateName })
					: '',

				templateId: this.templateId,
				active: true,
				questions: [],
			}
		},

		/**
		 * Type label.
		 *
		 * @param {*} type The type.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		typeLabel(type) {
			return (
				{
					text: t('filinq', 'Text'),
					choice: t('filinq', 'Choice'),
					date: t('filinq', 'Date'),
					registerObject: t('filinq', 'Register object'),
				}[type] || type
			)
		},

		/**
		 * Condition text.
		 *
		 * @param {*} condition The condition.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		conditionText(condition) {
			if (condition.operator === 'answered') {
				return t('filinq', 'Asked when "{question}" is answered', {
					question: condition.questionKey,
				})
			}
			if (condition.operator === 'notEquals') {
				return t('filinq', 'Asked when "{question}" is not {value}', {
					question: condition.questionKey,
					value: condition.value,
				})
			}
			return t('filinq', 'Asked when "{question}" is {value}', {
				question: condition.questionKey,
				value: condition.value,
			})
		},

		/**
		 * Move.
		 *
		 * @param {*} index The index.
		 * @param {*} by The by.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		move(index, by) {
			const questions = [...this.wizard.questions]
			const [question] = questions.splice(index, 1)
			questions.splice(index + by, 0, question)
			this.wizard = { ...this.wizard, questions }
		},

		/**
		 * Edit.
		 *
		 * @param {*} index The index.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		edit(index) {
			this.editing = index
		},

		/**
		 * Remove.
		 *
		 * @param {*} index The index.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		remove(index) {
			const key = this.wizard.questions[index].key
			this.wizard = {
				...this.wizard,
				questions: this.wizard.questions
					.filter((question, i) => i !== index)
					.map((question) =>
						question.condition?.questionKey === key
							? { ...question, condition: undefined }
							: question,
					),
			}
		},

		/**
		 * Store question.
		 *
		 * @param {*} question The question.
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		storeQuestion(question) {
			const questions = [...this.wizard.questions]
			if (this.editing >= 0) {
				questions.splice(this.editing, 1, question)
			} else {
				questions.push(question)
			}
			this.wizard = { ...this.wizard, questions }
			this.editing = null
		},

		/**
		 * Save.
		 *
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		async save() {
			this.saving = true
			this.errors = {}
			this.formError = ''
			const result = await saveWizard({
				...this.wizard,
				templateId: this.templateId,
			})
			this.saving = false
			if (!result.ok) {
				this.errors = result.errors || {}
				this.formError =
					{
						409: t(
							'filinq',
							'This template already has an active wizard. Switch the other one off first.',
						),

						423: t(
							'filinq',
							'Someone else is editing this template. Try again when they are done.',
						),

						403: t(
							'filinq',
							'Only template editors can change a wizard.',
						),

						422: t(
							'filinq',
							'The wizard has errors. Check the marked questions.',
						),
					}[result.status] || t('filinq', 'The wizard could not be saved.')
				return
			}
			this.wizard = { ...this.blank(), ...result.data.wizard }
			this.warnings = result.data.warnings || []
			showSuccess(t('filinq', 'Wizard saved'))
			this.$emit('saved', this.wizard)
		},

		/**
		 * Remove wizard.
		 *
		 * @spec openspec/changes/guided-document-wizard/tasks.md#4-1
		 */
		async removeWizard() {
			const result = await deleteWizard(this.wizard.uuid)
			if (!result.ok) {
				showError(t('filinq', 'The wizard could not be deleted.'))
				return
			}
			this.wizard = this.blank()
			this.$emit('saved', null)
		},
	},
}
</script>

<style scoped>
.wizard-authoring__meta,
.wizard-authoring__actions {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 3);
	margin: calc(var(--default-grid-baseline) * 3) 0;
}

.wizard-authoring__questions {
	padding-inline-start: calc(var(--default-grid-baseline) * 5);
}

.wizard-authoring__question {
	display: flex;
	justify-content: space-between;
	gap: calc(var(--default-grid-baseline) * 2);
	padding: calc(var(--default-grid-baseline) * 2) 0;
	border-bottom: 1px solid var(--color-border);
}

.wizard-authoring__question-text {
	display: flex;
	flex-direction: column;
}

.wizard-authoring__question-actions {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
}

.wizard-authoring__muted {
	color: var(--color-text-maxcontrast);
}

.wizard-authoring__error {
	color: var(--color-error-text);
}
</style>
