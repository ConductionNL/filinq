<script setup>
import { translate as t } from '@nextcloud/l10n'
import {
	anonymizationStore,
	fileViewerStore,
	myDocumentsStore,
} from '../../store/store.js'
</script>

<template>
	<div
		class="anonymization-widget"
		:class="{ 'anonymization-widget--tile': !inApp }">
		<!-- Drop zone -->
		<div class="upload-area">
			<!--
				Drag-and-drop is a pointer-only affordance by nature, so the file
				picker is opened by a REAL <button> rather than by a click handler
				on this wrapper. The wrapper used to carry `@click` while the
				<input type="file"> was `display: none`, which left keyboard users
				with no way at all to reach the picker (WCAG 2.1.1).
			-->
			<div
				class="drop-zone"
				:class="{ dragging: isDragging }"
				@dragover.prevent="isDragging = true"
				@dragleave.prevent="isDragging = false"
				@drop.prevent="handleDrop">
				<img :src="uploadIcon" alt="" class="upload-icon" />
				<div class="drop-content">
					<p class="drop-title">
						{{ t('filinq', 'Drag and drop one or more documents') }}
					</p>
					<p class="drop-subtitle">
						{{
							t(
								'filinq',
								'Only Word (.docx), ODT, PDF or TXT files are supported. Maximum file size 500 MB.',
							)
						}}
					</p>
					<button
						type="button"
						class="fake-button"
						@click="$refs.fileInput.click()">
						{{ t('filinq', '+ Select files') }}
					</button>
				</div>
				<input
					ref="fileInput"
					type="file"
					multiple
					:aria-label="t('filinq', 'Select files to anonymise')"
					accept=".docx,.odt,.txt,.pdf,.eml,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,text/plain,application/pdf,message/rfc822"
					class="file-input"
					@change="handleFileSelect" />
			</div>
		</div>

		<!-- Recent documents -->
		<section
			v-if="showRecent && (recentLoading || recentItems.length > 0)"
			class="recent-section">
			<h3 class="recent-section__title">
				{{ t('filinq', 'Recent documents') }}
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

		<!-- Outside the app (Nextcloud dashboard tile) the widget links to Filinq. -->
		<a v-if="!inApp" class="widget-footer" :href="appUrl">
			{{ t('filinq', 'Open Filinq') }}
		</a>

		<!-- Upload dialog (single document or dossier) — src/dialogs/ per ADR-004 -->
		<AnonymizationUploadDialog
			v-if="showDossierDialog"
			v-model:dossierName="dossierName"
			v-model:grondslagen="grondslagen"
			:singleFile="isSingleFile"
			:fileName="singleFileName"
			:submitting="dossierSubmitting"
			:error="dossierError"
			@confirm="confirmDossier"
			@cancel="cancelDossier" />
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcLoadingIcon } from '@nextcloud/vue'
import DdDocumentCard from '../../components/DdDocumentCard.vue'
import AnonymizationUploadDialog from '../../dialogs/AnonymizationUploadDialog.vue'
import uploadIcon from '../../assets/upload.png'
import { dossierPlan, partitionFiles } from '../../services/anonymizationUpload.js'

/**
 * Upload the chosen files: as a new dossier folder bound to OpenRegister
 * when there is a title, otherwise loose under /DocuDesk/.
 *
 * @param {Array<File>} files The files to upload.
 * @param {string} name The dossier title, empty for a loose upload.
 * @return {Promise<void>}
 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#requirement-auto-dossier-on-multi-upload-req-dddmu-005
 */
async function uploadPendingFiles(files, name) {
	if (!name) {
		await anonymizationStore.addFiles(files)
		return
	}

	await anonymizationStore.addFilesAsDossier(files, name)

	// Bind the WebDAV folder to an OpenRegister dossier
	// object (PROPFIND + POST). Best-effort: files are
	// uploaded fine regardless of OR binding, so we surface
	// the error in the dialog but don't roll the upload back.
	try {
		await anonymizationStore.bindDossier(name)
	} catch (err) {
		console.error('Failed to bind dossier to OpenRegister:', err)
	}

	await openDossierFolder(name)
}

/**
 * What the dialog shows when the upload itself threw.
 *
 * @param {object} err What was thrown.
 * @return {string} The server's message, the error's message, or a generic one.
 */
function uploadFailureMessage(err) {
	return err?.response?.data?.error || err?.message || 'Failed to upload'
}

/**
 * Switch the left-hand navigation to the new dossier folder so
 * `FolderFilesNavigation` lists every file just put inside it.
 *
 * @param {string} name The dossier title.
 * @return {Promise<void>}
 */
async function openDossierFolder(name) {
	try {
		await myDocumentsStore.fetchDocuments(`/DocuDesk/${name}`)
	} catch (err) {
		console.error('Failed to open dossier folder:', err)
	}
}

// Widget only handles upload + the dossier dialog. After upload the user is
// routed to the file viewer (/my-documents host), where `FileViewerSidebar`
// renders detected entities, grondslagen pickers and the anonymise button.

export default {
	name: 'AnonymizationWidget',
	components: {
		AnonymizationUploadDialog,
		NcLoadingIcon,
		DdDocumentCard,
	},

	// The Nextcloud dashboard passes the tile title, which its own header shows.
	inheritAttrs: false,

	props: {
		/**
		 * Show the "Recent documents" cards under the drop zone. Only the
		 * Anonymisation page has the room; the dashboard cell and the
		 * Nextcloud tile have a fixed height.
		 */
		showRecent: {
			type: Boolean,
			default: false,
		},
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
		 * Whether the widget runs inside Filinq. The Nextcloud dashboard tile
		 * mounts it without the app's router.
		 *
		 * @return {boolean}
		 * @spec openspec/changes/one-anonymisation-upload-widget/specs/anonymization/spec.md#requirement-after-an-upload-the-user-must-land-on-my-documents-with-the-first-file-open
		 */
		inApp() {
			return Boolean(this.$router)
		},

		/**
		 * Filinq's start page, for the link outside the app.
		 *
		 * @return {string}
		 * @spec openspec/changes/one-anonymisation-upload-widget/specs/anonymization/spec.md#requirement-after-an-upload-the-user-must-land-on-my-documents-with-the-first-file-open
		 */
		appUrl() {
			return generateUrl('/apps/filinq')
		},

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
		 * @spec exclude decorative time-of-day salutation above the upload zone; the widget's specified behaviour (drag-and-drop upload, progress, result display) is at openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08, which mandates no greeting
		 */
		greeting() {
			const hour = new Date().getHours()
			if (hour >= 5 && hour < 12) {
				return t('filinq', 'Good morning {name},', { name: this.userName })
			}
			if (hour >= 12 && hour < 18) {
				return t('filinq', 'Good afternoon {name},', {
					name: this.userName,
				})
			}
			return t('filinq', 'Good evening {name},', { name: this.userName })
		},

		/**
		 * Whether the upload modal is showing a single document (no dossier).
		 *
		 * @return {boolean} True when exactly one file is pending.
		 */
		isSingleFile() {
			return !dossierPlan(this.pendingFiles, '').asksDossierName
		},

		/**
		 * Filename of the single pending file, shown read-only in the modal.
		 *
		 * @return {string} The first pending file's name, or empty string.
		 */
		singleFileName() {
			return this.pendingFiles[0]?.name || ''
		},
	},

	mounted() {
		if (this.showRecent) {
			this.loadRecent()
		}
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
		 * @spec openspec/specs/anonymization/spec.md#requirement-anonymization-pipeline-ui-req-anon-08
		 */
		filterAllowed(files) {
			const { accepted, rejected } = partitionFiles(files)
			if (rejected.length > 0) {
				const names = rejected.map((f) => f.name).join(', ')
				showError(
					t(
						'filinq',
						'Only Word (.docx), ODT, PDF and TXT files are supported. Skipped: {names}',
						{ names },
					),
				)
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
		 * route — swallowed so the upload flow stays clean. Outside the app
		 * the browser opens Filinq's My documents page instead.
		 *
		 * @return {void}
		 * @spec openspec/changes/one-anonymisation-upload-widget/specs/anonymization/spec.md#requirement-after-an-upload-the-user-must-land-on-my-documents-with-the-first-file-open
		 */
		gotoViewer() {
			if (!this.inApp) {
				window.location.assign(generateUrl('/apps/filinq/my-documents'))
				return
			}
			if (this.$route?.name === 'MyDocuments') {
				return
			}
			this.$router.push({ name: 'MyDocuments' }).catch(() => {
				/* duplicate nav */
			})
		},

		/**
		 * Open the upload dialog with a fresh state: empty dossier name,
		 * grondslagen defaulting to AAN. AnonymizationUploadDialog focuses
		 * its own name field when it mounts (multi-file only), so the parent
		 * no longer reaches across the boundary for it.
		 *
		 * @param {File[]} files Files pending upload.
		 */
		openDossierDialog(files) {
			this.pendingFiles = files
			this.dossierName = ''
			this.dossierError = ''
			this.grondslagen = true
			this.showDossierDialog = true
		},

		/**
		 * Confirm handler for the dossier dialog. With a title the files are
		 * grouped into a new dossier folder and bound to OpenRegister; with
		 * no title each file is uploaded loose under /DocuDesk/, matching
		 * the single-file flow. Keeps the dialog open on failure so the
		 * user sees the error inline.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#requirement-auto-dossier-on-multi-upload-req-dddmu-005
		 */
		async confirmDossier() {
			const name = dossierPlan(this.pendingFiles, this.dossierName).dossierName
			this.dossierSubmitting = true
			this.dossierError = ''
			try {
				// Capture metadata before the store consumes the File blobs —
				// used to seed the file viewer afterwards.
				const upload = {
					firstMime: this.pendingFiles[0]?.type || '',
					before: anonymizationStore.files.length,
				}

				await uploadPendingFiles(this.pendingFiles, name)
				this.finishUpload(upload)
			} catch (err) {
				this.dossierError = uploadFailureMessage(err)
			} finally {
				this.dossierSubmitting = false
			}
		},

		/**
		 * After the upload: keep the dialog open on a failed entry, otherwise
		 * open the first uploaded file and close the dialog.
		 *
		 * @param {{before: number, firstMime: string}} upload The queue length before the upload and the first file's type.
		 * @spec openspec/changes/dossier-management-ui/specs/dossier-management-ui/spec.md#requirement-auto-dossier-on-multi-upload-req-dddmu-005
		 */
		finishUpload(upload) {
			// The store marks a failed upload on its entry instead of
			// throwing: keep the dialog open and show that error.
			const failed = anonymizationStore.files
				.slice(upload.before)
				.find((entry) => entry.status === 'error')
			if (failed) {
				this.dossierError = failed.error || 'Failed to upload'
				return
			}

			this.openFirstUploaded(upload)
			this.closeDossierDialog()
		},

		/**
		 * Open the first uploaded file in the viewer, when it has a file id.
		 *
		 * @param {{before: number, firstMime: string}} upload The queue length before the upload and the first file's type.
		 */
		openFirstUploaded(upload) {
			const firstEntry = anonymizationStore.files[upload.before]
			if (!firstEntry?.fileId) {
				return
			}
			fileViewerStore.open(
				{
					fileId: firstEntry.fileId,
					fileName: firstEntry.name,
					mimeType: upload.firstMime,
					path: firstEntry.filePath,
				},
				{ grondslagen: this.grondslagen },
			)
			this.gotoViewer()
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

.anonymization-widget--tile {
	padding: 0;
}

.anonymization-widget--tile .drop-zone {
	padding: 16px;
	gap: 12px;
}

.anonymization-widget--tile .upload-icon {
	width: 64px;
	height: 62px;
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
	background-color: var(--color-main-background);
	box-shadow: var(--dd-shadow-panel);
	/* Not a click target any more — the picker is opened by .fake-button. */
	transition:
		border-color 0.2s,
		background-color 0.2s;
}

.drop-zone:hover {
	border-color: var(--color-primary);
}

.drop-zone.dragging {
	border-color: var(--color-primary);
	background-color: var(--color-background-hover);
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
	text-align: start;
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

/* A real <button> now, so the browser's own border/font defaults are reset
   here to keep the previous appearance byte-for-byte. */
.fake-button {
	margin-top: 4px;
	padding: 8px 16px;
	border: none;
	border-radius: var(--border-radius);
	background-color: var(--color-primary-element);
	color: var(--color-primary-element-text);
	font-family: inherit;
	font-size: 0.9rem;
	font-weight: 500;
	white-space: nowrap;
	cursor: pointer;
}

.fake-button:focus-visible {
	outline: 2px solid var(--color-primary-element);
	outline-offset: 2px;
}

.file-input {
	display: none;
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

.widget-footer {
	display: block;
	text-align: center;
	padding: 8px;
	border-top: 1px solid var(--color-border);
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	text-decoration: none;
}

.widget-footer:hover {
	background-color: var(--color-background-hover);
	color: var(--color-main-text);
}

/* WCAG 2.2 SC 2.3.3 — users who ask the OS for reduced motion get the state
   change without the tween. */
@media (prefers-reduced-motion: reduce) {
	.drop-zone {
		transition: none;
	}
}
</style>
