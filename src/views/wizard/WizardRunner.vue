<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The guided document wizard: one question per step, with progress, back
navigation that keeps the answers, and the skip logic applied on every
answer. A register object question picks from OpenRegister through the
shared object store. The review step lists every question that was asked;
Generate document sends one request to api/documents/generate, which checks
the answers again. Started from a register object (?register, ?schema,
?objectId) the wizard prefills what it can as suggestions to review.

@spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
-->

<template>
	<div class="wizard-runner" data-testid="wizard-runner">
		<div class="wizard-runner__header">
			<NcButton variant="tertiary" @click="back">
				{{ t('filinq', 'Back to the template') }}
			</NcButton>
			<h2 class="wizard-runner__title">
				{{ wizard ? wizard.name : t('filinq', 'Guided document') }}
			</h2>
		</div>

		<NcLoadingIcon v-if="loading" />
		<NcEmptyContent
			v-else-if="!wizard"
			:name="t('filinq', 'This template has no active wizard')"
			:description="
				t(
					'filinq',
					'Ask a template editor to add a wizard on the Wizard tab of the template.',
				)
			" />

		<template v-else>
			<p
				class="wizard-runner__progress"
				aria-live="polite"
				data-testid="wizard-progress">
				{{
					onReview
						? t('filinq', 'Review your answers')
						: t('filinq', 'Question {current} of {total}', {
								current: step + 1,
								total: steps.length,
							})
				}}
			</p>
			<NcProgressBar :value="progress" size="medium" />

			<!-- QUESTION STEP -->
			<form
				v-if="!onReview && current"
				class="wizard-runner__step"
				data-testid="wizard-step"
				@submit.prevent="next">
				<fieldset>
					<legend class="wizard-runner__question">
						{{ current.label }}
						<span
							v-if="current.required"
							class="wizard-runner__required">
							{{ t('filinq', '(required)') }}
						</span>
					</legend>
					<p v-if="current.helpText" class="wizard-runner__help">
						{{ current.helpText }}
					</p>

					<NcTextField
						v-if="current.type === 'text'"
						:modelValue="String(answers[current.key] ?? '')"
						:label="current.label"
						:labelOutside="true"
						data-testid="wizard-answer-text"
						@update:modelValue="answer" />

					<div
						v-else-if="current.type === 'choice'"
						role="radiogroup"
						:aria-label="current.label">
						<NcCheckboxRadioSwitch
							v-for="choice in current.choices"
							:key="choice.value"
							:modelValue="answers[current.key] || ''"
							:value="choice.value"
							type="radio"
							:name="'wizard-' + current.key"
							:data-testid="'wizard-choice-' + choice.value"
							@update:modelValue="answer">
							{{ choice.label }}
						</NcCheckboxRadioSwitch>
					</div>

					<NcTextField
						v-else-if="current.type === 'date'"
						:modelValue="String(answers[current.key] ?? '')"
						type="date"
						:label="current.label"
						:labelOutside="true"
						data-testid="wizard-answer-date"
						@update:modelValue="answer" />

					<NcSelect
						v-else-if="current.type === 'registerObject'"
						:modelValue="pickedOption"
						:inputLabel="current.label"
						:options="pickerOptions"
						:loading="pickerLoading"
						:filterable="false"
						label="label"
						data-testid="wizard-answer-object"
						@search="searchObjects"
						@update:modelValue="pickObject" />

					<p
						v-if="suggested.includes(current.key)"
						class="wizard-runner__suggested">
						{{
							t(
								'filinq',
								'Suggested from the object you started from. Change it if it is wrong.',
							)
						}}
					</p>
					<NcNoteCard v-if="serverErrors[current.key]" type="error">
						{{ serverErrors[current.key] }}
					</NcNoteCard>
				</fieldset>

				<div class="wizard-runner__actions">
					<NcButton :disabled="step === 0" @click="previous">
						{{ t('filinq', 'Previous') }}
					</NcButton>
					<NcButton
						variant="primary"
						type="submit"
						:disabled="!canContinue"
						data-testid="wizard-next">
						{{ t('filinq', 'Next') }}
					</NcButton>
				</div>
			</form>

			<!-- REVIEW STEP -->
			<div v-else class="wizard-runner__review" data-testid="wizard-review">
				<dl class="wizard-runner__answers">
					<template v-for="(question, index) in steps" :key="question.key">
						<dt>
							{{ question.label }}
						</dt>
						<dd :data-testid="'wizard-review-' + question.key">
							<span>{{ displayAnswer(question) }}</span>
							<span
								v-if="overriding.includes(question.key)"
								class="wizard-runner__override">
								{{
									t(
										'filinq',
										'replaces the value from the picked object',
									)
								}}
							</span>
							<span
								v-if="serverErrors[question.key]"
								class="wizard-runner__error">
								{{ serverErrors[question.key] }}
							</span>
							<NcButton variant="tertiary" @click="step = index">
								{{ t('filinq', 'Change') }}
							</NcButton>
						</dd>
					</template>
				</dl>

				<NcSelect
					v-model="format"
					:inputLabel="t('filinq', 'Format')"
					:options="formatOptions"
					:clearable="false"
					label="label"
					:reduce="(option) => option.id"
					class="wizard-runner__format" />

				<NcNoteCard v-if="missing.length" type="warning">
					{{ t('filinq', 'Some required questions have no answer yet.') }}
				</NcNoteCard>

				<div class="wizard-runner__actions">
					<NcButton @click="previous">
						{{ t('filinq', 'Previous') }}
					</NcButton>
					<NcButton
						variant="primary"
						:disabled="generating || missing.length > 0"
						data-testid="wizard-generate"
						@click="generate">
						{{
							generating
								? t('filinq', 'Generating…')
								: t('filinq', 'Generate document')
						}}
					</NcButton>
				</div>
			</div>
		</template>
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
	NcProgressBar,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import pinia from '../../pinia.js'
import {
	generateWithWizard,
	isAnswered,
	loadTemplateWizard,
	missingKeys,
	overridingKeys,
	prefillWizard,
	visibleKeys,
} from '../../services/wizard.js'
import { useObjectStore } from '../../store/store.js'

export default {
	name: 'WizardRunner',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcProgressBar,
		NcSelect,
		NcTextField,
	},

	data() {
		return {
			loading: true,
			wizard: null,
			answers: {},
			labels: {},
			suggested: [],
			step: 0,
			format: 'pdf',
			generating: false,
			serverErrors: {},
			pickerOptions: [],
			pickerLoading: false,
		}
	},

	computed: {
		/**
		 * Template id.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		templateId() {
			return this.$route?.params?.id || ''
		},

		/**
		 * Steps.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		steps() {
			if (!this.wizard) {
				return []
			}
			const visible = new Set(visibleKeys(this.wizard.questions, this.answers))
			return this.wizard.questions.filter((q) => visible.has(q.key))
		},

		/**
		 * On review.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		onReview() {
			return this.step >= this.steps.length
		},

		/**
		 * Current.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		current() {
			return this.steps[this.step] || null
		},

		/**
		 * Progress.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		progress() {
			if (!this.steps.length) {
				return 0
			}
			return Math.round(
				(Math.min(this.step, this.steps.length) / this.steps.length) * 100,
			)
		},

		/**
		 * Can continue.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		canContinue() {
			return (
				!this.current?.required || isAnswered(this.answers[this.current.key])
			)
		},

		/**
		 * Missing.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		missing() {
			return this.wizard ? missingKeys(this.wizard, this.answers) : []
		},

		/**
		 * Overriding.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		overriding() {
			return this.wizard ? overridingKeys(this.wizard, this.answers) : []
		},

		/**
		 * Picked option.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		pickedOption() {
			const id = this.answers[this.current?.key]
			return id ? { id, label: this.labels[id] || id } : null
		},

		/**
		 * Format options.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		formatOptions() {
			return [
				{ id: 'pdf', label: 'PDF' },
				{ id: 'odf', label: 'OpenDocument' },
				{ id: 'docx', label: 'Word' },
				{ id: 'html', label: 'HTML' },
			]
		},
	},

	watch: {
		/**
		 * Current.
		 *
		 * @param {*} question The question.
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		current(question) {
			if (question?.type === 'registerObject') {
				this.searchObjects('')
			}
		},
	},

	/**
	 * Mounted.
	 *
	 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
	 */
	async mounted() {
		await this.load()
	},

	methods: {
		t,

		/**
		 * Load.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		async load() {
			const result = await loadTemplateWizard(this.templateId)
			this.loading = false
			if (!result.ok) {
				showError(t('filinq', 'The wizard could not be loaded.'))
				return
			}
			this.wizard = result.data.wizard
			if (this.wizard) {
				await this.applyPrefill()
			}
		},

		/**
		 * Apply prefill.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		async applyPrefill() {
			const { register, schema, objectId } = this.$route?.query || {}
			if (!register || !schema || !objectId) {
				return
			}
			const result = await prefillWizard(this.wizard.uuid, {
				register,
				schema,
				objectId,
			})
			if (!result.ok) {
				return
			}
			this.answers = { ...this.answers, ...result.data.answers }
			this.suggested = Object.keys(result.data.answers)
		},

		/**
		 * Answer.
		 *
		 * @param {*} value The value.
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		answer(value) {
			this.answers = { ...this.answers, [this.current.key]: value }
			this.suggested = this.suggested.filter((key) => key !== this.current.key)
			delete this.serverErrors[this.current.key]
		},

		/**
		 * Pick object.
		 *
		 * @param {*} option The option.
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		pickObject(option) {
			if (option) {
				this.labels = { ...this.labels, [option.id]: option.label }
			}
			this.answer(option ? option.id : '')
		},

		/**
		 * Search objects.
		 *
		 * @param {*} search The search.
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		async searchObjects(search) {
			const question = this.current
			if (!question || question.type !== 'registerObject') {
				return
			}
			const store = useObjectStore(pinia)
			const type = `wizard-${question.register}-${question.schema}`
			if (!store.objectTypeRegistry?.[type]) {
				store.registerObjectType(type, question.schema, question.register)
			}
			this.pickerLoading = true
			const rows = await store.fetchCollectionForOptions(type, {
				_search: search || undefined,
				_limit: 20,
			})
			this.pickerLoading = false
			this.pickerOptions = rows.map((row) => ({
				id: row.uuid || row.id || row['@self']?.id,
				label:
					row['@self']?.name
					|| row.title
					|| row.name
					|| row.uuid
					|| row.id,
			}))
		},

		/**
		 * Next.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		next() {
			if (this.canContinue) {
				this.step += 1
			}
		},

		/**
		 * Previous.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		previous() {
			this.step = Math.max(0, Math.min(this.step, this.steps.length) - 1)
		},

		/**
		 * Display answer.
		 *
		 * @param {*} question The question.
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		displayAnswer(question) {
			const value = this.answers[question.key]
			if (!isAnswered(value)) {
				return t('filinq', 'No answer')
			}
			if (question.type === 'choice') {
				return (
					question.choices.find((c) => c.value === value)?.label || value
				)
			}
			if (question.type === 'registerObject') {
				return this.labels[value] || value
			}
			return String(value)
		},

		/**
		 * Generate.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		async generate() {
			this.generating = true
			const result = await generateWithWizard(
				this.templateId,
				this.wizard,
				this.answers,
				this.format,
			)
			this.generating = false
			if (!result.ok) {
				this.serverErrors = result.errors || {}
				showError(
					result.status === 422
						? t(
								'filinq',
								'Some answers were not accepted. Check the marked questions.',
							)
						: t('filinq', 'The document could not be generated.'),
				)
				return
			}
			const url = URL.createObjectURL(result.data)
			const link = document.createElement('a')
			link.href = url
			link.download = result.filename
			link.click()
			URL.revokeObjectURL(url)
			showSuccess(t('filinq', 'The document is generated.'))
		},

		/**
		 * Back.
		 *
		 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#4-2
		 */
		back() {
			this.$router.push({
				name: 'TemplateDetail',
				params: { id: this.templateId },
			})
		},
	},
}
</script>

<style scoped>
.wizard-runner {
	padding: calc(var(--default-grid-baseline) * 4);
	max-width: 720px;
}

.wizard-runner__header {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
}

.wizard-runner__title {
	margin: 0;
}

.wizard-runner__step fieldset {
	border: none;
	padding: 0;
	margin: calc(var(--default-grid-baseline) * 4) 0;
}

.wizard-runner__question {
	font-weight: bold;
	font-size: 1.1em;
	margin-bottom: calc(var(--default-grid-baseline) * 2);
}

.wizard-runner__required,
.wizard-runner__help,
.wizard-runner__suggested,
.wizard-runner__override {
	color: var(--color-text-maxcontrast);
}

.wizard-runner__error {
	color: var(--color-error-text);
}

.wizard-runner__actions {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 4);
}

.wizard-runner__answers dt {
	font-weight: bold;
	margin-top: calc(var(--default-grid-baseline) * 2);
}

.wizard-runner__answers dd {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
	margin: 0;
}

.wizard-runner__format {
	margin-top: calc(var(--default-grid-baseline) * 4);
}
</style>
