<script setup>
import { translate as t } from '@nextcloud/l10n'
import { anonymizationStore, fileViewerStore, myDocumentsStore } from '../../store/store.js'
</script>

<template>
	<div class="anonymization-widget">
		<!-- Drop zone -->
		<div class="upload-area">
			<div
				class="drop-zone"
				:class="{ dragging: isDragging }"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop.prevent="handleDrop"
				@click="$refs.fileInput.click()">
				<img :src="uploadIcon" alt="" class="upload-icon">
				<div class="drop-content">
					<p class="drop-title">
						{{ t('docudesk', 'Drag and drop one or more documents') }}
					</p>
					<p class="drop-subtitle">
						{{ t('docudesk', 'Only Word (.docx), ODT, PDF or TXT files are supported. Maximum file size 500 MB.') }}
					</p>
					<span class="fake-button">
						{{ t('docudesk', '+ Select files') }}
					</span>
				</div>
				<input
					ref="fileInput"
					type="file"
					multiple
					accept=".docx,.odt,.txt,.pdf,.eml,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,text/plain,application/pdf,message/rfc822"
					class="file-input"
					@change="handleFileSelect">
			</div>
		</div>

		<!-- Recent documents -->
		<section v-if="recentLoading || recentItems.length > 0" class="recent-section">
			<h3 class="recent-section__title">
				{{ t('docudesk', 'Recent documents') }}
			</h3>
			<div v-if="recentLoading" class="recent-section__loading">
				<NcLoadingIcon :size="24" />
			</div>
			<div v-else class="recent-section__grid">
				<DdDocumentCard
					v-for="item in recentItems"
					:key="item.fileId || item.path"
					:item="item"
					@click="openRecent" />
			</div>
		</section>

		<!-- Upload dialog (single document or dossier) -->
		<NcDialog
			v-if="showDossierDialog"
			:name="dialogName"
			:can-close="!dossierSubmitting"
			size="normal"
			@closing="cancelDossier">
			<div class="dossier-dialog">
				<!-- Single file: read-only filename, no dossier name -->
				<div v-if="isSingleFile" class="single-file">
					<span class="single-file__label">{{ t('docudesk', 'Document') }}</span>
					<span class="single-file__name">{{ singleFileName }}</span>
					<NcNoteCard v-if="dossierError" type="error">
						{{ dossierError }}
					</NcNoteCard>
				</div>
				<!-- Multiple files: dossier name input -->
				<template v-else>
					<NcTextField
						ref="dossierInput"
						:value.sync="dossierName"
						:label="t('docudesk', 'Dossier name')"
						:placeholder="t('docudesk', 'e.g. Buurtinitiatieven 2026')"
						:disabled="dossierSubmitting"
						:error="!!dossierError"
						:helper-text="dossierError"
						@keyup.enter="confirmDossier" />
					<NcNoteCard type="info">
						{{ t('docudesk', 'You uploaded multiple documents. Enter a title to automatically create a dossier from them. No title? Then they will stay as separate documents.') }}
					</NcNoteCard>
				</template>

				<!-- Grondslagen toggle: drives whether entities are editable in the viewer -->
				<NcCheckboxRadioSwitch
					type="switch"
					:checked.sync="grondslagen"
					:disabled="dossierSubmitting">
					{{ t('docudesk', 'Establish legal grounds (grondslagen)') }}
				</NcCheckboxRadioSwitch>
				<NcNoteCard type="info">
					{{ t('docudesk', 'When enabled, you can review and adjust the legal grounds for each detected entity before anonymizing. When disabled, default grounds are applied and you can anonymize right away.') }}
				</NcNoteCard>
			</div>
			<template #actions>
				<NcButton type="tertiary" :disabled="dossierSubmitting" @click="cancelDossier">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" :disabled="dossierSubmitting" @click="confirmDossier">
					<template v-if="dossierSubmitting" #icon>
						<NcLoadingIcon :size="18" />
					</template>
					{{ t('docudesk', 'Continue to anonymization') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<script>
import { NcButton, NcCheckboxRadioSwitch, NcDialog, NcLoadingIcon, NcNoteCard, NcTextField } from '@nextcloud/vue'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import DdDocumentCard from '../../components/DdDocumentCard.vue'
import uploadIcon from '../../assets/upload.png'
import { partitionFiles } from '../../services/anonymizationUpload.js'

// Widget only handles upload + the dossier dialog. After upload the user is
// routed to the file viewer (/my-documents host), where `FileViewerSidebar`
// renders detected entities, grondslagen pickers and the anonymise button.

export default {
	name: 'AnonymizationWidget',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		DdDocumentCard,
	},
	data() {
		return {
			isDragging: false,
			showDossierDialog: false,
			pendingFiles: [],
			dossierName: '',
			dossierSubmitting: false,
			dossierError: '',
			grondslagen: true,
			uploadIcon,
			recentItems: [],
			recentLoading: false,
		}
	},
	computed: {
		/**
		 * Display name of the currently logged-in Nextcloud user.
		 *
		 * @return {string} The user's display name, or their uid as fallback, or empty string when unauthenticated.
		 */
		userName() {
			const user = getCurrentUser()
			return user?.displayName || user?.uid || ''
		},
		/**
		 * Time-of-day greeting interpolated with the user's display name.
		 *
		 * Morning: 05:00–11:59. Afternoon: 12:00–17:59. Evening: 18:00–04:59.
		 *
		 * @return {string} Localised greeting like 'Good morning Marco,'.
		 */
		greeting() {
			const hour = new Date().getHours()
			if (hour >= 5 && hour < 12) {
				return t('docudesk', 'Good morning {name},', { name: this.userName })
			}
			if (hour >= 12 && hour < 18) {
				return t('docudesk', 'Good afternoon {name},', { name: this.userName })
			}
			return t('docudesk', 'Good evening {name},', { name: this.userName })
		},
		/**
		 * Whether the upload modal is showing a single document (no dossier).
		 *
		 * @return {boolean} True when exactly one file is pending.
		 */
		isSingleFile() {
			return this.pendingFiles.length === 1
		},
		/**
		 * Filename of the single pending file, shown read-only in the modal.
		 *
		 * @return {string} The first pending file's name, or empty string.
		 */
		singleFileName() {
			return this.pendingFiles[0]?.name || ''
		},
		/**
		 * Title of the upload modal: single-file vs dossier wording.
		 *
		 * @return {string} Localised dialog title.
		 */
		dialogName() {
			return this.isSingleFile
				? t('docudesk', 'Anonymize document')
				: t('docudesk', 'Create dossier')
		},
	},
	mounted() {
		this.loadRecent()
	},
	methods: {
		/**
		 * Fetch the most-recent anonymized files and dossier folders under
		 * /DocuDesk/ for the "Recent documents" cards. Read-only — does not
		 * touch the My Documents store's navigation state.
		 *
		 * @return {Promise<void>}
		 */
		async loadRecent() {
			this.recentLoading = true
			try {
				this.recentItems = await myDocumentsStore.fetchRecentAnonymized(4)
			} catch (err) {
				console.error('Failed to load recent documents:', err)
				this.recentItems = []
			} finally {
				this.recentLoading = false
			}
		},
		/**
		 * Open a recent item: dossier folder → navigate to that folder in
		 * My Documents; anonymized file → open in the file viewer + route
		 * to My Documents host.
		 *
		 * @param {object} item Recent item from `loadRecent`.
		 * @return {Promise<void>}
		 */
		async openRecent(item) {
			if (!item) return
			if (item.isFolder) {
				// Await the fetch before routing. `MyDocumentsIndex.mounted`
				// fires a no-arg `fetchDocuments()` that resolves against
				// `currentPath`; routing before this fetch sets `currentPath`
				// to the dossier makes that mount-time refetch load the root
				// instead, leaving `documents` (root) out of sync with
				// `currentPath` (dossier) so the dossier sidebar shows the
				// wrong files.
				await myDocumentsStore.fetchDocuments(item.path)
				this.gotoViewer()
				return
			}
			fileViewerStore.open({
				fileId: item.fileId,
				fileName: item.fileName,
				mimeType: item.mimeType,
				path: item.path,
			})
			this.gotoViewer()
		},
		/**
		 * Drop handler for the drag-and-drop zone.
		 * Delegates to dispatchFiles so the dossier-dialog logic applies.
		 *
		 * @param {DragEvent} event Native drop event from the drop zone.
		 * @return {void}
		 */
		handleDrop(event) {
			this.isDragging = false
			const files = event.dataTransfer?.files
			if (files && files.length > 0) {
				const filtered = this.filterAllowed(files)
				if (filtered.length > 0) {
					this.dispatchFiles(filtered)
				}
			}
		},
		/**
		 * Change handler for the hidden file input.
		 * Resets the input value so the same file(s) can be picked again.
		 *
		 * @param {Event} event Native change event from the file input.
		 * @return {void}
		 */
		handleFileSelect(event) {
			const files = event.target?.files
			if (files && files.length > 0) {
				const filtered = this.filterAllowed(files)
				if (filtered.length > 0) {
					this.dispatchFiles(filtered)
				}
			}
			event.target.value = ''
		},
		/**
		 * Drop files whose extension/MIME isn't in the supported set.
		 * Surfaces a toast naming each rejected file so the user knows why
		 * nothing happened for that file.
		 *
		 * @param {FileList | File[]} files Incoming files from drop or input.
		 * @return {File[]} Accepted subset.
		 */
		filterAllowed(files) {
			const { accepted, rejected } = partitionFiles(files)
			if (rejected.length > 0) {
				const names = rejected.map((f) => f.name).join(', ')
				showError(t('docudesk', 'Only Word (.docx), ODT, PDF and TXT files are supported. Skipped: {names}', { names }))
			}
			return accepted
		},
		/**
		 * Always open the upload modal so the user confirms the grondslagen
		 * choice before anonymization, regardless of file count:
		 *   - single file → read-only filename, no dossier name.
		 *   - 2+ files → dossier-name input.
		 * The actual upload + viewer routing happens on confirm.
		 *
		 * @param {File[] | FileList} fileList Files from drop or input.
		 * @return {void}
		 */
		dispatchFiles(fileList) {
			const files = Array.from(fileList)
			if (files.length === 0) {
				return
			}
			this.openDossierDialog(files)
		},
		/**
		 * Route to the file-viewer host page when not already there.
		 * Hash-mode router throws NavigationDuplicated if we push the same
		 * route — swallowed so the upload flow stays clean.
		 *
		 * @return {void}
		 */
		gotoViewer() {
			if (this.$route?.name === 'MyDocuments') {
				return
			}
			this.$router.push({ name: 'MyDocuments' }).catch(() => { /* duplicate nav */ })
		},
		/**
		 * Open the upload dialog with a fresh state: empty dossier name,
		 * grondslagen defaulting to AAN. Focuses the dossier-name input on
		 * next tick when present (multi-file only).
		 *
		 * @param {File[]} files Files pending upload.
		 */
		openDossierDialog(files) {
			this.pendingFiles = files
			this.dossierName = ''
			this.dossierError = ''
			this.grondslagen = true
			this.showDossierDialog = true
			this.$nextTick(() => {
				this.$refs.dossierInput?.focus?.()
			})
		},
		/**
		 * Confirm handler for the dossier dialog. With a title the files are
		 * grouped into a new dossier folder and bound to OpenRegister; with
		 * no title each file is uploaded loose under /DocuDesk/, matching
		 * the single-file flow. Keeps the dialog open on failure so the
		 * user sees the error inline.
		 */
		async confirmDossier() {
			const name = this.dossierName.trim()
			this.dossierSubmitting = true
			this.dossierError = ''
			try {
				// Capture metadata before the store consumes the File blobs —
				// used to seed the file viewer afterwards.
				const firstMime = this.pendingFiles[0]?.type || ''
				const before = anonymizationStore.files.length

				if (name) {
					await anonymizationStore.addFilesAsDossier(this.pendingFiles, name)

					// Bind the WebDAV folder to an OpenRegister dossier
					// object (PROPFIND + POST). Best-effort: files are
					// uploaded fine regardless of OR binding, so we surface
					// the error in the dialog but don't roll the upload back.
					try {
						await anonymizationStore.bindDossier(name)
					} catch (err) {
						console.error('Failed to bind dossier to OpenRegister:', err)
					}

					// Switch the left-hand navigation to the new dossier
					// folder so `FolderFilesNavigation` lists every file we
					// just put inside it.
					try {
						await myDocumentsStore.fetchDocuments(`/DocuDesk/${name}`)
					} catch (err) {
						console.error('Failed to open dossier folder:', err)
					}
				} else {
					await anonymizationStore.addFiles(this.pendingFiles)
				}

				// Open the first uploaded file in the viewer.
				const firstEntry = anonymizationStore.files[before]
				if (firstEntry && firstEntry.fileId) {
					fileViewerStore.open({
						fileId: firstEntry.fileId,
						fileName: firstEntry.name,
						mimeType: firstMime,
						path: firstEntry.filePath,
					}, { grondslagen: this.grondslagen })
					this.gotoViewer()
				}

				this.closeDossierDialog()
			} catch (err) {
				this.dossierError = err?.response?.data?.error || err?.message || 'Failed to upload'
			} finally {
				this.dossierSubmitting = false
			}
		},
		/**
		 * Cancel handler — ignored while a submit is in flight to avoid
		 * leaving the store in a half-created state.
		 */
		cancelDossier() {
			if (this.dossierSubmitting) return
			this.closeDossierDialog()
		},
		/** Reset all dialog state back to its initial values. */
		closeDossierDialog() {
			this.showDossierDialog = false
			this.pendingFiles = []
			this.dossierName = ''
			this.dossierError = ''
			this.grondslagen = true
		},
	},
}
</script>

<style scoped>
.anonymization-widget {
	/* Theme-aware mid-contrast grey for the drop-zone's dashed border. */
	--dd-color-dark-grey: var(--color-border-maxcontrast, #61616c);
	display: flex;
	flex-direction: column;
	padding: 20px;
	max-width: 900px;
	margin-inline: auto;
	width: 100%;
}

.page-title {
	margin: 0 0 16px 0;
}

.upload-area {
	padding: 4px 0;
}

.drop-zone {
	width: 100%;
	display: flex;
	align-items: center;
	gap: 24px;
	border: 2px dashed var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 32px;
	background-color: var(--dd-surface, #fff);
	box-shadow: var(--dd-shadow-panel);
	cursor: pointer;
	transition: border-color 0.2s, background-color 0.2s;
}

.drop-zone:hover {
	border-color: var(--color-primary);
}

.drop-zone.dragging {
	border-color: var(--color-primary);
	background-color: #fff;
}

.upload-icon {
	width: 107px;
	height: 103px;
	flex-shrink: 0;
	object-fit: contain;
}

.drop-content {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	gap: 8px;
	text-align: left;
}

.drop-title {
	margin: 0;
	font-size: 1rem;
	font-weight: 600;
	color: var(--color-main-text);
}

.drop-subtitle {
	margin: 0;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.fake-button {
	margin-top: 4px;
	padding: 8px 16px;
	border-radius: var(--border-radius);
	background-color: var(--color-primary-element);
	color: var(--color-primary-element-text);
	font-size: 0.9rem;
	font-weight: 500;
	white-space: nowrap;
}

.file-input {
	display: none;
}

.dossier-dialog {
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding: 20px;
}

.dossier-dialog :deep(.notecard) {
	margin: 0;
}

.single-file {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.single-file__label {
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.single-file__name {
	font-weight: 600;
	color: var(--color-main-text);
	word-break: break-word;
}

.recent-section {
	margin-top: 24px;
}

.recent-section__title {
	margin: 0 0 12px 0;
	font-size: 1rem;
	font-weight: 600;
	color: var(--color-main-text);
}

.recent-section__loading {
	display: flex;
	justify-content: center;
	padding: 12px;
}

.recent-section__grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	grid-auto-rows: 1fr;
	gap: 16px;
}

</style>
