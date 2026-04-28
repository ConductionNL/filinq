<script setup>
import { translate as t } from '@nextcloud/l10n'
import { myDocumentsStore } from '../../store/store.js'
</script>

<template>
	<div class="my-documents-wrapper">
		<!-- Breadcrumb navigation -->
		<div v-if="breadcrumbs.length > 1" class="my-documents-breadcrumbs">
			<span
				v-for="(crumb, index) in breadcrumbs"
				:key="crumb.path"
				class="my-documents-breadcrumb">
				<span
					class="my-documents-breadcrumb__link"
					:class="{ 'my-documents-breadcrumb__link--active': index === breadcrumbs.length - 1 }"
					@click="navigateToBreadcrumb(crumb)">
					{{ crumb.name }}
				</span>
				<ChevronRight
					v-if="index < breadcrumbs.length - 1"
					:size="16"
					class="my-documents-breadcrumb__separator" />
			</span>
		</div>

		<!-- Search bar -->
		<div class="my-documents-toolbar">
			<div class="my-documents-search">
				<Magnify :size="18" class="my-documents-search__icon" />
				<input
					v-model="searchQuery"
					type="text"
					class="my-documents-search__input"
					:placeholder="t('docudesk', 'Search by name')">
			</div>
		</div>

		<CnIndexPage
			ref="indexPage"
			:title="t('docudesk', 'Documents')"
			:show-title="true"
			:objects="paginatedDocuments"
			:columns="tableColumns"
			:pagination="paginationData"
			:loading="myDocumentsStore.loading"
			:selectable="false"
			:show-edit-action="false"
			:show-copy-action="false"
			:show-delete-action="false"
			:show-mass-import="false"
			:show-mass-export="false"
			:show-mass-copy="false"
			:show-mass-delete="false"
			:show-add="false"
			:show-view-toggle="true"
			:view-mode="viewMode"
			row-key="fileId"
			:empty-text="emptyContentName"
			:refreshing="isRefreshing"
			@refresh="handleRefresh"
			@page-changed="onPageChanged"
			@page-size-changed="onPageSizeChanged"
			@update:view-mode="onViewModeChange">

		<!-- Name with file icon (clickable for folders) -->
		<template #column-fileName="{ row }">
			<div
				class="my-documents-name"
				:class="{ 'my-documents-name--clickable': row.isFolder }"
				@click="handleNameClick(row)">
				<component :is="iconFor(row)" :size="20" class="my-documents-name__icon" />
				<span>{{ displayName(row) }}</span>
			</div>
		</template>

		<!-- Type badge (Concept / Geanonimiseerd) -->
		<template #column-kind="{ row }">
			<CnStatusBadge
				:label="kindLabel(row)"
				:color-map="kindColorMap" />
		</template>

		<!-- Status (placeholder: "Niet gecontroleerd") -->
		<template #column-status="{ row }">
			<span class="my-documents-status">
				<EyeOffOutline :size="16" class="my-documents-status__icon" />
				{{ statusLabel(row) }}
			</span>
		</template>

		<!-- Date -->
		<template #column-modified="{ row }">
			{{ formatDate(row.modified) }}
		</template>

		<!-- Size -->
		<template #column-fileSize="{ row }">
			{{ formatSize(row.fileSize) }}
		</template>

		<!-- Card view -->
		<template #card="{ object }">
			<div class="my-documents-card">
				<div class="my-documents-card__icon">
					<component :is="iconFor(object)" :size="36" />
				</div>
				<div class="my-documents-card__title">
					{{ displayName(object) }}
				</div>
				<CnStatusBadge
					:label="kindLabel(object)"
					:color-map="kindColorMap" />
				<div class="my-documents-card__meta">
					<span>{{ formatDate(object.modified) }}</span>
					<span>{{ formatSize(object.fileSize) }}</span>
				</div>
			</div>
		</template>

		<!-- Row actions (kebab menu) -->
		<template #row-actions="{ row }">
			<NcActions>
				<template #icon>
					<DotsHorizontal :size="20" />
				</template>
				<NcActionButton close-after-click @click="openFile(row)">
					<template #icon>
						<Eye :size="20" />
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
	</CnIndexPage>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import { CnIndexPage, CnStatusBadge } from '@conduction/nextcloud-vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

export default {
	name: 'MyDocumentsIndex',
	components: {
		CnIndexPage,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		DotsHorizontal,
		Eye,
		EyeOffOutline,
		Download,
		Magnify,
		ChevronRight,
		FolderOutline,
		FilePdfBox,
		FileWordBox,
		FileDocumentOutline,
	},
	data() {
		return {
			isRefreshing: false,
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
		breadcrumbs() {
			return myDocumentsStore.breadcrumbs
		},
		tableColumns() {
			return [
				{ key: 'fileName', label: t('docudesk', 'Name'), sortable: true },
				{ key: 'kind', label: t('docudesk', 'Kind'), sortable: true },
				{ key: 'status', label: t('docudesk', 'Status'), sortable: false },
				{ key: 'modified', label: t('docudesk', 'Date'), sortable: true },
				{ key: 'fileSize', label: t('docudesk', 'Size'), sortable: true },
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
		 * Re-fetch the document list via the refresh button in CnIndexPage.
		 * Shows a spinner on the refresh icon while in flight.
		 */
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await myDocumentsStore.fetchDocuments()
			} finally {
				this.isRefreshing = false
			}
		},
		/**
		 * Handle click on file/folder name.
		 * For folders: navigate into the folder.
		 * For files: do nothing (row actions handle file operations).
		 */
		handleNameClick(row) {
			if (row.isFolder) {
				myDocumentsStore.openFolder(row.fileName)
				this.currentPage = 1
			}
		},
		/**
		 * Navigate to a breadcrumb path.
		 */
		async navigateToBreadcrumb(crumb) {
			await myDocumentsStore.fetchDocuments(crumb.path)
			this.currentPage = 1
		},
		/** Pagination: track which page is active. */
		onPageChanged(page) {
			this.currentPage = page
		},
		/** Pagination: apply new page size and reset to first page. */
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},
		/** Toggle between 'table' and 'cards' (Tegels/Lijst in the design). */
		onViewModeChange(mode) {
			this.viewMode = mode
		},
		/**
		 * Open the file in the Nextcloud Files app in a new tab.
		 * Triggered only from the kebab-menu action — row clicks do nothing.
		 */
		openFile(row) {
			if (!row || !row.fileId) return
			window.open(generateUrl(`/f/${row.fileId}`), '_blank')
		},
		/** Download the file via the classic Files app download endpoint. */
		downloadFile(row) {
			if (!row || !row.fileId) return
			window.open(generateUrl(`/apps/files/ajax/download.php?dir=/&files=${encodeURIComponent(row.fileName)}&downloadStartSecret=&ocRequest=true`), '_blank')
		},
		/**
		 * Pick an icon component name based on the file's MIME type / extension.
		 * Returned as a string because the components are registered via `components`.
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
		/** Strip the file extension for cleaner display (folders show full name). */
		displayName(row) {
			const name = row.fileName || ''
			// Folders/dossiers show full name, files strip extension
			return row.isFolder ? name : name.replace(/\.[^./]+$/, '')
		},
		/** Badge label for the "Soort" column: folder/dossier, original, or anonymized copy. */
		kindLabel(row) {
			if (row.isFolder) return t('docudesk', 'Dossier')
			return row.isAnonymized ? t('docudesk', 'Anonymized') : t('docudesk', 'Concept')
		},
		/** Placeholder status until the app has a real "checked" signal. */
		statusLabel() {
			return t('docudesk', 'Not checked')
		},
		/** Format a timestamp (unix seconds or ISO string) as DD-MM-YYYY. */
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
		/** Human-readable size (e.g. "12 MB") from a raw byte count. */
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

.my-documents-breadcrumbs {
	display: flex;
	align-items: center;
	gap: 4px;
	padding: 12px 0;
	font-size: 14px;
	color: var(--color-text-maxcontrast);
}

.my-documents-breadcrumb {
	display: flex;
	align-items: center;
	gap: 4px;
}

.my-documents-breadcrumb__link {
	cursor: pointer;
	transition: color 0.15s ease;
}

.my-documents-breadcrumb__link:hover {
	color: var(--color-primary-element);
}

.my-documents-breadcrumb__link--active {
	color: var(--color-main-text);
	font-weight: 600;
	cursor: default;
}

.my-documents-breadcrumb__link--active:hover {
	color: var(--color-main-text);
}

.my-documents-breadcrumb__separator {
	color: var(--color-text-maxcontrast);
}

.my-documents-toolbar {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 16px;
	margin-bottom: 16px;
}

.my-documents-search {
	position: relative;
	display: flex;
	align-items: center;
	max-width: 360px;
	flex: 1;
}

.my-documents-search__icon {
	position: absolute;
	left: 14px;
	color: var(--color-text-maxcontrast);
	pointer-events: none;
}

.my-documents-search__input {
	width: 100%;
	padding: 10px 14px 10px 40px;
	border: 1px solid var(--color-border);
	border-radius: 999px;
	background: var(--color-main-background);
	font-size: 14px;
}

.my-documents-search__input:focus {
	outline: none;
	border-color: var(--color-primary-element);
}

.my-documents-name {
	display: flex;
	align-items: center;
	gap: 10px;
}

.my-documents-name--clickable {
	cursor: pointer;
}

.my-documents-name--clickable:hover {
	color: var(--color-primary-element);
}

.my-documents-name--clickable:hover .my-documents-name__icon {
	color: var(--color-primary-element);
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

.my-documents-card {
	display: flex;
	flex-direction: column;
	gap: 10px;
	padding: 16px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	background: var(--color-main-background);
	transition: box-shadow 0.15s ease;
}

.my-documents-card:hover {
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.my-documents-card__icon {
	color: var(--color-text-maxcontrast);
}

.my-documents-card__title {
	font-weight: 600;
	word-break: break-word;
}

.my-documents-card__meta {
	display: flex;
	justify-content: space-between;
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	margin-top: auto;
}
</style>
