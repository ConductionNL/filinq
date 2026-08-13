<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/specs/document-versions/spec.md
-->

<template>
	<div class="versions-view">
		<div class="versions-view__header">
			<h2>{{ t('docudesk', 'Versions') }}</h2>
			<p class="versions-view__subtitle">
				{{
					t(
						'docudesk',
						'File versions of this document, read from Nextcloud. Open, download, restore, or compare a version.',
					)
				}}
			</p>
		</div>

		<NcNoteCard
			v-if="unavailable"
			type="info"
			data-testid="versions-unavailable">
			{{ t('docudesk', 'File versions are not available on this instance') }}
		</NcNoteCard>

		<NcNoteCard v-else-if="error" type="error">
			{{ error }}
		</NcNoteCard>

		<NcLoadingIcon v-if="loading" :size="32" />

		<CnDataTable
			v-else-if="!unavailable"
			:columns="columns"
			:rows="rows"
			:table-label="t('docudesk', 'Versions')"
			data-testid="versions-table">
			<template #actions="{ row }">
				<NcButton variant="tertiary" @click="download(row)">
					{{ t('docudesk', 'Download') }}
				</NcButton>
				<NcButton
					v-if="!row.isCurrent"
					variant="tertiary"
					data-testid="version-restore"
					@click="promptRestore(row)">
					{{ t('docudesk', 'Restore') }}
				</NcButton>
				<NcButton
					v-if="canCompare(row)"
					variant="tertiary"
					data-testid="version-compare"
					@click="compare(row)">
					{{ t('docudesk', 'Compare with current') }}
				</NcButton>
			</template>
		</CnDataTable>

		<ConfirmRestoreVersionDialog
			v-if="restoreTarget"
			:version-number="restoreTarget.timestamp"
			@confirm="confirmRestore"
			@cancel="restoreTarget = null" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import {
	CnDataTable,
	NcButton,
	NcLoadingIcon,
	NcNoteCard,
} from '@conduction/nextcloud-vue'
import ConfirmRestoreVersionDialog from '../../dialogs/ConfirmRestoreVersionDialog.vue'
import {
	listVersions,
	versionDownloadUrl,
	restoreVersion,
} from '../../services/versionService.js'

export default {
	name: 'VersionsView',
	components: {
		CnDataTable,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		ConfirmRestoreVersionDialog,
	},
	props: {
		// Optional preselected document (else read from ?fileId=).
		initialFileId: { type: [String, Number], default: '' },
		// Whether the document's versions are text-extractable (enables compare).
		textExtractable: { type: Boolean, default: true },
	},
	data() {
		return {
			fileId: Number(
				this.initialFileId || (this.$route && this.$route.query.fileId) || 0,
			),
			versions: [],
			loading: false,
			error: '',
			unavailable: false,
			restoreTarget: null,
		}
	},
	computed: {
		/**
		 * Table columns for the version list.
		 *
		 * @return {Array} The column definitions.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		columns() {
			return [
				{ key: 'when', label: t('docudesk', 'When') },
				{ key: 'author', label: t('docudesk', 'Author') },
				{ key: 'size', label: t('docudesk', 'Size') },
				{ key: 'current', label: t('docudesk', 'Current') },
			]
		},
		/**
		 * Rows for the CnDataTable, newest first, current marked.
		 *
		 * @return {Array} The rows.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		rows() {
			return this.versions.map((v) => ({
				...v,
				when: this.formatTimestamp(v.timestamp),
				current: v.isCurrent ? t('docudesk', 'Current version') : '',
				size: this.formatBytes(v.size),
			}))
		},
	},
	mounted() {
		if (this.fileId > 0) {
			this.load()
		}
	},
	methods: {
		t,
		/**
		 * Load the version list for the current document.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		async load() {
			this.loading = true
			this.error = ''
			this.unavailable = false
			try {
				const data = await listVersions(this.fileId)
				this.versions = data.versions || []
			} catch (e) {
				const reason =
					e && e.response && e.response.data && e.response.data.reason
				if (reason === 'versions-unavailable') {
					this.unavailable = true
				} else {
					this.error =
						(e && e.response && e.response.data && e.response.data.error)
						|| t('docudesk', 'Could not list versions')
				}
			} finally {
				this.loading = false
			}
		},
		/**
		 * Whether the compare action is offered for a row (text-extractable only).
		 *
		 * @param {object} row The version row.
		 * @return {boolean} True when compare is available.
		 * @spec openspec/specs/document-versions/spec.md
		 */
		canCompare(row) {
			return this.textExtractable && !row.isCurrent
		},
		/**
		 * Open/download a version's bytes.
		 *
		 * @param {object} row The version row.
		 * @return {void}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		download(row) {
			const ts = row.isCurrent ? 0 : row.timestamp
			window.open(versionDownloadUrl(this.fileId, ts), '_blank')
		},
		/**
		 * Prompt to restore a prior version.
		 *
		 * @param {object} row The version row.
		 * @return {void}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		promptRestore(row) {
			this.restoreTarget = row
		},
		/**
		 * Confirm and perform the restore, then reload.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		async confirmRestore() {
			const target = this.restoreTarget
			this.restoreTarget = null
			if (!target) return
			try {
				await restoreVersion(this.fileId, target.timestamp)
				await this.load()
			} catch (e) {
				this.error =
					(e && e.response && e.response.data && e.response.data.error)
					|| t('docudesk', 'Could not restore version')
			}
		},
		/**
		 * Hand the version to the existing comparison flow (fileId + timestamp).
		 *
		 * @param {object} row The version row.
		 * @return {void}
		 * @spec openspec/specs/document-versions/spec.md
		 */
		compare(row) {
			this.$router.push({
				name: 'Comparison',
				query: {
					left: String(this.fileId),
					leftVersion: String(row.timestamp),
					right: String(this.fileId),
				},
			})
		},
		/**
		 * Format a UNIX timestamp for display.
		 *
		 * @param {number} ts The timestamp (seconds).
		 * @return {string} The formatted date.
		 */
		formatTimestamp(ts) {
			if (!ts) return ''
			return new Date(Number(ts) * 1000).toLocaleString()
		},
		/**
		 * Human-readable byte size.
		 *
		 * @param {number} bytes The size in bytes.
		 * @return {string} The formatted size.
		 */
		formatBytes(bytes) {
			const n = Number(bytes || 0)
			if (n < 1024) return `${n} B`
			if (n < 1048576) return `${(n / 1024).toFixed(1)} KB`
			return `${(n / 1048576).toFixed(1)} MB`
		},
	},
}
</script>

<style scoped>
.versions-view {
	padding: var(--default-grid-baseline, 16px);
}

.versions-view__subtitle {
	color: var(--color-text-maxcontrast);
}
</style>
