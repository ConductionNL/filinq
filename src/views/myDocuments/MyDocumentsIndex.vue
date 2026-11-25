<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<script setup>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { fileViewerStore, myDocumentsStore } from '../../store/store.js'
</script>

<template>
	<div class="my-documents-wrapper">
		<FileViewerPage v-if="fileViewerStore.currentFile" />

		<template v-else>
			<DdPageHeader :title="t('filinq', 'Documents')" />

			<DdIndexPage
				:objects="paginatedDocuments"
				:columns="tableColumns"
				:pagination="paginationData"
				:loading="myDocumentsStore.loading"
				:viewMode="viewMode"
				rowKey="fileId"
				:emptyText="emptyContentName"
				:tableLabel="t('filinq', 'List')"
				:cardsLabel="t('filinq', 'Tiles')"
				:viewToggleLabel="t('filinq', 'View mode')"
				:itemsPerPageLabel="t('filinq', 'Items per page:')"
				:pageInfoFormat="t('filinq', 'Page {current} of {total}')"
				:firstLabel="t('filinq', 'First')"
				:previousLabel="t('filinq', 'Previous')"
				:nextLabel="t('filinq', 'Next')"
				:lastLabel="t('filinq', 'Last')"
				:selectable="bulkSelect"
				:selectedKeys="selectedIds"
				:selectAllLabel="t('filinq', 'Select all')"
				@pageChanged="onPageChanged"
				@pageSizeChanged="onPageSizeChanged"
				@update:viewMode="onViewModeChange"
				@rowClick="onRowClick"
				@toggleSelect="onToggleSelect"
				@toggleSelectAll="onToggleSelectAll">
				<template #header-actions>
					<DdSearchBar
						v-model="searchQuery"
						class="my-documents-search"
						:placeholder="searchMode === 'content' ? t('filinq', 'Search by name and content') : t('filinq', 'Search by name')"
						:clearLabel="t('filinq', 'Clear search')" />
					<div
						class="my-documents-search-mode"
						role="group"
						:aria-label="t('filinq', 'Search in')"
						data-testid="my-documents-search-mode">
						<button
							v-for="mode in searchModes"
							:key="mode.key"
							type="button"
							class="my-documents-search-mode__button"
							:aria-pressed="searchMode === mode.key ? 'true' : 'false'"
							:data-testid="'my-documents-search-mode-' + mode.key"
							@click="setSearchMode(mode.key)">
							{{ mode.label }}
						</button>
					</div>
					<p
						v-if="contentSearchNote"
						class="my-documents-search-note"
						role="status"
						data-testid="my-documents-search-note">
						{{ contentSearchNote }}
						<button
							v-if="contentAnswer && contentAnswer.unread.length > 0"
							type="button"
							class="my-documents-search-mode__button"
							:disabled="contentReading"
							data-testid="my-documents-read-now"
							@click="readUnreadDocuments">
							{{ contentReading ? t('filinq', 'Reading documents') : t('filinq', 'Read them now') }}
						</button>
					</p>
				</template>

				<template #column-fileName="{ row }">
					<div class="my-documents-name">
						<DdIcon
							:name="iconFor(row)"
							:size="18"
							class="my-documents-name__icon" />
						<span>{{ displayName(row) }}</span>
						<span
							v-if="sealedIds[String(row.fileId)]"
							class="my-documents-ocr-badge"
							data-testid="waarmerk-badge"
							:title="t('filinq', 'This file carries an organisation waarmerk')">
							{{ t('filinq', 'Waarmerk') }}
						</span>
						<span
							v-if="ocrBadgeFor(row)"
							class="my-documents-ocr-badge"
							:title="
								t(
									'filinq',
									'Text recognised by OCR, with its confidence',
								)
							">
							{{ ocrBadgeFor(row) }}
						</span>
						<span
							v-if="row.notesCount > 0"
							class="my-documents-notes-count"
							:title="n('filinq', '%n note', '%n notes', row.notesCount)"
							:aria-label="n('filinq', '%n note', '%n notes', row.notesCount)"
							data-testid="my-documents-notes-count">
							<CommentTextOutline :size="16" />
							{{ row.notesCount }}
						</span>
						<span
							v-if="caseSystemFiles[String(row.fileId)]"
							class="my-documents-case-system-badge"
							data-testid="my-documents-case-system-badge"
							:title="t('filinq', 'The case system holds this document')">
							{{ t('filinq', 'In the case system') }}
						</span>
						<span
							v-if="sourceBadges[String(row.fileId)]"
							class="my-documents-case-system-badge"
							data-testid="my-documents-source-badge"
							:title="t('filinq', 'The master record of this document is in the case system')">
							{{ sourceBadgeLabel(sourceBadges[String(row.fileId)]) }}
						</span>
						<span
							v-if="snippetFor(row).length > 0"
							class="my-documents-snippet"
							:data-testid="'my-documents-snippet-' + row.fileId"><component
							:is="part.marked ? 'mark' : 'span'"
							v-for="(part, index) in snippetFor(row)"
							:key="index">{{ part.text }}</component></span>
					</div>
				</template>

				<template #column-kind="{ row }">
					<CnStatusBadge
						:label="kindLabel(row)"
						:colorMap="kindColorMap" />
				</template>

				<template #column-status="{ row }">
					<span
						v-if="!row.isFolder"
						class="my-documents-status"
						:class="{
							'my-documents-status--checked':
								!!reviewMarks[String(row.fileId)],
						}"
						:title="statusTitle(row)">
						<CheckDecagram
							v-if="reviewMarks[String(row.fileId)]"
							:size="16"
							class="my-documents-status__icon" />
						<EyeOffOutline
							v-else
							:size="16"
							class="my-documents-status__icon" />
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
						@toggleSelect="onToggleSelect" />
				</template>

				<template #actions-header>
					<NcActions
						class="my-documents-options"
						forceMenu
						:aria-label="t('filinq', 'Options')">
						<template #icon>
							<component
								:is="bulkSelect ? 'Cog' : 'FilterOutline'"
								:size="20"
								class="my-documents-filter-icon"
								:title="
									bulkSelect
										? t('filinq', 'Options')
										: t('filinq', 'Filter')
								" />
						</template>
						<NcActionButton closeAfterClick @click="toggleBulkSelect">
							<template #icon>
								<CheckboxMultipleMarkedOutline :size="20" />
							</template>
							{{
								bulkSelect
									? t('filinq', 'Cancel bulk selection')
									: t('filinq', 'Bulk selection')
							}}
						</NcActionButton>
						<NcActionButton
							v-if="bulkSelect && selectedShareIds.length > 0"
							closeAfterClick
							data-testid="documents-share-selected"
							@click="openShare(selectedShareIds)">
							<template #icon>
								<LinkVariant :size="20" />
							</template>
							{{
								t('filinq', 'Share selected by link ({count})', {
									count: selectedShareIds.length,
								})
							}}
						</NcActionButton>
						<NcActionButton
							closeAfterClick
							data-testid="documents-shared-links"
							@click="$router.push({ name: 'SharedLinks' })">
							<template #icon>
								<LinkVariant :size="20" />
							</template>
							{{ t('filinq', 'Shared links') }}
						</NcActionButton>
						<NcActionButton
							v-if="bulkSelect && selectedIds.length > 0"
							closeAfterClick
							@click="bulkDelete">
							<template #icon>
								<Delete :size="20" />
							</template>
							{{
								t('filinq', 'Delete selected ({count})', {
									count: selectedIds.length,
								})
							}}
						</NcActionButton>
					</NcActions>
				</template>

				<template #row-actions="{ row }">
					<NcActions class="my-documents-row-actions">
						<template #icon>
							<DotsHorizontal :size="24" />
						</template>
						<NcActionButton closeAfterClick @click="onRowClick(row)">
							<template #icon>
								<Eye :size="20" />
							</template>
							{{ t('filinq', 'Open') }}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder"
							closeAfterClick
							@click="downloadFile(row)">
							<template #icon>
								<Download :size="20" />
							</template>
							{{ t('filinq', 'Download') }}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder"
							closeAfterClick
							data-testid="document-share-by-link"
							@click="openShare([row.fileId])">
							<template #icon>
								<LinkVariant :size="20" />
							</template>
							{{ t('filinq', 'Share by link') }}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder"
							closeAfterClick
							@click="validateDocument(row)">
							<template #icon>
								<ShieldCheckOutline :size="20" />
							</template>
							{{ t('filinq', 'Validate') }}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder"
							closeAfterClick
							data-testid="document-sanitize"
							@click="sanitizeDocument(row)">
							<template #icon>
								<BroomIcon :size="20" />
							</template>
							{{ t('filinq', 'Sanitize') }}
						</NcActionButton>
						<NcActionButton
							v-if="ocrOfferedFor(row)"
							:disabled="Boolean(ocrRunning[row.fileId])"
							closeAfterClick
							@click="runOcrOn(row)">
							<template #icon>
								<TextRecognition :size="20" />
							</template>
							{{
								ocrRunning[row.fileId]
									? t('filinq', 'Running OCR…')
									: t('filinq', 'Run OCR')
							}}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder && iconFor(row) === 'pdf'"
							closeAfterClick
							data-testid="document-waarmerk"
							@click="openWaarmerk(row)">
							<template #icon>
								<CertificateOutline :size="20" />
							</template>
							{{ t('filinq', 'Add waarmerk') }}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder"
							closeAfterClick
							@click="compareDocument(row)">
							<template #icon>
								<Compare :size="20" />
							</template>
							{{ t('filinq', 'Compare…') }}
						</NcActionButton>
						<NcActionButton
							v-if="!row.isFolder"
							closeAfterClick
							@click="openVersions(row)">
							<template #icon>
								<History :size="20" />
							</template>
							{{ t('filinq', 'Versions') }}
						</NcActionButton>
						<NcActionButton closeAfterClick @click="confirmDelete(row)">
							<template #icon>
								<Delete :size="20" />
							</template>
							{{ t('filinq', 'Delete') }}
						</NcActionButton>
					</NcActions>
				</template>
			</DdIndexPage>
		</template>

		<ShareByLinkDialog
			:show="shareDocuments.length > 0"
			:documents="shareDocuments"
			@close="shareDocuments = []" />
		<SanitizationReportModal
			:show="sanitization.show"
			:loading="sanitization.loading"
			:error="sanitization.error"
			:result="sanitization.result"
			@close="sanitization.show = false" />
		<WaarmerkSealModal
			:show="waarmerk.show"
			:fileId="waarmerk.fileId"
			:fileName="waarmerk.fileName"
			@sealed="onSealed"
			@sanitize="sanitizeDocument({ fileId: $event })"
			@close="waarmerk.show = false" />
		<ValidationResultModal
			:show="validation.show"
			:loading="validation.loading"
			:error="validation.error"
			:status="validation.status"
			:findings="validation.findings"
			@close="validation.show = false"
			@ocr="onOcrRequested"
			@removePassword="openPasswordDialog" />
		<PdfPasswordModal
			:show="unlock.show"
			:fileId="validation.fileId"
			@close="unlock.show = false"
			@unlocked="onUnlocked" />

		<!--
			Delete confirmation, single row and bulk. Replaces window.confirm():
			executeDelete()/executeBulkDelete() run only from @confirm, so
			nothing is removed without an explicit confirmation.
		-->
		<CnConfirmDialog
			v-if="deleteTarget"
			:dialogTitle="t('filinq', 'Delete document')"
			:message="deleteMessage"
			:confirmLabel="t('filinq', 'Delete')"
			variant="error"
			@confirm="executeDelete"
			@close="cancelDelete" />

		<CnConfirmDialog
			v-if="bulkDeleteNames.length > 0"
			:dialogTitle="t('filinq', 'Delete selected items')"
			:message="bulkDeleteMessage"
			:confirmLabel="t('filinq', 'Delete')"
			variant="error"
			@confirm="executeBulkDelete"
			@close="cancelBulkDelete" />

		<WaitingForMeList />
		<DueForReviewList />
	</div>
</template>

<script>
import { CnConfirmDialog, CnStatusBadge } from '@conduction/nextcloud-vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcActionButton, NcActions } from '@nextcloud/vue'
import BroomIcon from 'vue-material-design-icons/Broom.vue'
import CertificateOutline from 'vue-material-design-icons/CertificateOutline.vue'
import CheckboxMultipleMarkedOutline from 'vue-material-design-icons/CheckboxMultipleMarkedOutline.vue'
import CheckDecagram from 'vue-material-design-icons/CheckDecagram.vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import CommentTextOutline from 'vue-material-design-icons/CommentTextOutline.vue'
import Compare from 'vue-material-design-icons/Compare.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import Download from 'vue-material-design-icons/Download.vue'
import Eye from 'vue-material-design-icons/Eye.vue'
import EyeOffOutline from 'vue-material-design-icons/EyeOffOutline.vue'
import FilterOutline from 'vue-material-design-icons/FilterOutline.vue'
import History from 'vue-material-design-icons/History.vue'
import LinkVariant from 'vue-material-design-icons/LinkVariant.vue'
import ShieldCheckOutline from 'vue-material-design-icons/ShieldCheckOutline.vue'
import TextRecognition from 'vue-material-design-icons/TextRecognition.vue'
import DdDocumentCard from '../../components/DdDocumentCard.vue'
import DdIcon from '../../components/DdIcon.vue'
import DdIndexPage from '../../components/DdIndexPage.vue'
import DdPageHeader from '../../components/DdPageHeader.vue'
import DdSearchBar from '../../components/DdSearchBar.vue'
import DueForReviewList from '../../components/myDocuments/DueForReviewList.vue'
import WaitingForMeList from '../../components/myDocuments/WaitingForMeList.vue'
import PdfPasswordModal from '../../modals/PdfPasswordModal.vue'
import SanitizationReportModal from '../../modals/SanitizationReportModal.vue'
import ShareByLinkDialog from '../../modals/ShareByLinkDialog.vue'
import ValidationResultModal from '../../modals/ValidationResultModal.vue'
import WaarmerkSealModal from '../../modals/WaarmerkSealModal.vue'
import FileViewerPage from '../fileViewer/FileViewerPage.vue'
import { markedSnippet } from '../../integrations/documentContentSearch.js'
import { ocrRun } from '../../mixins/ocrRun.js'
import { fetchSourceBadges, sourceBadgeLabel } from '../../services/bridge.js'
import { fetchCaseSystemFiles } from '../../services/caseSystemFiles.js'
import { openLinkedFile } from '../../services/fileDeepLink.js'
import { nameThenContent, readListFiles, searchListContent } from '../../services/listContentSearch.js'
import { fetchOcrStatus, isOcrCandidate, ocrBadgeLabel } from '../../services/ocr.js'
import { fetchReviewMarks } from '../../services/reviewMark.js'
import { sanitizeFile } from '../../services/sanitization.js'
import { validateFile } from '../../services/validationService.js'
import { sealedFileIds } from '../../services/waarmerk.js'

const VIEW_MODE_STORAGE_KEY = 'filinq:myDocuments:viewMode'
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
		CommentTextOutline,
		DueForReviewList,
		WaitingForMeList,
		DdIndexPage,
		CnStatusBadge,
		NcActions,
		NcActionButton,
		DdSearchBar,
		DdPageHeader,
		DdDocumentCard,
		DdIcon,
		FileViewerPage,
		PdfPasswordModal,
		ValidationResultModal,
		SanitizationReportModal,
		ShareByLinkDialog,
		LinkVariant,
		BroomIcon,
		CnConfirmDialog,
		DotsHorizontal,
		Eye,
		// TODO: re-enable with the Status column (see import above).
		CheckDecagram,
		EyeOffOutline,
		Download,
		ShieldCheckOutline,
		TextRecognition,
		Compare,
		History,
		Delete,
		Cog,
		CheckboxMultipleMarkedOutline,
		FilterOutline,
		CertificateOutline,
		WaarmerkSealModal,
	},

	mixins: [ocrRun],

	data() {
		return {
			currentPage: 1,
			pageSize: 20,
			searchQuery: '',
			// Search the names only, or the names and the content.
			searchMode: 'name',
			// The last content search's answer, for `contentWords`.
			contentAnswer: null,
			contentWords: '',
			contentReading: false,
			contentReadFailed: 0,
			contentTimer: null,
			viewMode: loadPersistedViewMode(),
			bulkSelect: false,
			selectedIds: [],
			// The file ids the Share by link dialog is open for, or none.
			shareDocuments: [],
			sanitization: {
				show: false,
				loading: false,
				error: '',
				result: null,
			},

			validation: {
				show: false,
				loading: false,
				error: '',
				status: '',
				findings: [],
				fileId: null,
			},

			// The password dialog opened from a locked PDF's finding.
			unlock: { show: false },

			kindColorMap: {
				[t('filinq', 'Concept')]: 'warning',
				[t('filinq', 'Anonymized')]: 'success',
			},

			deleteTarget: null, // row awaiting delete confirmation, or null
			bulkDeleteNames: [], // file names awaiting bulk-delete confirmation

			// OCR (ocr-trigger-surface): whether it can run here, the last
			// result per file id, and which files are running now.
			ocrAvailable: false,
			ocrResults: {},
			// The latest review mark per file id on this page.
			reviewMarks: {},
			caseSystemFiles: {},
			sourceBadges: {},
			ocrRunning: {},
			// Waarmerk (document-waarmerk-certification): the seal dialog and the sealed file ids.
			waarmerk: { show: false, fileId: 0, fileName: '' },
			sealedIds: {},
		}
	},

	computed: {
		/**
		 * CnDataTable column set for the document listing.
		 *
		 * @return {object[]}
		 * @spec exclude column header labels for the file listing; no openspec requirement fixes this table's columns — the "Kind" column's authoritative value is specified at openspec/specs/anonymization-link/spec.md#requirement-bidirectional-lookup-via-or-search-api-req-alink-03
		 */
		tableColumns() {
			return [
				{ key: 'fileName', label: t('filinq', 'Name') },
				{ key: 'kind', label: t('filinq', 'Kind') },
				{ key: 'status', label: t('filinq', 'Status') },
				{ key: 'modified', label: t('filinq', 'Date') },
				{ key: 'fileSize', label: t('filinq', 'Size') },
			]
		},

		/**
		 * The listed documents that match the search: by name, or in content
		 * mode the answer of the content search.
		 *
		 * @return {Array<object>} The documents.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		filteredDocuments() {
			const query = this.searchQuery.trim().toLowerCase()
			const docs = myDocumentsStore.visibleDocuments
			if (!query) return docs
			if (this.contentMatches) {
				return this.contentMatches.rows
			}
			return docs.filter((d) =>
				(d.fileName || '').toLowerCase().includes(query),
			)
		},

		/**
		 * The two search modes, with their labels.
		 *
		 * @return {Array<{key: string, label: string}>} The modes.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		searchModes() {
			return [
				{ key: 'name', label: t('filinq', 'Name') },
				{ key: 'content', label: t('filinq', 'Name and content') },
			]
		},

		/**
		 * In content mode, the rows of the answer to the words now typed:
		 * name matches first, then content matches; null otherwise.
		 *
		 * @return {{rows: Array<object>, snippets: object}|null} The matches.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		contentMatches() {
			const words = this.searchQuery.trim()
			if (this.searchMode !== 'content' || words === '' || !this.contentAnswer || this.contentWords !== words) {
				return null
			}
			return nameThenContent(myDocumentsStore.visibleDocuments, words, this.contentAnswer.hits)
		},

		/**
		 * What the page says about the content search: not available, could
		 * not be asked, or how many documents have not been read yet.
		 *
		 * @return {string} The sentence, or empty.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		contentSearchNote() {
			if (!this.contentMatches) {
				return ''
			}
			if (this.contentReadFailed > 0) {
				return n('filinq', '%n document could not be read', '%n documents could not be read', this.contentReadFailed)
			}
			if (this.contentAnswer.failed) {
				return t('filinq', 'The content could not be searched; only names were matched.')
			}
			if (!this.contentAnswer.available) {
				return t('filinq', 'The content of documents cannot be searched here; only names were matched.')
			}
			const unread = this.contentAnswer.unread.length
			return unread > 0
				? n('filinq', '%n document has not been read yet and cannot be found by content', '%n documents have not been read yet and cannot be found by content', unread)
				: ''
		},

		/**
		 * The selected documents that can be shared by link: files, not folders.
		 *
		 * @return {number[]} Their file ids.
		 * @spec openspec/changes/archive/2026-11-25-share-document-links/tasks.md#task-3.1
		 */
		selectedShareIds() {
			return myDocumentsStore.documents
				.filter((d) => !d.isFolder && this.selectedIds.includes(d.fileId))
				.map((d) => d.fileId)
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

		/**
		 * Empty-state heading for the document listing — the store's error when
		 * loading failed, otherwise the no-documents message.
		 *
		 * @return {string}
		 * @spec exclude presentational empty/error placeholder for the file listing; no openspec requirement governs the listing chrome — the rows' authoritative anonymised/concept state is specified at openspec/specs/anonymization-link/spec.md#requirement-bidirectional-lookup-via-or-search-api-req-alink-03
		 */
		emptyContentName() {
			if (myDocumentsStore.error) {
				return myDocumentsStore.error
			}
			return t('filinq', 'No documents found')
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
				? t(
						'filinq',
						'Delete dossier "{name}" and all documents inside it? This cannot be undone.',
						{ name: row.fileName },
					)
				: t('filinq', 'Delete "{name}"? This cannot be undone.', {
						name: this.displayName(row),
					})
		},

		/**
		 * Body text of the bulk delete confirmation dialog.
		 *
		 * @spec exclude Presentational string for the delete confirmation dialog.
		 */
		bulkDeleteMessage() {
			return t(
				'filinq',
				'Delete {count} selected item(s)? Dossiers and the documents inside them will be removed. This cannot be undone.',
				{ count: this.bulkDeleteNames.length },
			)
		},
	},

	/**
	 * @spec exclude Lifecycle bootstrap (fetch + keyboard listener wiring).
	 */
	watch: {
		/**
		 * Search the content again, shortly after the words change.
		 *
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		searchQuery() {
			this.scheduleContentSearch()
		},

		/**
		 * Open the document a changed `file` route parameter names.
		 *
		 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
		 */
		'$route.query.file': function() {
			this.openFromRoute()
		},

		paginatedDocuments: {
			/**
			 * Read the OCR status when the files on screen change.
			 *
			 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
			 */
			handler(rows) {
				this.loadOcrStatus(rows)
				this.loadReviewMarks(rows)
				this.loadCaseSystemFiles(rows)
				this.loadSourceBadges(rows)
			},
		},
	},

	/**
	 * Load the documents and which of them carry a waarmerk.
	 *
	 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
	 */
	mounted() {
		myDocumentsStore.fetchDocuments()
		this.loadSealedIds()
		this.openFromRoute()
		window.addEventListener('keydown', this.onKeydown)
	},

	/**
	 * Stop a pending content search and the keyboard listener.
	 *
	 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
	 */
	beforeUnmount() {
		clearTimeout(this.contentTimer)
		window.removeEventListener('keydown', this.onKeydown)
	},

	methods: {
		/**
		 * Search the names only, or the names and the content.
		 *
		 * @param {string} mode `name` or `content`.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		setSearchMode(mode) {
			this.searchMode = mode === 'content' ? 'content' : 'name'
			this.currentPage = 1
			this.scheduleContentSearch()
		},

		/**
		 * Run the content search a moment after the last keystroke, so one
		 * search goes out per pause, not one per letter.
		 *
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		scheduleContentSearch() {
			clearTimeout(this.contentTimer)
			if (this.searchMode !== 'content' || this.searchQuery.trim() === '') {
				return
			}
			this.contentTimer = setTimeout(() => this.runContentSearch(), 400)
		},

		/**
		 * Ask which listed documents hold the words now typed.
		 *
		 * @return {Promise<void>} Resolves once the answer is kept.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		async runContentSearch() {
			const words = this.searchQuery.trim()
			if (this.searchMode !== 'content' || words === '') {
				return
			}
			const answer = await searchListContent(words, myDocumentsStore.visibleDocuments)
			if (this.searchQuery.trim() !== words) {
				return
			}
			this.contentAnswer = answer
			this.contentWords = words
			this.contentReadFailed = 0
			this.currentPage = 1
		},

		/**
		 * Have the documents read that have no extracted text yet, then
		 * search again so they can be found by content.
		 *
		 * @return {Promise<void>} Resolves once the search ran again.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		async readUnreadDocuments() {
			this.contentReading = true
			const outcome = await readListFiles(this.contentAnswer ? this.contentAnswer.unread : [])
			this.contentReading = false
			await this.runContentSearch()
			this.contentReadFailed = outcome.failed
		},

		/**
		 * The snippet of a content match, split so the searched words are marked.
		 *
		 * @param {object} row One listed document.
		 * @return {Array<{text: string, marked: boolean}>} The parts, or none for a name match.
		 * @spec openspec/changes/work-content-search/tasks.md#task-2.1
		 */
		snippetFor(row) {
			const snippet = this.contentMatches ? this.contentMatches.snippets[String(row.fileId)] : undefined
			return snippet ? markedSnippet(snippet, this.contentWords) : []
		},

		/**
		 * Open the viewer on the document the `file` route parameter names
		 * (`/apps/filinq/my-documents?file=<id>`), the deep link of a document
		 * hit. A file the caller has no access to is not opened.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/work-content-search/specs/document-content-search/spec.md#requirement-unified-search-finds-document-content-through-openregister-req-wcs-004
		 */
		async openFromRoute() {
			if (await openLinkedFile(this.$route?.query, fileViewerStore) === 'missing') {
				showError(t('filinq', 'The linked document could not be found, or you do not have access to it.'))
			}
		},

		/**
		 * Which files carry an active waarmerk, for the badge. A failure leaves no badges.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		async loadSealedIds() {
			try {
				const ids = await sealedFileIds()
				this.sealedIds = Object.fromEntries([...ids].map((id) => [String(id), true]))
			} catch {
				this.sealedIds = {}
			}
		},

		/**
		 * Open the "Add waarmerk" dialog for a PDF.
		 *
		 * @param {object} row The document row.
		 * @return {void}
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		openWaarmerk(row) {
			this.waarmerk = { show: true, fileId: Number(row.fileId), fileName: this.displayName(row) }
		},

		/**
		 * A seal was made: list the sealed copy and mark it.
		 *
		 * @param {object} result The seal result.
		 * @return {void}
		 * @spec openspec/changes/document-waarmerk-certification/specs/document-waarmerk-certification/spec.md
		 */
		onSealed(result) {
			this.sealedIds = { ...this.sealedIds, [String(result.fileId)]: true }
			myDocumentsStore.fetchDocuments()
		},

		/**
		 * Read whether OCR can run, and the results for the images and PDFs on this page.
		 *
		 * @param {Array<object>} rows The rows on the page.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
		 */
		async loadOcrStatus(rows) {
			const ids = (rows || [])
				.filter(
					(row) =>
						!row.isFolder && row.fileId && isOcrCandidate(row.mimeType),
				)
				.map((row) => row.fileId)
			if (ids.length === 0) {
				return
			}
			try {
				const status = await fetchOcrStatus(ids)
				this.ocrAvailable = status.capability?.available === true
				this.ocrResults = { ...this.ocrResults, ...(status.results || {}) }
			} catch {
				// No status: no action and no badge. The list itself still shows.
			}
		},

		/**
		 * Whether Run OCR is offered on a row. The server checks again.
		 *
		 * @param {object} row The row.
		 * @return {boolean}
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
		 */
		ocrOfferedFor(row) {
			return this.ocrAvailable && !row.isFolder && isOcrCandidate(row.mimeType)
		},

		/**
		 * The OCR badge for a row.
		 *
		 * @param {object} row The row.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
		 */
		ocrBadgeFor(row) {
			return ocrBadgeLabel(this.ocrResults[String(row.fileId)] ?? null)
		},

		/**
		 * Run OCR on a row and update its badge without a reload.
		 *
		 * @param {object} row The row.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-09-29-ocr-trigger-surface/tasks.md#task-3.1
		 */
		async runOcrOn(row) {
			this.ocrRunning = { ...this.ocrRunning, [row.fileId]: true }
			try {
				const result = await this.runOcrAndReport(row.fileId)
				if (result) {
					this.ocrResults = {
						...this.ocrResults,
						[String(row.fileId)]: result,
					}
				}
			} finally {
				this.ocrRunning = { ...this.ocrRunning, [row.fileId]: false }
			}
		},

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
			const allSelected =
				pageIds.length > 0
				&& pageIds.every((id) => this.selectedIds.includes(id))
			if (allSelected) {
				this.selectedIds = this.selectedIds.filter(
					(id) => !pageIds.includes(id),
				)
			} else {
				this.selectedIds = [...new Set([...this.selectedIds, ...pageIds])]
			}
		},

		/**
		 * Open the Share by link dialog for one or several documents.
		 *
		 * @param {number[]} fileIds The documents.
		 * @spec openspec/changes/archive/2026-11-25-share-document-links/tasks.md#task-3.1
		 */
		openShare(fileIds) {
			this.shareDocuments = [...fileIds]
		},

		/**
		 * Confirm and delete every selected document/dossier in one bulk
		 * operation. Dossiers and their contents are removed recursively.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no Filinq domain semantics).
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
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no Filinq domain semantics).
		 */
		cancelBulkDelete() {
			this.bulkDeleteNames = []
		},

		/**
		 * Delete the confirmed selection. Reachable only from the dialog's
		 *
		 * @confirm, so nothing is removed without an explicit confirmation.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no Filinq domain semantics).
		 */
		async executeBulkDelete() {
			const names = this.bulkDeleteNames
			if (names.length === 0) return
			try {
				const failed = await myDocumentsStore.deleteDocuments(names)
				if (failed.length > 0) {
					showError(
						t('filinq', 'Failed to delete {count} item(s)', {
							count: failed.length,
						}),
					)
				} else {
					showSuccess(
						t('filinq', 'Deleted {count} item(s)', {
							count: names.length,
						}),
					)
				}
			} catch (err) {
				console.error('Bulk delete failed:', err)
				showError(t('filinq', 'Failed to delete the selected items'))
			} finally {
				this.selectedIds = []
				this.bulkDeleteNames = []
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
				const firstFile = myDocumentsStore.visibleDocuments.find(
					(d) => !d.isFolder,
				)
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
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no Filinq domain semantics).
		 */
		confirmDelete(row) {
			if (!row || !row.fileName) return
			this.deleteTarget = row
		},

		/**
		 * Dismiss the delete confirmation without deleting anything.
		 *
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no Filinq domain semantics).
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
		 * @spec exclude UI confirmation wrapper around WebDAV passthrough; auth + ACL enforced by Nextcloud core (no Filinq domain semantics).
		 */
		async executeDelete() {
			const row = this.deleteTarget
			if (!row) return
			try {
				await myDocumentsStore.deleteDocument(row.fileName)
				showSuccess(
					t('filinq', 'Deleted "{name}"', {
						name: this.displayName(row),
					}),
				)
			} catch (err) {
				console.error('Failed to delete document:', err)
				showError(
					t('filinq', 'Failed to delete "{name}"', {
						name: this.displayName(row),
					}),
				)
			} finally {
				this.deleteTarget = null
			}
		},

		/**
		 * Preview the file inline using Filinq's own file viewer modal
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
			const anonymized = row.isAnonymized
				? row
				: myDocumentsStore.anonymizedFor(row)
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
			window.open(
				generateUrl(
					`/apps/files/ajax/download.php?dir=/&files=${encodeURIComponent(row.fileName)}&downloadStartSecret=&ocRequest=true`,
				),
				'_blank',
			)
		},

		/**
		 * Sanitize a document into a clean copy beside it and show what was removed.
		 *
		 * @param {object} row Document row.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-21-document-sanitization/tasks.md#4-1
		 */
		async sanitizeDocument(row) {
			if (!row || !row.fileId) return
			this.sanitization = {
				show: true,
				loading: true,
				error: '',
				result: null,
			}
			const answer = await sanitizeFile(row.fileId)
			this.sanitization.loading = false
			if (!answer.ok) {
				this.sanitization.error = answer.error
				return
			}
			this.sanitization.result = answer.data
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
				this.validation.error = reason || t('filinq', 'Validation failed')
			} finally {
				this.validation.loading = false
			}
		},

		/**
		 * Open the password dialog for the locked PDF of the validation result.
		 *
		 * @return {void}
		 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
		 */
		openPasswordDialog() {
			this.unlock.show = true
		},

		/**
		 * A locked PDF was unlocked into a copy: show the copy's own findings and
		 * reload the list so the copy appears beside the original.
		 *
		 * @param {object} copy The copy: fileId, name, validationStatus, validationFindings.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-intake-pdf-password-removal/tasks.md#2-screens
		 */
		async onUnlocked(copy) {
			this.validation.fileId = copy.fileId
			this.validation.status = copy.validationStatus || ''
			this.validation.findings = copy.validationFindings || []
			this.validation.show = true
			showSuccess(t('filinq', 'Unlocked copy saved as {name}', { name: copy.name || '' }))
			await myDocumentsStore.fetchDocuments()
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
			this.$router.push({
				name: 'Versions',
				query: { fileId: String(row.fileId) },
			})
		},

		/**
		 * Pick a Filinq icon name based on the file's MIME type / extension.
		 *
		 * @param {object} row Document row.
		 * @return {string} DdIcon name ('folder', 'pdf' or 'article').
		 */
		iconFor(row) {
			const mime = row.mimeType || ''
			const name = (row.fileName || '').toLowerCase()
			if (mime === 'httpd/unix-directory' || name.endsWith('/'))
				return 'folder'
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
		 * @spec openspec/specs/anonymization-link/spec.md#requirement-bidirectional-lookup-via-or-search-api-req-alink-03
		 */
		kindLabel(row) {
			if (row.isFolder) {
				return row.allChildrenAnonymized
					? t('filinq', 'Anonymized')
					: t('filinq', 'Concept')
			}
			return row.isAnonymized
				? t('filinq', 'Anonymized')
				: t('filinq', 'Concept')
		},

		/**
		 * Read who checked the files on this page, for the Status column.
		 *
		 * @param {Array<object>} rows The rows on the page.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-5
		 */
		async loadReviewMarks(rows) {
			const ids = (rows || [])
				.filter((row) => !row.isFolder && row.fileId)
				.map((row) => row.fileId)
			if (ids.length === 0) {
				return
			}
			try {
				const marks = await fetchReviewMarks(ids)
				this.reviewMarks = { ...this.reviewMarks, ...marks }
			} catch {
				// No status read: every row keeps saying "Not checked".
			}
		},

		/**
		 * Read which documents on this page are in the case system, in one request.
		 *
		 * @param {Array<object>} rows The rows on the page.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-generate-store-in-case-system/specs/case-system-delivery/spec.md#requirement-a-deliverys-outcome-is-visible-and-a-failure-can-be-retried-req-gsc-003
		 */
		async loadCaseSystemFiles(rows) {
			const ids = (rows || [])
				.filter((row) => !row.isFolder && row.fileId)
				.map((row) => row.fileId)
			if (ids.length === 0) {
				return
			}
			try {
				const delivered = await fetchCaseSystemFiles(ids)
				this.caseSystemFiles = { ...this.caseSystemFiles, ...delivered }
			} catch {
				// No answer: no badge. The list itself still shows.
			}
		},

		sourceBadgeLabel,

		/**
		 * The case-system source of the files on the page, in one request
		 * (zgw-document-bridge REQ-DDZGW-009).
		 *
		 * @param {Array<object>} rows The rows on the page.
		 * @return {Promise<void>}
		 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-2
		 */
		async loadSourceBadges(rows) {
			const ids = (rows || [])
				.filter((row) => !row.isFolder && row.fileId)
				.map((row) => row.fileId)
			if (ids.length === 0) {
				return
			}
			try {
				this.sourceBadges = { ...this.sourceBadges, ...(await fetchSourceBadges(ids)) }
			} catch {
				// No answer: no badge. The list itself still shows.
			}
		},

		/**
		 * Whether a person checked the document's findings.
		 *
		 * @param {object} row The row.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-5
		 */
		statusLabel(row) {
			return this.reviewMarks[String(row.fileId)]
				? t('filinq', 'Checked')
				: t('filinq', 'Not checked')
		},

		/**
		 * Who checked the document and when, as a tooltip.
		 *
		 * @param {object} row The row.
		 * @return {string}
		 * @spec openspec/changes/archive/2026-10-30-anonymise-asks-for-the-review-mark/tasks.md#task-5
		 */
		statusTitle(row) {
			const mark = this.reviewMarks[String(row.fileId)]
			if (!mark) {
				return ''
			}
			return t('filinq', 'Checked by {user} on {date}', {
				user: mark.checkedBy,
				date: this.formatDate(mark.checkedAt),
			})
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
.my-documents-search-mode {
	display: inline-flex;
	gap: 4px;
}

.my-documents-search-mode__button {
	padding: 4px 10px;
	border: 1px solid var(--color-border-dark);
	border-radius: var(--border-radius-element, 8px);
	background: var(--color-main-background);
	color: var(--color-main-text);
	cursor: pointer;
}

.my-documents-search-mode__button[aria-pressed='true'] {
	background: var(--color-primary-element);
	border-color: var(--color-primary-element);
	color: var(--color-primary-element-text);
}

.my-documents-search-note {
	margin: 0;
	color: var(--color-text-maxcontrast);
}

.my-documents-snippet {
	display: block;
	flex-basis: 100%;
	color: var(--color-text-maxcontrast);
	font-size: 0.9em;
}

.my-documents-snippet mark {
	background-color: var(--color-primary-element-light);
	color: var(--color-main-text);
}

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

.my-documents-status--checked,
.my-documents-status--checked .my-documents-status__icon {
	color: var(--color-success-text);
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

/* WCAG 2.2 SC 2.3.3 — users who ask the OS for reduced motion get the state
   change without the tween. */
@media (prefers-reduced-motion: reduce) {
	.my-documents-name__icon {
		transition: none;
	}
}

.my-documents-notes-count {
	display: inline-flex;
	align-items: center;
	gap: 2px;
	margin-inline-start: 8px;
	color: var(--color-text-maxcontrast);
}

.my-documents-case-system-badge {
	margin-inline-start: 8px;
	padding: 0 6px;
	border-radius: var(--border-radius-pill);
	border: 1px solid var(--color-success);
	background: var(--color-main-background);
	color: var(--color-success-text);
	font-size: 0.85em;
}

.my-documents-ocr-badge {
	margin-inline-start: 8px;
	padding: 0 6px;
	border-radius: var(--border-radius-pill);
	background: var(--color-primary-element-light);
	color: var(--color-primary-element-light-text);
	font-size: 0.85em;
}
</style>
