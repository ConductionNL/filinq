<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
-->
<template>
	<div class="legal-holds">
		<div class="legal-holds__header">
			<div>
				<h2>{{ t('filinq', 'Legal holds') }}</h2>
				<p class="legal-holds__subtitle">
					{{
						t(
							'filinq',
							'Records under a legal hold cannot be destroyed or deleted until the hold is released.',
						)
					}}
				</p>
			</div>
			<NcButton variant="primary" @click="creating = true">
				{{ t('filinq', 'New legal hold') }}
			</NcButton>
		</div>

		<div class="legal-holds__filters">
			<NcSelect
				v-model="statusFilter"
				:options="statusOptions"
				label="label"
				:inputLabel="t('filinq', 'Status')"
				@update:modelValue="load" />
			<NcSelect
				v-model="typeFilter"
				:options="typeOptions"
				label="label"
				:inputLabel="t('filinq', 'Matter type')"
				@update:modelValue="load" />
			<NcTextField
				v-model="custodianFilter"
				:label="t('filinq', 'Custodian')"
				@update:modelValue="load" />
		</div>

		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<NcLoadingIcon v-if="loading && cases.length === 0" :size="32" />
		<NcEmptyContent
			v-else-if="!loading && cases.length === 0 && !error"
			:name="t('filinq', 'No legal holds')"
			:description="
				t(
					'filinq',
					'Place a hold when a lawsuit, an audit or a Woo appeal needs records kept as they are.',
				)
			" />
		<table v-else class="legal-holds__table">
			<thead>
				<tr>
					<th scope="col">
						{{ t('filinq', 'Name') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Matter type') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Status') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Custodian') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Records') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Placed') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="holdCase in cases"
					:key="holdCase.uuid"
					:class="{
						'legal-holds__row--selected':
							selected && selected.uuid === holdCase.uuid,
					}">
					<td>
						<button
							class="legal-holds__open"
							type="button"
							@click="selected = holdCase">
							{{ holdCase.name }}
						</button>
					</td>
					<td>{{ typeLabel(holdCase.holdType) }}</td>
					<td>
						{{ statusLabel(holdCase) }}
					</td>
					<td>{{ holdCase.custodian || '' }}</td>
					<td>
						{{
							(holdCase.scopeDocuments || []).length
							+ (holdCase.scopeDossiers || []).length
						}}
					</td>
					<td>{{ formatTime(holdCase.placedAt) }}</td>
				</tr>
			</tbody>
		</table>

		<section
			v-if="selected"
			class="legal-holds__detail"
			:aria-label="t('filinq', 'Legal hold details')">
			<h3>{{ selected.name }}</h3>
			<p>{{ selected.reason }}</p>
			<NcNoteCard
				v-if="
					selected.status === 'active'
					&& selected.protection !== 'complete'
				"
				type="warning">
				{{
					t(
						'filinq',
						'Not every record is frozen yet. Check the records below and retry.',
					)
				}}
			</NcNoteCard>
			<NcNoteCard
				v-if="selected.fileLockBackstop === 'unavailable'"
				type="info">
				{{
					t(
						'filinq',
						'File locking is not installed, so files are not locked. The records themselves are frozen.',
					)
				}}
			</NcNoteCard>
			<p v-if="selected.status === 'released'">
				{{
					t('filinq', 'Released by {user} on {date}: {reason}', {
						user: selected.releasedBy,
						date: formatTime(selected.releasedAt),
						reason: selected.releaseReason,
					})
				}}
			</p>
			<table class="legal-holds__table">
				<thead>
					<tr>
						<th scope="col">
							{{ t('filinq', 'Record') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Record hold') }}
						</th>
						<th scope="col">
							{{ t('filinq', 'Files') }}
						</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="entry in selected.fanOut || []" :key="entry.ref">
						<td>{{ entry.ref }}</td>
						<td>
							{{ recordLabel(entry) }}
							<span
								v-if="entry.recordError"
								class="legal-holds__error"
								>{{ entry.recordError }}</span
							>
						</td>
						<td>
							{{ fileLabel(entry) }}
							<span
								v-if="entry.fileError"
								class="legal-holds__error"
								>{{ entry.fileError }}</span
							>
						</td>
					</tr>
				</tbody>
			</table>
			<div class="legal-holds__actions">
				<NcButton v-if="needsRetry(selected)" @click="onRetry">
					{{ t('filinq', 'Retry') }}
				</NcButton>
				<template v-if="selected.status === 'active'">
					<NcTextArea
						v-model="additions"
						:label="
							t('filinq', 'Add document record ids, one per line')
						" />
					<NcButton
						:disabled="parseRefs(additions).length === 0"
						@click="onAddScope">
						{{ t('filinq', 'Add to hold') }}
					</NcButton>
					<NcButton variant="warning" @click="releasing = true">
						{{ t('filinq', 'Release') }}
					</NcButton>
				</template>
			</div>
		</section>

		<NewLegalHoldDialog
			v-if="creating"
			@close="creating = false"
			@created="onSaved" />
		<ReleaseLegalHoldDialog
			v-if="releasing && selected"
			:holdId="selected.uuid"
			:holdName="selected.name"
			@close="releasing = false"
			@released="onSaved" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	NcButton,
	NcEmptyContent,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
	NcTextArea,
	NcTextField,
} from '@nextcloud/vue'
import NewLegalHoldDialog from '../../dialogs/NewLegalHoldDialog.vue'
import ReleaseLegalHoldDialog from '../../dialogs/ReleaseLegalHoldDialog.vue'
import {
	addScope,
	fileLabel,
	holdTypeOptions,
	listCases,
	needsRetry,
	parseRefs,
	recordLabel,
	retryCase,
} from '../../services/legalHolds.js'

/**
 * The hold register: every legal hold, filtered by status, matter type and
 * custodian, with the placement per record and the release. Hold + freeze +
 * audit only; no review, tagging or export.
 *
 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
 */
export default {
	name: 'LegalHolds',
	components: {
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcTextArea,
		NcTextField,
		NewLegalHoldDialog,
		ReleaseLegalHoldDialog,
	},

	data() {
		return {
			cases: [],
			selected: null,
			loading: false,
			error: '',
			creating: false,
			releasing: false,
			additions: '',
			statusFilter: null,
			typeFilter: null,
			custodianFilter: '',
			statusOptions: [
				{ id: 'active', label: t('filinq', 'Active') },
				{ id: 'released', label: t('filinq', 'Released') },
			],

			typeOptions: holdTypeOptions(),
		}
	},

	/**
	 * Load the register.
	 *
	 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
	 */
	mounted() {
		this.load()
	},

	methods: {
		t,
		fileLabel,
		needsRetry,
		parseRefs,
		recordLabel,

		/**
		 * Read the register with the current filters.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		async load() {
			this.loading = true
			const answer = await listCases({
				status: this.statusFilter?.id,
				holdType: this.typeFilter?.id,
				custodian: this.custodianFilter.trim(),
			})
			this.loading = false
			if (!answer.ok) {
				this.error = answer.error
				this.cases = []
				return
			}
			this.error = ''
			this.cases = answer.data.results || []
			if (this.selected) {
				this.selected =
					this.cases.find((c) => c.uuid === this.selected.uuid) || null
			}
		},

		/**
		 * Show a saved case and read the register again.
		 *
		 * @param {object} holdCase The case as saved.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		async onSaved(holdCase) {
			this.creating = false
			this.releasing = false
			this.selected = holdCase
			await this.load()
		},

		/**
		 * Retry the records that failed.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		async onRetry() {
			const answer = await retryCase(this.selected.uuid)
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			await this.onSaved(answer.data)
		},

		/**
		 * Add the typed records to the hold.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		async onAddScope() {
			const answer = await addScope(this.selected.uuid, {
				scopeDocuments: parseRefs(this.additions),
			})
			if (!answer.ok) {
				this.error = answer.error
				return
			}
			this.additions = ''
			await this.onSaved(answer.data)
		},

		/**
		 * The matter type in words.
		 *
		 * @param {string} id The type.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		typeLabel(id) {
			return this.typeOptions.find((o) => o.id === id)?.label || id
		},

		/**
		 * The status in words, with partial protection called out.
		 *
		 * @param {object} holdCase The case.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		statusLabel(holdCase) {
			if (holdCase.status === 'released') {
				return t('filinq', 'Released')
			}
			return holdCase.protection === 'complete'
				? t('filinq', 'Active')
				: t('filinq', 'Active, not every record frozen')
		},

		/**
		 * A date and time in the user's locale.
		 *
		 * @param {string} value An ISO 8601 date-time.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-29-e-discovery-legal-hold/tasks.md#task-3.1
		 */
		formatTime(value) {
			return value ? new Date(value).toLocaleString() : ''
		},
	},
}
</script>

<style scoped>
.legal-holds {
	padding: calc(var(--default-grid-baseline, 4px) * 4);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 3);
}

.legal-holds__header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: calc(var(--default-grid-baseline, 4px) * 3);
}

.legal-holds__subtitle {
	color: var(--color-text-maxcontrast);
}

.legal-holds__filters {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline, 4px) * 3);
}

.legal-holds__table {
	width: 100%;
	border-collapse: collapse;
}

.legal-holds__table th,
.legal-holds__table td {
	text-align: start;
	padding: calc(var(--default-grid-baseline, 4px) * 2);
	border-bottom: 1px solid var(--color-border);
}

.legal-holds__row--selected {
	background-color: var(--color-primary-element-light);
}

.legal-holds__open {
	background: none;
	border: none;
	padding: 0;
	color: var(--color-main-text);
	text-decoration: underline;
	cursor: pointer;
}

.legal-holds__error {
	display: block;
	color: var(--color-text-maxcontrast);
}

.legal-holds__detail {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}

.legal-holds__actions {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}
</style>
