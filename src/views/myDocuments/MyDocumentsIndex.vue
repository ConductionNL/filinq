<script setup>
import { translate as t } from '@nextcloud/l10n'
import { myDocumentsStore, fileViewerStore } from '../../store/store.js'
</script>

<template>
	<div class="my-documents-wrapper">
		<FileViewerPage v-if="fileViewerStore.currentFile" />

		<template v-else>
			<DdPageHeader :title="t('docudesk', 'Documents')" />

			<DdIndexPage
				:objects="paginatedDocuments"
				:columns="tableColumns"
				:pagination="paginationData"
				:loading="myDocumentsStore.loading"
				:view-mode="viewMode"
				row-key="fileId"
				:empty-text="emptyContentName"
				:table-label="t('docudesk', 'List')"
				:cards-label="t('docudesk', 'Tiles')"
				:view-toggle-label="t('docudesk', 'View mode')"
				:items-per-page-label="t('docudesk', 'Items per page:')"
				:page-info-format="t('docudesk', 'Page {current} of {total}')"
				:first-label="t('docudesk', 'First')"
				:previous-label="t('docudesk', 'Previous')"
				:next-label="t('docudesk', 'Next')"
				:last-label="t('docudesk', 'Last')"
				@page-changed="onPageChanged"
				@page-size-changed="onPageSizeChanged"
				@update:view-mode="onViewModeChange"
				@row-click="onRowClick">
				<template #header-actions>
					<DdSearchBar
						v-model="searchQuery"
						class="my-documents-search"
						:placeholder="t('docudesk', 'Search by name')"
						:clear-label="t('docudesk', 'Clear search')" />
				</template>

				<template #column-fileName="{ row }">
					<div class="my-documents-name">
						<component :is="iconFor(row)" :size="20" class="my-documents-name__icon" />
						<span>{{ displayName(row) }}</span>
					</div>
				</template>

				<template #column-kind="{ row }">
					<CnStatusBadge
						:label="kindLabel(row)"
						:color-map="kindColorMap" />
				</template>

				<template #column-status="{ row }">
					<span class="my-documents-status">
						<EyeOffOutline :size="16" class="my-documents-status__icon" />
						{{ statusLabel(row) }}
					</span>
				</template>

				<template #column-modified="{ row }">
					{{ formatDate(row.modified) }}
				</template>

				<template #column-fileSize="{ row }">
					{{ formatSize(row.fileSize) }}
				</template>

				<template #card="{ object }">
					<DdDocumentCard :item="object" @click="onRowClick" />
				</template>

				<template #actions-header>
					<FilterOutline
						:size="20"
						class="my-documents-filter-icon"
						:title="t('docudesk', 'Filter')"
						@click="onFilterClick" />
				</template>

				<template #row-actions="{ row }">
					<NcActions class="my-documents-row-actions">
						<template #icon>
							<DotsHorizontal :size="24" />
						</template>
						<NcActionButton v-if="!row.isFolder" close-after-click @click="viewFile(row)">
							<template #icon>
								<Eye :size="20" />
							</template>
							{{ t('docudesk', 'View') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="openFile(row)">
							<template #icon>
								<OpenInNew :size="20" />
							</template>
							{{ t('docudesk', 'Open in Files') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="downloadFile(row)">
							<template #icon>
								<Download :size="20" />
							</template>
							{{ t('docudesk', 'Download') }}
						</NcActionButton>
					</NcActions>
				</template>
			</DdIndexPage>
		</template>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import DdSearchBar from '../../components/DdSearchBar.vue'
import DdPageHeader from '../../components/DdPageHeader.vue'
import DdIndexPage from '../../components/DdIndexPage.vue'
import DdDocumentCard from '../../components/DdDocumentCard.vue'
import FileViewerPage from '../fileViewer/FileViewerPage.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FilterOutline from 'vue-material-design-icons/FilterOutline.vue'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

export default {
	name: 'MyDocumentsIndex',
	components: {
		DdIndexPage,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		DdSearchBar,
		DdPageHeader,
		DdDocumentCard,
		FileViewerPage,
		DotsHorizontal,
		Eye,
		OpenInNew,
		EyeOffOutline,
		Download,
		FilterOutline,
		FolderOutline,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
	},
	data() {
		return {
			currentPage: 1,
			pageSize: 20,
			searchQuery: '',
			viewMode: 'table',
			kindColorMap: {
				[t('docudesk', 'Dossier')]: 'info',
				[t('docudesk', 'Concept')]: 'warning',
				[t('docudesk', 'Anonymized')]: 'success',
			},
		}
	},
	computed: {
		tableColumns() {
			return [
				{ key: 'fileName', label: t('docudesk', 'Name') },
				{ key: 'kind', label: t('docudesk', 'Kind') },
				{ key: 'status', label: t('docudesk', 'Status') },
				{ key: 'modified', label: t('docudesk', 'Date') },
				{ key: 'fileSize', label: t('docudesk', 'Size') },
			]
		},
		filteredDocuments() {
			const query = this.searchQuery.trim().toLowerCase()
			const docs = myDocumentsStore.documents
			if (!query) return docs
			return docs.filter((d) => (d.fileName || '').toLowerCase().includes(query))
		},
		paginatedDocuments() {
			const start = (this.currentPage - 1) * this.pageSize
			const end = start + this.pageSize
			return this.filteredDocuments.slice(start, end)
		},
		paginationData() {
			const total = this.filteredDocuments.length
			const pages = Math.ceil(total / this.pageSize) || 1
			return { page: this.currentPage, pages, total, limit: this.pageSize }
		},
		emptyContentName() {
			if (myDocumentsStore.error) {
				return myDocumentsStore.error
			}
			return t('docudesk', 'No documents found')
		},
	},
	mounted() {
		myDocumentsStore.fetchDocuments()
	},
	methods: {
		/**
		 * Click handler for the filter icon in the table header.
		 * Placeholder until a filter panel is wired up.
		 */
		onFilterClick() {
			// TODO: open filter panel
		},
		/**
		 * Click handler for the entire table row / card.
		 * Files open directly in the in-app viewer. Folders (dossiers) are flat
		 * by design, so opening one navigates into it and immediately opens its
		 * first file — the list view inside a dossier is intentionally skipped.
		 *
		 * @param {object} row Document row.
		 */
		async onRowClick(row) {
			if (!row) return
			if (row.isFolder) {
				await myDocumentsStore.openFolder(row.fileName)
				this.currentPage = 1
				const firstFile = myDocumentsStore.documents.find((d) => !d.isFolder)
				if (firstFile) {
					this.viewFile(firstFile)
				}
				return
			}
			this.viewFile(row)
		},
		/**
		 * Pagination: track which page is active.
		 *
		 * @param {number} page New active page number (1-based).
		 */
		onPageChanged(page) {
			this.currentPage = page
		},
		/**
		 * Pagination: apply new page size and reset to first page.
		 *
		 * @param {number} size New page size.
		 */
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},
		/**
		 * Toggle between 'table' and 'cards' (Tegels/Lijst in the design).
		 *
		 * @param {string} mode New view mode ('table' or 'cards').
		 */
		onViewModeChange(mode) {
			this.viewMode = mode
		},
		/**
		 * Open the file in the Nextcloud Files app in a new tab.
		 * Triggered only from the kebab-menu action — row clicks do nothing.
		 *
		 * @param {object} row Document row.
		 */
		openFile(row) {
			if (!row || !row.fileId) return
			window.open(generateUrl(`/f/${row.fileId}`), '_blank')
		},
		/**
		 * Preview the file inline using DocuDesk's own file viewer modal
		 * (PDF / docx / text). Folders are ignored.
		 *
		 * @param {object} row Document row from the table.
		 */
		viewFile(row) {
			if (!row || row.isFolder) return
			const path = `${myDocumentsStore.currentPath}/${row.fileName}`
			fileViewerStore.open({
				fileId: row.fileId,
				fileName: row.fileName,
				mimeType: row.mimeType,
				path,
			})
		},
		/**
		 * Download the file via the classic Files app download endpoint.
		 *
		 * @param {object} row Document row.
		 */
		downloadFile(row) {
			if (!row || !row.fileId) return
			window.open(generateUrl(`/apps/files/ajax/download.php?dir=/&files=${encodeURIComponent(row.fileName)}&downloadStartSecret=&ocRequest=true`), '_blank')
		},
		/**
		 * Pick an icon component name based on the file's MIME type / extension.
		 *
		 * @param {object} row Document row.
		 * @return {string} Component name.
		 */
		iconFor(row) {
			const mime = row.mimeType || ''
			const name = (row.fileName || '').toLowerCase()
			if (mime === 'httpd/unix-directory' || name.endsWith('/')) return 'FolderOutline'
			if (mime.includes('pdf') || name.endsWith('.pdf')) return 'FilePdfBox'
			if (mime.includes('word') || name.match(/\.(docx?|odt)$/)) return 'FileWordBox'
			return 'FileDocumentOutline'
		},
		/**
		 * Strip the file extension for cleaner display (folders show full name).
		 *
		 * @param {object} row Document row.
		 * @return {string} Display name.
		 */
		displayName(row) {
			const name = row.fileName || ''
			return row.isFolder ? name : name.replace(/\.[^./]+$/, '')
		},
		/**
		 * Badge label for the "Soort" column: folder/dossier, original, or anonymized copy.
		 *
		 * @param {object} row Document row.
		 * @return {string} Badge label.
		 */
		kindLabel(row) {
			if (row.isFolder) return t('docudesk', 'Dossier')
			return row.isAnonymized ? t('docudesk', 'Anonymized') : t('docudesk', 'Concept')
		},
		/** Placeholder status until the app has a real "checked" signal. */
		statusLabel() {
			return t('docudesk', 'Not checked')
		},
		/**
		 * Format a timestamp (unix seconds or ISO string) as DD-MM-YYYY.
		 *
		 * @param {number|string} ts Timestamp.
		 * @return {string} Formatted date.
		 */
		formatDate(ts) {
			if (!ts) return '-'
			try {
				const d = typeof ts === 'number' ? new Date(ts * 1000) : new Date(ts)
				const dd = String(d.getDate()).padStart(2, '0')
				const mm = String(d.getMonth() + 1).padStart(2, '0')
				const yyyy = d.getFullYear()
				return `${dd}-${mm}-${yyyy}`
			} catch (e) {
				return String(ts)
			}
		},
		/**
		 * Human-readable size (e.g. "12 MB") from a raw byte count.
		 *
		 * @param {number} bytes Raw size in bytes.
		 * @return {string} Formatted size.
		 */
		formatSize(bytes) {
			if (bytes === null || bytes === undefined) return '-'
			const units = ['B', 'KB', 'MB', 'GB']
			let i = 0
			let size = Number(bytes)
			while (size >= 1024 && i < units.length - 1) {
				size /= 1024
				i++
			}
			return `${size.toFixed(size < 10 && i > 0 ? 1 : 0)} ${units[i]}`
		},
	},
}
</script>

<style scoped>
.my-documents-wrapper {
	padding: 0;
}

.my-documents-search {
	max-width: 280px;
}

.my-documents-name {
	display: flex;
	align-items: center;
	gap: var(--dd-my-documents-name-gap, 16px);
}

.my-documents-name__icon {
	color: var(--color-text-maxcontrast);
	flex-shrink: 0;
	transition: color 0.15s ease;
}

.my-documents-status {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
}

.my-documents-status__icon {
	color: var(--color-text-maxcontrast);
}

.my-documents-filter-icon {
	color: var(--color-text-maxcontrast);
	cursor: pointer;
}

/* Compact kebab button in the table's row-actions cell.
   NcActions sizes itself off Nextcloud's `--default-clickable-area` token. */
.my-documents-row-actions {
	--default-clickable-area: 32px;
}

</style>
