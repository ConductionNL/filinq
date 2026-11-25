<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The mail archive import page: pick an MBOX, PST or ZIP file in Files, map the
archive's folders to dossiers, and follow the import while cron drains it,
with the counts and every message that failed. The user sees their own
imports only, like the api/mail-archive-imports routes behind it.

@spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
-->

<template>
	<div>
		<CnIndexPage
			:title="t('filinq', 'Mail archive import')"
			:description="
				t(
					'filinq',
					'Import a whole mailbox archive at once: an MBOX file, an Outlook PST or a ZIP of .eml files. Mails in a folder you map to a dossier are filed there; the rest go to the intake inbox. A large archive is imported a few hundred mails at a time, so this page shows how far it got.',
				)
			"
			:showTitle="true"
			:objects="rows"
			:columns="tableColumns"
			:loading="loading"
			v-bind="$options.listOnlyIndexPage"
			:showAdd="false"
			rowKey="uuid"
			:emptyText="
				t(
					'filinq',
					'No imports yet. Pick a mail archive in your files to start one.',
				)
			"
			:refreshing="refreshing"
			data-testid="mail-archive-import-index"
			@refresh="refresh">
			<template #below-header>
				<div class="mail-archive-import__bar">
					<NcButton
						variant="primary"
						:disabled="starting"
						data-testid="mail-archive-import-pick"
						@click="pickArchive">
						{{ t('filinq', 'Import a mail archive') }}
					</NcButton>
				</div>
			</template>

			<template #column-fileName="{ row }">
				<span>{{ row.fileName }}</span>
				<span v-if="row.format" class="mail-archive-import__muted">
					{{ formatLabel(row.format) }}
				</span>
			</template>

			<template #column-status="{ row }">
				<CnStatusBadge
					:label="statusLabel(row.status)"
					:colorMap="colorMap"
					:data-testid="'mail-archive-status-' + row.status" />
				<p
					v-if="row.statusReason"
					class="mail-archive-import__reason"
					data-testid="mail-archive-status-reason">
					{{ row.statusReason }}
				</p>
			</template>

			<template #column-progress="{ row }">
				<progress
					class="mail-archive-import__progress"
					max="100"
					:value="progressPercent(row.counts)"
					:aria-label="t('filinq', 'Import progress')" />
				<p
					class="mail-archive-import__counts"
					data-testid="mail-archive-counts">
					{{ countsText(row.counts) }}
				</p>
				<details
					v-if="(row.failures || []).length > 0"
					data-testid="mail-archive-failures">
					<summary>
						{{
							t('filinq', 'Failed messages ({count})', {
								count: row.failures.length,
							})
						}}
					</summary>
					<ul class="mail-archive-import__failures">
						<li
							v-for="(failure, index) in row.failures"
							:key="index">
							<strong>{{ failure.archiveFolder }}/{{ failure.name }}</strong>
							<span v-if="failure.messageId" class="mail-archive-import__muted">
								&lt;{{ failure.messageId }}&gt;
							</span>
							{{ failureText(failure.reason) }}
						</li>
					</ul>
				</details>
			</template>

			<template #row-actions="{ row }">
				<NcButton
					v-if="row.status === 'mapping'"
					variant="primary"
					data-testid="mail-archive-map"
					@click="openMapping(row)">
					{{ t('filinq', 'Map folders') }}
				</NcButton>
				<NcButton
					v-if="canCancel(row)"
					variant="secondary"
					:disabled="cancelling === row.uuid"
					data-testid="mail-archive-cancel"
					@click="cancel(row)">
					{{ t('filinq', 'Cancel import') }}
				</NcButton>
			</template>
		</CnIndexPage>

		<MailArchiveMappingDialog
			v-if="mapping"
			:archiveImport="mapping"
			:dossierOptions="dossierOptions"
			:saving="saving"
			:error="mappingError"
			@cancel="closeMapping"
			@confirm="confirmMapping" />
	</div>
</template>

<script>
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import { getFilePickerBuilder, showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { NcButton } from '@nextcloud/vue'
import MailArchiveMappingDialog from '../../dialogs/MailArchiveMappingDialog.vue'
import { LIST_ONLY_INDEX_PAGE } from '../../constants/indexPage.js'
import {
	canCancel,
	cancelImport,
	countsText,
	failureText,
	formatLabel,
	listImports,
	loadDossierOptions,
	mapFolders,
	progressPercent,
	startImport,
	statusColorMap,
	statusLabel,
} from '../../services/mailArchiveImport.js'

export default {
	name: 'MailArchiveImport',
	listOnlyIndexPage: LIST_ONLY_INDEX_PAGE,
	components: {
		CnIndexPage,
		CnStatusBadge,
		MailArchiveMappingDialog,
		NcButton,
	},

	data() {
		return {
			rows: [],
			loading: true,
			refreshing: false,
			starting: false,
			cancelling: '',
			mapping: null,
			mappingError: '',
			saving: false,
			dossierOptions: [],
			colorMap: statusColorMap(),
		}
	},

	computed: {
		/**
		 * The columns of the import table.
		 *
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		tableColumns() {
			return [
				{ key: 'fileName', label: t('filinq', 'Archive') },
				{ key: 'status', label: t('filinq', 'Status') },
				{ key: 'progress', label: t('filinq', 'Progress') },
			]
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		canCancel,
		countsText,
		failureText,
		formatLabel,
		progressPercent,
		statusLabel,

		/**
		 * Load the user's imports.
		 *
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		async load() {
			const result = await listImports()
			this.loading = false
			if (!result.ok) {
				showError(t('filinq', 'The imports could not be loaded.'))
				return
			}
			this.rows = result.data.results || []
		},

		/**
		 * Reload the list on the refresh action.
		 *
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		async refresh() {
			this.refreshing = true
			await this.load()
			this.refreshing = false
		},

		/**
		 * Pick an archive in Files and start an import of it.
		 *
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		async pickArchive() {
			let nodes
			try {
				nodes = await getFilePickerBuilder(
					t('filinq', 'Pick a mail archive'),
				)
					.setMultiSelect(false)
					.allowDirectories(false)
					.build()
					.pickNodes()
			} catch {
				return
			}
			const fileId = nodes?.[0]?.fileid
			if (!fileId) {
				return
			}
			this.starting = true
			const result = await startImport(fileId)
			this.starting = false
			if (!result.ok) {
				showError(t('filinq', 'The import could not be started.'))
				return
			}
			await this.load()
			if (result.data.status === 'mapping') {
				await this.openMapping(result.data)
			} else if (result.data.status === 'failed') {
				showError(result.data.statusReason || t('filinq', 'The import failed.'))
			}
		},

		/**
		 * Open the folder mapping of an import.
		 *
		 * @param {object} row The import in mapping.
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		async openMapping(row) {
			this.mappingError = ''
			if (this.dossierOptions.length === 0) {
				this.dossierOptions = await loadDossierOptions()
			}
			this.mapping = row
		},

		closeMapping() {
			this.mapping = null
			this.mappingError = ''
		},

		/**
		 * Send the mapping and start importing.
		 *
		 * @param {Array<object>} folderMap Rows of { archiveFolder, dossier }.
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		async confirmMapping(folderMap) {
			this.saving = true
			const result = await mapFolders(this.mapping.uuid, folderMap)
			this.saving = false
			if (!result.ok) {
				this.mappingError
					= result.status === 403
						? t('filinq', 'You may not file into one of the dossiers you picked.')
						: t('filinq', 'The mapping could not be saved.')
				return
			}
			this.closeMapping()
			showSuccess(
				t(
					'filinq',
					'The import has started. It runs in the background; come back to this page to follow it.',
				),
			)
			await this.load()
		},

		/**
		 * Cancel an import; what was filed stays filed.
		 *
		 * @param {object} row The import.
		 * @spec openspec/changes/archive/2026-11-25-intake-mailbox-archive-import/tasks.md#4-1
		 */
		async cancel(row) {
			this.cancelling = row.uuid
			const result = await cancelImport(row.uuid)
			this.cancelling = ''
			if (!result.ok) {
				showError(t('filinq', 'The import could not be cancelled.'))
				return
			}
			await this.load()
		},
	},
}
</script>

<style scoped>
.mail-archive-import__bar {
	display: flex;
	gap: calc(var(--default-grid-baseline) * 3);
	margin-block-end: calc(var(--default-grid-baseline) * 3);
}

.mail-archive-import__muted,
.mail-archive-import__reason {
	color: var(--color-text-maxcontrast);
}

.mail-archive-import__muted {
	margin-inline-start: calc(var(--default-grid-baseline) * 2);
}

.mail-archive-import__reason,
.mail-archive-import__counts {
	margin: var(--default-grid-baseline) 0 0;
}

.mail-archive-import__progress {
	width: 100%;
	accent-color: var(--color-primary-element);
}

.mail-archive-import__failures {
	margin: var(--default-grid-baseline) 0 0;
	padding-inline-start: calc(var(--default-grid-baseline) * 4);
}
</style>
