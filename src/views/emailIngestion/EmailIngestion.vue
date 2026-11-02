<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The email ingestion status page: every email filed from a watched inbox into
its dossier, and every file that could not be filed, with the reason. A filed
email without its PDF copy can be converted again; the inboxes can be scanned
by hand. Admins only, like the api/email-ingestion routes behind it.

@spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
-->

<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Email ingestion')"
			:description="
				t(
					'filinq',
					'Emails saved as .eml in a watched inbox folder are filed into the dossier of that inbox, with a PDF copy beside them. A file that could not be filed stays in the inbox and shows here with the reason.',
				)
			"
			:showTitle="true"
			:objects="visibleRows"
			:columns="tableColumns"
			:loading="loading"
			:selectable="false"
			:showAdd="false"
			:showEditAction="false"
			:showCopyAction="false"
			:showDeleteAction="false"
			:showMassImport="false"
			:showMassExport="false"
			:showMassCopy="false"
			:showMassDelete="false"
			:showViewToggle="false"
			rowKey="uuid"
			:emptyText="
				t(
					'filinq',
					'No emails yet. Map an inbox folder to a dossier in the filinq admin settings, then put .eml files in it.',
				)
			"
			:refreshing="refreshing"
			data-testid="email-ingestion-index"
			@refresh="refresh">
			<template #below-header>
				<div class="email-ingestion__bar">
					<NcSelect
						v-model="stateFilter"
						class="email-ingestion__filter"
						:inputLabel="t('filinq', 'Status')"
						:options="stateOptions"
						label="label"
						:reduce="(option) => option.id"
						data-testid="email-ingestion-status-filter" />
					<NcSelect
						v-model="dossierFilter"
						class="email-ingestion__filter"
						:inputLabel="t('filinq', 'Dossier')"
						:options="dossierOptions"
						data-testid="email-ingestion-dossier-filter" />
					<NcButton
						variant="primary"
						:disabled="scanning"
						data-testid="email-ingestion-rescan"
						@click="scanNow">
						{{ t('filinq', 'Scan inboxes now') }}
					</NcButton>
				</div>
			</template>

			<template #column-subject="{ row }">
				<span v-if="row.subject">{{ row.subject }}</span>
				<span v-else class="email-ingestion__muted">{{
					t('filinq', 'No subject')
				}}</span>
				<CnStatusBadge
					v-if="inThread(row, rows)"
					class="email-ingestion__thread"
					:label="t('filinq', 'Thread')"
					:colorMap="{ [t('filinq', 'Thread')]: 'primary' }" />
			</template>

			<template #column-sentAt="{ row }">
				{{ row.sentAt ? formatDate(row.sentAt) : '' }}
			</template>

			<template #column-state="{ row }">
				<CnStatusBadge
					:label="stateLabel(rowState(row))"
					:colorMap="stateColorMap"
					:data-testid="'email-state-' + rowState(row)" />
				<p
					v-if="row.status === 'failed'"
					class="email-ingestion__reason"
					data-testid="email-failure-reason">
					{{ failureText(row.failureReason) }}
				</p>
			</template>

			<template #row-actions="{ row }">
				<NcButton
					v-if="rowState(row) === 'not-converted'"
					variant="secondary"
					:disabled="retrying === row.uuid"
					data-testid="email-ingestion-retry"
					@click="convertAgain(row)">
					{{ t('filinq', 'Convert again') }}
				</NcButton>
			</template>
		</CnIndexPage>
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcSelect } from '@nextcloud/vue'
import {
	failureText,
	inThread,
	listEmails,
	rescan,
	retryConversion,
	rowState,
	stateLabel,
} from '../../services/emailIngestion.js'

export default {
	name: 'EmailIngestion',
	components: {
		CnIndexPage,
		CnStatusBadge,
		NcButton,
		NcSelect,
	},

	data() {
		return {
			rows: [],
			loading: true,
			refreshing: false,
			scanning: false,
			retrying: '',
			stateFilter: null,
			dossierFilter: null,
			stateColorMap: {
				[t('filinq', 'Filed')]: 'success',
				[t('filinq', 'Filed, not converted')]: 'warning',
				[t('filinq', 'Failed')]: 'error',
				[t('filinq', 'Received')]: 'default',
			},
		}
	},

	computed: {
		/**
		 * The columns of the status table.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		tableColumns() {
			return [
				{ key: 'subject', label: t('filinq', 'Subject') },
				{ key: 'fromAddress', label: t('filinq', 'From') },
				{ key: 'sentAt', label: t('filinq', 'Sent at') },
				{ key: 'dossierRef', label: t('filinq', 'Dossier') },
				{ key: 'state', label: t('filinq', 'Status') },
			]
		},

		/**
		 * The status filter options.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		stateOptions() {
			return ['filed', 'not-converted', 'failed'].map((id) => ({
				id,
				label: stateLabel(id),
			}))
		},

		/**
		 * The dossiers that occur in the list, for the dossier filter.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		dossierOptions() {
			return [...new Set(this.rows.map((row) => row.dossierRef))]
				.filter(Boolean)
				.sort()
		},

		/**
		 * The rows that pass the status and dossier filters.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		visibleRows() {
			return this.rows.filter(
				(row) =>
					(!this.stateFilter || rowState(row) === this.stateFilter)
					&& (!this.dossierFilter
						|| row.dossierRef === this.dossierFilter),
			)
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		failureText,
		inThread,
		rowState,
		stateLabel,

		/**
		 * Load the email records.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		async load() {
			const result = await listEmails()
			this.loading = false
			if (!result.ok) {
				showError(t('filinq', 'The emails could not be loaded.'))
				return
			}
			this.rows = result.data.results || []
		},

		/**
		 * Reload the list on the refresh action.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		async refresh() {
			this.refreshing = true
			await this.load()
			this.refreshing = false
		},

		/**
		 * Scan the inboxes now and reload.
		 *
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		async scanNow() {
			this.scanning = true
			const result = await rescan()
			this.scanning = false
			if (!result.ok) {
				showError(t('filinq', 'The inboxes could not be scanned.'))
				return
			}
			showSuccess(
				t('filinq', 'Emails handled: {count}', {
					count: result.data.processed,
				}),
			)
			await this.load()
		},

		/**
		 * Make the PDF copy of a filed email again.
		 *
		 * @param {object} row The record.
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		async convertAgain(row) {
			this.retrying = row.uuid
			const result = await retryConversion(row.uuid)
			this.retrying = ''
			if (!result.ok || !result.data.pdfFileRef) {
				showError(
					t(
						'filinq',
						'The email is still not converted. Try again later.',
					),
				)
				return
			}
			showSuccess(t('filinq', 'The PDF copy is ready.'))
			await this.load()
		},

		/**
		 * A date as the user reads it.
		 *
		 * @param {string} value An ISO 8601 date-time.
		 * @return {string} The localised date.
		 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#3-1
		 */
		formatDate(value) {
			return new Date(value).toLocaleString()
		},
	},
}
</script>

<style scoped>
.email-ingestion__bar {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: flex-end;
	margin-block-end: 12px;
}

.email-ingestion__filter {
	min-width: 220px;
}

.email-ingestion__muted,
.email-ingestion__reason {
	color: var(--color-text-maxcontrast);
}

.email-ingestion__reason {
	margin: 4px 0 0;
	font-size: 0.9em;
}

.email-ingestion__thread {
	margin-inline-start: 8px;
}
</style>
