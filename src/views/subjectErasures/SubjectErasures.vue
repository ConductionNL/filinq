<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
@visual exclude No pixel baseline yet: the page is driven through its /subject-erasures route by
	tests/e2e/workflows/subject-erasure.spec.ts, which reaches it by URL. A baseline needs a seeded
	instance whose entity catalogue names a person.
-->
<template>
	<div class="subject-erasures">
		<div class="subject-erasures__header">
			<div>
				<h2>{{ t('filinq', 'Erasure requests') }}</h2>
				<p class="subject-erasures__subtitle">
					{{
						t(
							'filinq',
							'Remove a person from every document they appear in, while the documents themselves stay.',
						)
					}}
				</p>
			</div>
			<NcButton variant="primary" @click="creating = true">
				{{ t('filinq', 'New erasure request') }}
			</NcButton>
		</div>

		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<NcLoadingIcon v-if="loading && requests.length === 0" :size="32" />
		<NcEmptyContent
			v-else-if="!loading && requests.length === 0 && !error"
			:name="t('filinq', 'No erasure requests')"
			:description="
				t(
					'filinq',
					'Record a request when somebody asks to be removed from the documents.',
				)
			" />
		<table v-else-if="requests.length > 0" class="subject-erasures__table">
			<thead>
				<tr>
					<th scope="col">
						{{ t('filinq', 'Person') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Status') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Received') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Due') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="request in requests"
					:key="request.uuid"
					:class="{
						'subject-erasures__row--selected':
							selected && selected.uuid === request.uuid,
					}">
					<td>
						<button
							class="subject-erasures__open"
							type="button"
							@click="select(request)">
							{{ request.subject }}
						</button>
					</td>
					<td>{{ statusLabel(request.status) }}</td>
					<td>{{ formatTime(request.requestedAt) }}</td>
					<td>{{ formatTime(request.dueAt) }}</td>
				</tr>
			</tbody>
		</table>

		<section
			v-if="selected"
			class="subject-erasures__detail"
			:aria-label="t('filinq', 'Erasure request details')">
			<h3>{{ selected.subject }}</h3>
			<p>
				{{
					t('filinq', 'Ground: {ground}. Requested by {requester}.', {
						ground: selected.ground,
						requester: selected.requester,
					})
				}}
			</p>
			<p v-if="selected.progress && selected.progress.documentsTotal > 0">
				{{
					t('filinq', '{done} of {total} documents reached.', {
						done: selected.progress.documentsDone,
						total: selected.progress.documentsTotal,
					})
				}}
			</p>
			<div class="subject-erasures__actions">
				<NcButton v-if="canPreview" @click="onPreview">
					{{ t('filinq', 'Build preview') }}
				</NcButton>
				<NcButton
					v-if="canRun"
					variant="error"
					@click="running = true">
					{{
						selected.status === 'partially_completed'
							? t('filinq', 'Resume erasure')
							: t('filinq', 'Erase')
					}}
				</NcButton>
				<NcButton v-if="selected.status === 'completed'" @click="onCertificate">
					{{ t('filinq', 'Show certificate') }}
				</NcButton>
			</div>

			<template v-if="preview">
				<h4>{{ t('filinq', 'Preview') }}</h4>
				<p>
					{{
						t(
							'filinq',
							'{occurrences} occurrences in {total} documents. {refused} refused, {unprocessable} cannot be processed.',
							{
								occurrences: preview.occurrencesTotal,
								total: preview.documentsTotal,
								refused: preview.refusedDocuments,
								unprocessable: preview.unprocessableCount,
							},
						)
					}}
				</p>
				<NcNoteCard v-if="preview.truncated" type="warning">
					{{
						t(
							'filinq',
							'Showing the first {cap} of {total} documents. The erasure covers all of them.',
							{ cap: preview.cap, total: preview.documentsTotal },
						)
					}}
				</NcNoteCard>
				<NcNoteCard
					v-for="item in preview.unprocessable"
					:key="`u-${item.document}`"
					type="warning">
					{{
						t('filinq', 'Document {document} cannot be processed: {reason}', {
							document: item.document,
							reason: item.reason,
						})
					}}
				</NcNoteCard>
				<table class="subject-erasures__table">
					<thead>
						<tr>
							<th scope="col">
								{{ t('filinq', 'Document') }}
							</th>
							<th scope="col">
								{{ t('filinq', 'Occurrences') }}
							</th>
							<th scope="col">
								{{ t('filinq', 'Final version') }}
							</th>
							<th scope="col">
								{{ t('filinq', 'Erase') }}
							</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="row in preview.documents" :key="row.document">
							<td>{{ row.name || row.document }}</td>
							<td>{{ row.occurrences }}</td>
							<td>
								{{ row.finalVersion ? t('filinq', 'Yes, a new version is written') : t('filinq', 'No') }}
							</td>
							<td>
								<template v-if="row.obligations.length > 0">
									<p
										v-for="obligation in row.obligations"
										:key="obligation.obligation">
										{{ obligation.reason }}
										{{ t('filinq', 'Decided by: {who}', { who: obligation.decidedBy }) }}
									</p>
								</template>
								<template v-else>
									<NcCheckboxRadioSwitch
										v-for="value in row.values"
										:key="value"
										:modelValue="isErased(row.document, value)"
										@update:modelValue="setErased(row.document, value, $event)">
										{{ value }}
									</NcCheckboxRadioSwitch>
									<NcTextField
										v-if="leavesSomething(row)"
										:modelValue="reasons[row.document] || ''"
										:label="t('filinq', 'Why is this left in place?')"
										@update:modelValue="setReason(row.document, $event)" />
								</template>
							</td>
						</tr>
					</tbody>
				</table>
				<NcButton
					v-if="canRun"
					:disabled="!exclusionsReady"
					@click="onSaveExclusions">
					{{ t('filinq', 'Save what is left in place') }}
				</NcButton>
			</template>

			<template v-if="(selected.results || []).length > 0">
				<h4>{{ t('filinq', 'Results') }}</h4>
				<table class="subject-erasures__table">
					<tbody>
						<tr v-for="result in selected.results" :key="`r-${result.document}`">
							<td>{{ result.name || result.document }}</td>
							<td>{{ outcomeLabel(result.outcome) }}</td>
							<td>{{ result.reason }}</td>
						</tr>
					</tbody>
				</table>
			</template>

			<template v-if="certificate">
				<h4>{{ t('filinq', 'Certificate') }}</h4>
				<NcNoteCard :type="certificate.complete ? 'success' : 'warning'">
					{{
						certificate.complete
							? t('filinq', 'The person was erased from every document. No document was deleted.')
							: t('filinq', 'Not everything was erased. The refused documents below need a decision.')
					}}
				</NcNoteCard>
				<p>
					{{
						t('filinq', 'Issued {date} by {actor}. {erased} documents erased, {refused} refused, {republish} to republish.', {
							date: formatTime(certificate.issuedAt),
							actor: certificate.actor,
							erased: (certificate.erased || []).length,
							refused: (certificate.refused || []).length,
							republish: (certificate.needsRepublishing || []).length,
						})
					}}
				</p>
				<ul>
					<li v-for="row in certificate.refused || []" :key="`c-${row.document}-${row.obligation}`">
						{{ row.document }}: {{ row.reason }}
						{{ t('filinq', 'Decided by: {who}', { who: row.decidedBy }) }}
					</li>
				</ul>
			</template>
		</section>

		<NewSubjectErasureDialog
			v-if="creating"
			@close="creating = false"
			@created="onCreated" />
		<RunSubjectErasureDialog
			v-if="running && selected"
			:requestId="selected.uuid"
			:erasable="preview ? preview.erasableDocuments : 0"
			:refused="preview ? preview.refusedDocuments : 0"
			@close="running = false"
			@ran="onRan" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcCheckboxRadioSwitch,
	NcEmptyContent,
	NcLoadingIcon,
	NcNoteCard,
	NcTextField,
} from '@nextcloud/vue'
import NewSubjectErasureDialog from '../../dialogs/NewSubjectErasureDialog.vue'
import RunSubjectErasureDialog from '../../dialogs/RunSubjectErasureDialog.vue'
import {
	buildExclusions,
	exclusionsComplete,
	fetchCertificate,
	listRequests,
	outcomeLabel,
	previewRequest,
	saveExclusions,
	statusLabel,
} from '../../services/subjectErasures.js'

/**
 * Erasure requests: record one, read the preview, leave occurrences in place
 * with a reason, run, and read the certificate.
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
 */
export default {
	name: 'SubjectErasures',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		NewSubjectErasureDialog,
		RunSubjectErasureDialog,
	},

	data() {
		return {
			requests: [],
			selected: null,
			preview: null,
			certificate: null,
			kept: {},
			reasons: {},
			loading: false,
			error: '',
			creating: false,
			running: false,
		}
	},

	computed: {
		/**
		 * Whether a preview may be built now.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		canPreview() {
			return ['received', 'previewed', 'partially_completed'].includes(this.selected?.status)
		},

		/**
		 * Whether the run may start or resume now.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		canRun() {
			return ['previewed', 'partially_completed'].includes(this.selected?.status)
		},

		/**
		 * The exclusions the choices make.
		 *
		 * @return {Array<object>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		exclusions() {
			return buildExclusions(this.preview?.documents || [], this.kept, this.reasons)
		},

		/**
		 * Whether every exclusion has a reason.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		exclusionsReady() {
			return exclusionsComplete(this.exclusions)
		},
	},

	/**
	 * Load the requests.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
	 */
	mounted() {
		this.load()
	},

	methods: {
		t,
		outcomeLabel,
		statusLabel,

		/**
		 * Read the requests.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async load() {
			this.loading = true
			const answer = await listRequests()
			this.loading = false
			if (!answer.ok) {
				this.error = answer.error
				this.requests = []
				return
			}
			this.error = ''
			this.requests = answer.data.results || []
			if (this.selected) {
				this.selected = this.requests.find((r) => r.uuid === this.selected.uuid) || null
			}
		},

		/**
		 * Show one request.
		 *
		 * @param {object} request The request.
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		select(request) {
			this.selected = request
			this.preview = null
			this.certificate = null
			this.kept = {}
			this.reasons = {}
		},

		/**
		 * Show a new request.
		 *
		 * @param {object} request The request.
		 * @return {Promise<void>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async onCreated(request) {
			this.creating = false
			await this.load()
			this.select(request)
		},

		/**
		 * Build the preview.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async onPreview() {
			const answer = await previewRequest(this.selected.uuid)
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.error = ''
			this.preview = answer.data
			await this.load()
		},

		/**
		 * Store the exclusions.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		async onSaveExclusions() {
			const answer = await saveExclusions(this.selected.uuid, this.exclusions)
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.error = ''
			this.selected = answer.data
		},

		/**
		 * Show the outcome of a run.
		 *
		 * @param {object} outcome {request, certificate}.
		 * @return {Promise<void>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async onRan(outcome) {
			this.running = false
			this.certificate = outcome.certificate
			await this.load()
			this.selected = outcome.request
		},

		/**
		 * Read the certificate.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		async onCertificate() {
			const answer = await fetchCertificate(this.selected.uuid)
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.certificate = answer.data
		},

		/**
		 * Whether a value is ticked for erasure.
		 *
		 * @param {string} documentId The document.
		 * @param {string} value The identifier.
		 * @return {boolean}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		isErased(documentId, value) {
			return this.kept[documentId]?.[value] !== false
		},

		/**
		 * Tick or untick a value.
		 *
		 * @param {string} documentId The document.
		 * @param {string} value The identifier.
		 * @param {boolean} erased Whether it is erased.
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		setErased(documentId, value, erased) {
			this.kept = { ...this.kept, [documentId]: { ...(this.kept[documentId] || {}), [value]: erased } }
		},

		/**
		 * Whether a row leaves an identifier in place.
		 *
		 * @param {object} row The preview row.
		 * @return {boolean}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		leavesSomething(row) {
			return (row.values || []).some((value) => !this.isErased(row.document, value))
		},

		/**
		 * Set a row's reason.
		 *
		 * @param {string} documentId The document.
		 * @param {string} reason The reason.
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-2.2
		 */
		setReason(documentId, reason) {
			this.reasons = { ...this.reasons, [documentId]: reason }
		},

		/**
		 * A date and time in the user's locale.
		 *
		 * @param {string} value An ISO 8601 date-time.
		 * @return {string}
		 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-5.2
		 */
		formatTime(value) {
			return value ? new Date(value).toLocaleString() : ''
		},
	},
}
</script>

<style scoped>
.subject-erasures {
	padding: calc(var(--default-grid-baseline, 4px) * 4);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 3);
}

.subject-erasures__header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: calc(var(--default-grid-baseline, 4px) * 3);
}

.subject-erasures__subtitle {
	color: var(--color-text-maxcontrast);
}

.subject-erasures__table {
	width: 100%;
	border-collapse: collapse;
}

.subject-erasures__table th,
.subject-erasures__table td {
	text-align: start;
	padding: calc(var(--default-grid-baseline, 4px) * 2);
	border-bottom: 1px solid var(--color-border);
	vertical-align: top;
}

.subject-erasures__row--selected {
	background-color: var(--color-primary-element-light);
}

.subject-erasures__open {
	background: none;
	border: none;
	padding: 0;
	color: var(--color-main-text);
	text-decoration: underline;
	cursor: pointer;
}

.subject-erasures__detail,
.subject-erasures__actions {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}
</style>
