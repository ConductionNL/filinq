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
						<DdIcon :name="iconFor(row)" :size="18" class="my-documents-name__icon" />
						<span>{{ displayName(row) }}</span>
					</div>
				</template>

				<template #column-kind="{ row }">
					<CnStatusBadge
						:label="kindLabel(row)"
						:color-map="kindColorMap" />
				</template>

				<!-- TODO: re-enable the Status column once a real per-document
				     checked/reviewed status is available from the backend. The
				     label is hardcoded ("Not checked") for now, so the column is
				     hidden rather than showing a placeholder for every row.
				<template #column-status="{ row }">
					<span class="my-documents-status">
						<EyeOffOutline :size="16" class="my-documents-status__icon" />
						{{ statusLabel(row) }}
					</span>
				</template>
				-->

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

		<!--
			Delete confirmation, single row and bulk. Replaces window.confirm():
			executeDelete()/executeBulkDelete() run only from @confirm, so
			nothing is removed without an explicit confirmation.
		-->
		<ConfirmActionDialog
			v-if="deleteTarget"
			:name="t('docudesk', 'Delete document')"
			:message="deleteMessage"
			:busy="deleting"
			@confirm="executeDelete"
			@cancel="cancelDelete" />

		<ConfirmActionDialog
			v-if="bulkDeleteNames.length > 0"
			:name="t('docudesk', 'Delete selected items')"
			:message="bulkDeleteMessage"
			:busy="deleting"
			@confirm="executeBulkDelete"
			@cancel="cancelBulkDelete" />
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
import DdIcon from '../../components/DdIcon.vue'
import FileViewerPage from '../fileViewer/FileViewerPage.vue'
import ValidationResultModal from '../../modals/ValidationResultModal.vue'
import ConfirmActionDialog from '../../dialogs/ConfirmActionDialog.vue'
import { validateFile } from '../../services/validationService.js'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
// TODO: re-enable with the Status column (commented out until a real
// per-document checked status exists).
// import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import Download from 'vue-material-design-icons/Download.vue'
import ShieldCheckOutline from 'vue-material-design-icons/ShieldCheckOutline.vue'
import Compare from 'vue-material-design-icons/Compare.vue'
import History from 'vue-material-design-icons/History.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import CheckboxMultipleMarkedOutline from 'vue-material-design-icons/CheckboxMultipleMarkedOutline.vue'
import FilterOutline from 'vue-material-design-icons/FilterOutline.vue'

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
		DdIcon,
		FileViewerPage,
		ValidationResultModal,
		ConfirmActionDialog,
		DotsHorizontal,
		Eye,
		// TODO: re-enable with the Status column (see import above).
		// EyeOffOutline,
		Download,
		ShieldCheckOutline,
		Compare,
		History,
		Delete,
		Cog,
		CheckboxMultipleMarkedOutline,
		FilterOutline,
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
				[t('docudesk', 'Concept')]: 'warning',
				[t('docudesk', 'Anonymized')]: 'success',
			},
			deleteTarget: null, // row awaiting delete confirmation, or null
			bulkDeleteNames: [], // file names awaiting bulk-delete confirmation
			deleting: false,
		}
	},
	computed: {
		tableColumns() {
			return [
				{ key: 'fileName', label: t('docudesk', 'Name') },
				{ key: 'kind', label: t('docudesk', 'Kind') },
				// TODO: re-enable once a real per-document checked/reviewed status
				// is available from the backend (label is hardcoded for now).
				// { key: 'status', label: t('docudesk', 'Status') },
				{ key: 'modified', label: t('docudesk', 'Date') },
				{ key: 'fileSize', label: t('docudesk', 'Size') },
			]
		},
		filteredDocuments() {
			const query = this.searchQuery.trim().toLowerCase()
			const docs = myDocumentsStore.visibleDocuments
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
		/**
		 * Body text of the single-item delete confirmation dialog.
		 *
		 * @spec exclude Presentational string for the delete confirmation dialog.
		 */
		deleteMessage() {
			const row = this.deleteTarget
			if (!row) {
				return ''
			}
			return row.isFolder
				? t('docudesk', 'Delete dossier "{name}" and all documents inside it? This cannot be undone.', { name: row.fileName })
				: t('docudesk', 'Delete "{name}"? This cannot be undone.', { name: this.displayName(row) })
		},
		/**
		 * Body text of the bulk delete confirmation dialog.
		 *
		 * @spec exclude Presentational string for the delete confirmation dialog.
		 */
		bulkDeleteMessage() {
			return t('docudesk', 'Delete {count} selected item(s)? Dossiers and the documents inside them will be removed. This cannot be undone.', { count: this.bulkDeleteNames.length })
		},
	},
	/**
	 * @spec exclude Lifecycle bootstrap (fetch + keyboard listener wiring).
	 */
	mounted() {
		myDocumentsStore.fetchDocuments()
		window.addEventListener('keydown', this.onKeydown)
	},
	beforeUnmount() {
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
		bulkDelete() {
			const names = myDocumentsStore.documents
				.filter((d) => this.selectedIds.includes(d.fileId))
				.map((d) => d.fileName)
			if (names.length === 0) return
			this.bulkDeleteNames = names
		},
		/**
		 * Dismiss the bulk-delete confirmation without deleting anything.
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no DocuDesk domain semantics).
		 */
		cancelBulkDelete() {
			this.bulkDeleteNames = []
		},
		/**
		 * Delete the confirmed selection. Reachable only from the dialog's
		 * @confirm, so nothing is removed without an explicit confirmation.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no DocuDesk domain semantics).
		 */
		async executeBulkDelete() {
			const names = this.bulkDeleteNames
			if (names.length === 0) return
			this.deleting = true
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
				this.bulkDeleteNames = []
				this.deleting = false
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
				const firstFile = myDocumentsStore.visibleDocuments.find((d) => !d.isFolder)
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
		confirmDelete(row) {
			if (!row || !row.fileName) return
			this.deleteTarget = row
		},
		/**
		 * Dismiss the delete confirmation without deleting anything.
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no DocuDesk domain semantics).
		 */
		cancelDelete() {
			this.deleteTarget = null
		},
		/**
		 * Delete the confirmed file or dossier. Reachable only from the
		 * dialog's @confirm, so nothing is removed without an explicit
		 * confirmation.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no DocuDesk domain semantics).
		 */
		async executeDelete() {
			const row = this.deleteTarget
			if (!row) return
			this.deleting = true
			try {
				await myDocumentsStore.deleteDocument(row.fileName)
				showSuccess(t('docudesk', 'Deleted "{name}"', { name: this.displayName(row) }))
			} catch (err) {
				console.error('Failed to delete document:', err)
				showError(t('docudesk', 'Failed to delete "{name}"', { name: this.displayName(row) }))
			} finally {
				this.deleteTarget = null
				this.deleting = false
			}
		},
		/**
		 * Preview the file inline using DocuDesk's own file viewer modal
		 * (PDF / docx / text). Folders are ignored.
		 *
		 * The overview only lists the anonymized copy once a file has been
		 * anonymized, so opening one wires up both variants: the concept
		 * (original) is loaded as the base and the anonymized copy is shown on
		 * top, leaving the "Show original" toggle to switch back to the concept.
		 *
		 * @param {object} row Document row from the table.
		 */
		viewFile(row) {
			if (!row || row.isFolder) return
			const concept = row.isAnonymized ? myDocumentsStore.conceptFor(row) : row
			const anonymized = row.isAnonymized ? row : myDocumentsStore.anonymizedFor(row)
			const base = concept || row
			fileViewerStore.open({
				fileId: base.fileId,
				fileName: base.fileName,
				mimeType: base.mimeType,
				path: `${myDocumentsStore.currentPath}/${base.fileName}`,
			})
			if (anonymized && anonymized.fileId !== base.fileId) {
				fileViewerStore.setAnonymizedVariant({
					fileId: anonymized.fileId,
					fileName: anonymized.fileName,
					mimeType: anonymized.mimeType,
					path: `${myDocumentsStore.currentPath}/${anonymized.fileName}`,
				})
			}
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
		 * @spec openspec/specs/document-validation-checks/spec.md
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
		 * @spec openspec/specs/document-validation-checks/spec.md
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
		 * @spec openspec/specs/document-comparison/spec.md
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
		 * @spec openspec/specs/document-versions/spec.md
		 */
		openVersions(row) {
			if (!row || !row.fileId) return
			this.$router.push({ name: 'Versions', query: { fileId: String(row.fileId) } })
		},
		/**
		 * Pick a DocuDesk icon name based on the file's MIME type / extension.
		 *
		 * @param {object} row Document row.
		 * @return {string} DdIcon name ('folder', 'pdf' or 'article').
		 */
		iconFor(row) {
			const mime = row.mimeType || ''
			const name = (row.fileName || '').toLowerCase()
			if (mime === 'httpd/unix-directory' || name.endsWith('/')) return 'folder'
			if (mime.includes('pdf') || name.endsWith('.pdf')) return 'pdf'
			return 'article'
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
		 * Badge label for the "Soort" column. Files are tagged by their own
		 * state; a dossier (folder) reflects its contents — "Anonymized" once
		 * every source inside has an anonymized output, "Concept" otherwise.
		 *
		 * @param {object} row Document row.
		 * @return {string} Badge label.
		 */
		kindLabel(row) {
			if (row.isFolder) {
				return row.allChildrenAnonymized ? t('docudesk', 'Anonymized') : t('docudesk', 'Concept')
			}
			return row.isAnonymized ? t('docudesk', 'Anonymized') : t('docudesk', 'Concept')
		},
		// TODO: re-enable when the app has a real per-document checked/reviewed
		// status. The Status column is commented out for now (both its column
		// definition and template) because this was hardcoded to "Not checked".
		// /** Placeholder status until the app has a real "checked" signal. */
		// statusLabel() {
		//     return t('docudesk', 'Not checked')
		// },
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

/* TODO: re-enable with the Status column (commented out until a real
   per-document checked status exists).
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
*/

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

/* WCAG 2.2 SC 2.3.3 — users who ask the OS for reduced motion get the state
   change without the tween. */
@media (prefers-reduced-motion: reduce) {
	.my-documents-name__icon {
		transition: none;
	}
}

</style>
