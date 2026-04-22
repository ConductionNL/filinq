<script setup>
import { translate as t } from '@nextcloud/l10n'
import { myDocumentsStore } from '../../store/store.js'
</script>

<template>
	<CnIndexPage
		ref="indexPage"
		:title="t('docudesk', 'Documents')"
		:show-title="true"
		:objects="filteredDocuments"
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
		<!-- Search bar above the table -->
		<template #above-table>
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
		</template>

		<!-- Name with file icon -->
		<template #column-fileName="{ row }">
			<div class="my-documents-name">
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
				[t('docudesk', 'Concept')]: 'warning',
				[t('docudesk', 'Anonymized')]: 'success',
			},
		}
	},
	computed: {
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
		async handleRefresh() {
			this.isRefreshing = true
			try {
				await myDocumentsStore.fetchDocuments()
			} finally {
				this.isRefreshing = false
			}
		},
		onPageChanged(page) {
			this.currentPage = page
		},
		onPageSizeChanged(size) {
			this.pageSize = size
			this.currentPage = 1
		},
		onViewModeChange(mode) {
			this.viewMode = mode
		},
		openFile(row) {
			if (!row || !row.fileId) return
			window.open(generateUrl(`/f/${row.fileId}`), '_blank')
		},
		downloadFile(row) {
			if (!row || !row.fileId) return
			window.open(generateUrl(`/apps/files/ajax/download.php?dir=/&files=${encodeURIComponent(row.fileName)}&downloadStartSecret=&ocRequest=true`), '_blank')
		},
		iconFor(row) {
			const mime = row.mimeType || ''
			const name = (row.fileName || '').toLowerCase()
			if (mime === 'httpd/unix-directory' || name.endsWith('/')) return 'FolderOutline'
			if (mime.includes('pdf') || name.endsWith('.pdf')) return 'FilePdfBox'
			if (mime.includes('word') || name.match(/\.(docx?|odt)$/)) return 'FileWordBox'
			return 'FileDocumentOutline'
		},
		displayName(row) {
			const name = row.fileName || ''
			return name.replace(/\.[^./]+$/, '')
		},
		kindLabel(row) {
			return row.isAnonymized ? t('docudesk', 'Anonymized') : t('docudesk', 'Concept')
		},
		statusLabel() {
			return t('docudesk', 'Not checked')
		},
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

.my-documents-name__icon {
	color: var(--color-text-maxcontrast);
	flex-shrink: 0;
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
