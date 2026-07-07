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
				:selectable="bulkSelect"
				:selected-keys="selectedIds"
				:select-all-label="t('docudesk', 'Select all')"
				@page-changed="onPageChanged"
				@page-size-changed="onPageSizeChanged"
				@update:view-mode="onViewModeChange"
				@row-click="onRowClick"
				@toggle-select="onToggleSelect"
				@toggle-select-all="onToggleSelectAll">
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
					<DdDocumentCard
						:item="object"
						:selectable="bulkSelect"
						:selected="selectedIds.includes(object.fileId)"
						@click="onRowClick"
						@toggle-select="onToggleSelect" />
				</template>

				<template #actions-header>
					<NcActions
						class="my-documents-options"
						force-menu
						:aria-label="t('docudesk', 'Options')">
						<template #icon>
							<component
								:is="bulkSelect ? 'Cog' : 'FilterOutline'"
								:size="20"
								class="my-documents-filter-icon"
								:title="bulkSelect ? t('docudesk', 'Options') : t('docudesk', 'Filter')" />
						</template>
						<NcActionButton close-after-click @click="toggleBulkSelect">
							<template #icon>
								<CheckboxMultipleMarkedOutline :size="20" />
							</template>
							{{ bulkSelect ? t('docudesk', 'Cancel bulk selection') : t('docudesk', 'Bulk selection') }}
						</NcActionButton>
						<NcActionButton
							v-if="bulkSelect && selectedIds.length > 0"
							close-after-click
							@click="bulkDelete">
							<template #icon>
								<Delete :size="20" />
							</template>
							{{ t('docudesk', 'Delete selected ({count})', { count: selectedIds.length }) }}
						</NcActionButton>
					</NcActions>
				</template>

				<template #row-actions="{ row }">
					<NcActions class="my-documents-row-actions">
						<template #icon>
							<DotsHorizontal :size="24" />
						</template>
						<NcActionButton close-after-click @click="onRowClick(row)">
							<template #icon>
								<Eye :size="20" />
							</template>
							{{ t('docudesk', 'Open') }}
						</NcActionButton>
						<NcActionButton v-if="!row.isFolder" close-after-click @click="downloadFile(row)">
							<template #icon>
								<Download :size="20" />
							</template>
							{{ t('docudesk', 'Download') }}
						</NcActionButton>
						<NcActionButton v-if="!row.isFolder" close-after-click @click="validateDocument(row)">
							<template #icon>
								<ShieldCheckOutline :size="20" />
							</template>
							{{ t('docudesk', 'Validate') }}
						</NcActionButton>
						<NcActionButton v-if="!row.isFolder" close-after-click @click="compareDocument(row)">
							<template #icon>
								<Compare :size="20" />
							</template>
							{{ t('docudesk', 'Compare…') }}
						</NcActionButton>
						<NcActionButton v-if="!row.isFolder" close-after-click @click="openVersions(row)">
							<template #icon>
								<History :size="20" />
							</template>
							{{ t('docudesk', 'Versions') }}
						</NcActionButton>
						<NcActionButton close-after-click @click="confirmDelete(row)">
							<template #icon>
								<Delete :size="20" />
							</template>
							{{ t('docudesk', 'Delete') }}
						</NcActionButton>
					</NcActions>
				</template>
			</DdIndexPage>
		</template>

		<ValidationResultModal
			:show="validation.show"
			:loading="validation.loading"
			:error="validation.error"
			:status="validation.status"
			:findings="validation.findings"
			@close="validation.show = false"
			@ocr="onOcrRequested" />
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import DdSearchBar from '../../components/DdSearchBar.vue'
import DdPageHeader from '../../components/DdPageHeader.vue'
import DdIndexPage from '../../components/DdIndexPage.vue'
import DdDocumentCard from '../../components/DdDocumentCard.vue'
import FileViewerPage from '../fileViewer/FileViewerPage.vue'
import ValidationResultModal from '../../modals/ValidationResultModal.vue'
import { validateFile } from '../../services/validationService.js'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import ShieldCheckOutline from 'vue-material-design-icons/ShieldCheckOutline.vue'
import Compare from 'vue-material-design-icons/Compare.vue'
import History from 'vue-material-design-icons/History.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import CheckboxMultipleMarkedOutline from 'vue-material-design-icons/CheckboxMultipleMarkedOutline.vue'
import FilterOutline from 'vue-material-design-icons/FilterOutline.vue'
import FolderOutline from 'vue-material-design-icons/FolderOutline.vue'
import FilePdfBox from 'vue-material-design-icons/FilePdfBox.vue'
import FileWordBox from 'vue-material-design-icons/FileWordBox.vue'
import FileDocumentOutline from 'vue-material-design-icons/FileDocumentOutline.vue'

const VIEW_MODE_STORAGE_KEY = 'docudesk:myDocuments:viewMode'
const VALID_VIEW_MODES = ['table', 'cards']

/**
 * Read the persisted view-mode preference from localStorage.
 * Falls back to 'table' when storage is unavailable or the value is invalid.
 *
 * @return {string} Stored view mode or default 'table'.
 */
function loadPersistedViewMode() {
	try {
		const stored = window.localStorage.getItem(VIEW_MODE_STORAGE_KEY)
		if (stored && VALID_VIEW_MODES.includes(stored)) {
			return stored
		}
	} catch (e) {
		// localStorage can throw in private mode / sandboxed iframes
	}
	return 'table'
}

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
		ValidationResultModal,
		DotsHorizontal,
		Eye,
		EyeOffOutline,
		Download,
		ShieldCheckOutline,
		Compare,
		History,
		Delete,
		Cog,
		CheckboxMultipleMarkedOutline,
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
			viewMode: loadPersistedViewMode(),
			bulkSelect: false,
			selectedIds: [],
			validation: {
				show: false,
				loading: false,
				error: '',
				status: '',
				findings: [],
				fileId: null,
			},
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
	/**
	 * @spec exclude Lifecycle bootstrap (fetch + keyboard listener wiring).
	 */
	mounted() {
		myDocumentsStore.fetchDocuments()
		window.addEventListener('keydown', this.onKeydown)
	},
	beforeDestroy() {
		window.removeEventListener('keydown', this.onKeydown)
	},
	methods: {
		/**
		 * Global keydown handler. Escape cancels bulk-selection mode so the
		 * user can bail out of a bulk action without reaching for the menu.
		 *
		 * @param {KeyboardEvent} e Keyboard event.
		 *
		 * @spec exclude Local bulk-selection UI keyboard handler; no domain or persistence semantics.
		 */
		onKeydown(e) {
			if (e.key === 'Escape' && this.bulkSelect) {
				this.cancelBulkSelect()
			}
		},
		/**
		 * Leave bulk-selection mode and clear any pending selection.
		 *
		 * @spec exclude Local bulk-selection UI state reset; no domain or persistence semantics.
		 */
		cancelBulkSelect() {
			this.bulkSelect = false
			this.selectedIds = []
		},
		/**
		 * Toggle bulk-selection mode on/off. Turning it off clears any
		 * existing selection so the checkboxes don't linger as hidden state.
		 *
		 * @spec exclude Local bulk-selection UI mode toggle; no domain or persistence semantics.
		 */
		toggleBulkSelect() {
			if (this.bulkSelect) {
				this.cancelBulkSelect()
			} else {
				this.bulkSelect = true
			}
		},
		/**
		 * Toggle a single document's selection by fileId.
		 *
		 * @param {object} row Document row.
		 *
		 * @spec exclude Local bulk-selection UI per-row toggle; no domain or persistence semantics.
		 */
		onToggleSelect(row) {
			if (!row || !row.fileId) return
			const idx = this.selectedIds.indexOf(row.fileId)
			if (idx === -1) {
				this.selectedIds = [...this.selectedIds, row.fileId]
			} else {
				this.selectedIds = this.selectedIds.filter((id) => id !== row.fileId)
			}
		},
		/**
		 * Select-all toggle for the table header checkbox. Operates on the
		 * documents visible on the current page: if all are already selected
		 * it clears them, otherwise it adds them to the selection.
		 *
		 * @spec exclude Local bulk-selection UI select-all toggle; no domain or persistence semantics.
		 */
		onToggleSelectAll() {
			const pageIds = this.paginatedDocuments.map((d) => d.fileId)
			const allSelected = pageIds.length > 0 && pageIds.every((id) => this.selectedIds.includes(id))
			if (allSelected) {
				this.selectedIds = this.selectedIds.filter((id) => !pageIds.includes(id))
			} else {
				this.selectedIds = [...new Set([...this.selectedIds, ...pageIds])]
			}
		},
		/**
		 * Confirm and delete every selected document/dossier in one bulk
		 * operation. Dossiers and their contents are removed recursively.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no DocuDesk domain semantics).
		 */
		async bulkDelete() {
			const names = myDocumentsStore.documents
				.filter((d) => this.selectedIds.includes(d.fileId))
				.map((d) => d.fileName)
			if (names.length === 0) return
			// eslint-disable-next-line no-alert
			if (!window.confirm(t('docudesk', 'Delete {count} selected item(s)? Dossiers and the documents inside them will be removed. This cannot be undone.', { count: names.length }))) {
				return
			}
			try {
				const failed = await myDocumentsStore.deleteDocuments(names)
				if (failed.length > 0) {
					showError(t('docudesk', 'Failed to delete {count} item(s)', { count: failed.length }))
				} else {
					showSuccess(t('docudesk', 'Deleted {count} item(s)', { count: names.length }))
				}
			} catch (err) {
				console.error('Bulk delete failed:', err)
				showError(t('docudesk', 'Failed to delete the selected items'))
			} finally {
				this.selectedIds = []
			}
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
		 * Toggle between 'table' and 'cards' (Tegels/Lijst in the design)
		 * and persist the choice so it survives reloads / navigation.
		 *
		 * @param {string} mode New view mode ('table' or 'cards').
		 *
		 * @spec exclude Local view-mode display preference (localStorage); no domain or persistence semantics.
		 */
		onViewModeChange(mode) {
			this.viewMode = mode
			try {
				window.localStorage.setItem(VIEW_MODE_STORAGE_KEY, mode)
			} catch (e) {
				// localStorage can throw in private mode / sandboxed iframes
			}
		},
		/**
		 * Confirm and delete a file or dossier. Deleting a dossier (folder)
		 * removes the folder and all documents inside it. After a successful
		 * delete the list is refreshed by the store.
		 *
		 * @param {object} row Document row.
		 * @return {Promise<void>}
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no DocuDesk domain semantics).
		 */
		async confirmDelete(row) {
			if (!row || !row.fileName) return
			const message = row.isFolder
				? t('docudesk', 'Delete dossier "{name}" and all documents inside it? This cannot be undone.', { name: row.fileName })
				: t('docudesk', 'Delete "{name}"? This cannot be undone.', { name: this.displayName(row) })
			// eslint-disable-next-line no-alert
			if (!window.confirm(message)) {
				return
			}
			try {
				await myDocumentsStore.deleteDocument(row.fileName)
				showSuccess(t('docudesk', 'Deleted "{name}"', { name: this.displayName(row) }))
			} catch (err) {
				console.error('Failed to delete document:', err)
				showError(t('docudesk', 'Failed to delete "{name}"', { name: this.displayName(row) }))
			}
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
		 * Run on-demand validation for a document and surface the verdict +
		 * findings in a modal. Nothing is persisted by this call.
		 *
		 * @param {object} row Document row.
		 * @return {Promise<void>}
		 * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
		 */
		async validateDocument(row) {
			if (!row || !row.fileId) return
			this.validation.show = true
			this.validation.loading = true
			this.validation.error = ''
			this.validation.fileId = row.fileId
			try {
				const result = await validateFile(row.fileId, row.documentType)
				this.validation.status = result.validationStatus || ''
				this.validation.findings = result.validationFindings || []
			} catch (e) {
				const reason = e.response && e.response.data && e.response.data.error
				this.validation.error = reason || t('docudesk', 'Validation failed')
			} finally {
				this.validation.loading = false
			}
		},
		/**
		 * Handle an OCR cross-link from a text-layer-missing finding.
		 *
		 * @return {void}
		 * @spec openspec/changes/document-validation-checks/specs/document-validation-checks/spec.md
		 */
		onOcrRequested() {
			this.validation.show = false
			this.$router.push({ name: 'Anonymization' })
		},
		/**
		 * Open the side-by-side comparison view with this document preselected
		 * on the left. The anonymised output (when present) is offered as the
		 * right subject so operators can verify the redaction.
		 *
		 * @param {object} row Document row.
		 * @return {void}
		 * @spec openspec/changes/document-comparison/specs/document-comparison/spec.md
		 */
		compareDocument(row) {
			if (!row || !row.fileId) return
			const query = { left: String(row.fileId) }
			if (row.anonymizedFileId) {
				query.right = String(row.anonymizedFileId)
			}
			this.$router.push({ name: 'Comparison', query })
		},
		/**
		 * Open the document's Nextcloud file versions (Versies) view.
		 *
		 * @param {object} row Document row.
		 * @return {void}
		 * @spec openspec/changes/document-versions-detail-tab/specs/document-versions/spec.md
		 */
		openVersions(row) {
			if (!row || !row.fileId) return
			this.$router.push({ name: 'Versions', query: { fileId: String(row.fileId) } })
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

/* Options menu in the actions-column header (bulk-selection / bulk actions). */
.my-documents-options {
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
