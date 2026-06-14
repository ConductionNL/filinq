<script setup>
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { fileViewerStore, anonymizationStore } from '../store/store.js'
</script>

<template>
	<!-- The native NcAppSidebar X-button is hidden via :deep() CSS below.
	     Closing the entity review while the user is still picking which
	     entities to anonymise would lose unsaved selections — the only
	     exit is the FileViewerPage "Back" button, which closes the viewer
	     and the sidebar together via fileViewerStore.close(). -->
	<NcAppSidebar
		v-if="fileViewerStore.currentFile"
		:name="sidebarTitle"
		:subname="sidebarSubtitle">
		<div class="file-viewer-sidebar">
			<!-- Loading state: skeletons while ensureExtracted resolves. -->
			<div v-if="isLoading" class="entities-list">
				<DdEntityCard v-for="i in 4" :key="'skeleton-' + i" loading />
			</div>

			<!-- Error state. -->
			<NcNoteCard v-else-if="entry && entry.status === 'error'" type="error">
				{{ entry.error || t('docudesk', 'Failed to load entities') }}
			</NcNoteCard>

			<!-- Success state for files that finished anonymising. -->
			<NcNoteCard v-else-if="entry && entry.status === 'completed' && entry.anonymizedFilePath" type="success">
				<div>{{ t('docudesk', 'Anonymisation complete') }}</div>
				<div class="muted">
					{{ n('docudesk', '%n entity replaced', '%n entities replaced', entry.replacementCount || 0) }}
				</div>
				<a :href="downloadUrl" download class="download-link">
					{{ t('docudesk', 'Download anonymised file') }}
				</a>
			</NcNoteCard>

			<!-- Anonymised-document view: a read-only list resolved from the
			     `[<TYPE>: <entity_id>]` placeholders baked into the file.
			     Original values stay hidden behind an explicit reveal so
			     opening the result doesn't silently de-anonymise it. -->
			<div v-else-if="entry && entry.viewMode === 'anonymized'" class="entities-list">
				<NcNoteCard type="warning" class="reveal-note">
					<div>{{ t('docudesk', 'These items were removed from this document.') }}</div>
					<NcButton type="tertiary" class="reveal-toggle" @click="revealValues = !revealValues">
						{{ revealValues
							? t('docudesk', 'Hide original values')
							: t('docudesk', 'Reveal original values') }}
					</NcButton>
				</NcNoteCard>
				<DdEntityCard
					v-for="(item, idx) in entry.entities"
					:key="'anon-' + idx"
					:item="item"
					mode="anonymized"
					:reveal-values="revealValues" />
			</div>

			<!-- Empty state. -->
			<div v-else-if="entry && entry.entities.length === 0" class="empty-state">
				<p>{{ t('docudesk', 'No entities detected in this file.') }}</p>
			</div>

			<!-- Entity list — one card per detected entity. -->
			<div v-else-if="entry" class="entities-list">
				<DdEntityCard
					v-for="(item, idx) in entry.entities"
					:key="'entity-' + idx"
					:item="item"
					mode="review"
					:bases-options="basesOptions"
					@toggle="anonymizationStore.toggleEntity(entry, idx)"
					@set-bases="anonymizationStore.setEntityBases(entry, idx, $event)" />
			</div>
		</div>

		<!-- Sticky action bar — `NcAppSidebar` v8 has no `footer` slot, so
		     the button rides inside the default slot and stays glued to
		     the viewport bottom via `position: sticky`. -->
		<div v-if="entry && entry.status === 'extracted'" class="sidebar-action-bar">
			<NcButton
				type="primary"
				:disabled="includedCount === 0 || isAnonymising"
				@click="onAnonymise">
				<template v-if="isAnonymising" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ n('docudesk', 'Anonymize %n entity', 'Anonymize %n entities', includedCount) }}
			</NcButton>
		</div>
	</NcAppSidebar>
</template>

<script>
import { NcAppSidebar, NcButton, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { generateRemoteUrl } from '@nextcloud/router'
import DdEntityCard from '../components/DdEntityCard.vue'

// Woo Art. 5 grondslagen — duplicated from EntityReviewTable so we don't
// reach into a sibling component's internals. Both lists must stay in
// sync until a production page fetches the bases register from the
// dossier register (Wave 1.1 plumbing).
const BASES_OPTIONS = [
	'persoonsgegevens',
	'bijzondere-persoonsgegevens',
	'strafrechtelijk',
	'bedrijfs-fabricagegegevens',
	'onevenredige-benadeling',
	'nationale-veiligheid',
]

export default {
	name: 'FileViewerSidebar',
	components: {
		NcAppSidebar,
		NcButton,
		NcLoadingIcon,
		NcNoteCard,
		DdEntityCard,
	},
	data() {
		return {
			basesOptions: BASES_OPTIONS,
			loadingFileId: null,
			// Anonymised-document view shows the original values by default —
			// the whole point of the panel is to review what was removed. The
			// toggle still lets the user hide them again (e.g. screen-sharing).
			revealValues: true,
		}
	},
	computed: {
		/**
		 * Id of the file currently open in the viewer, or null. Watched to
		 * trigger entity loading — a plain instance computed fires reliably
		 * where a string-path watch on the imported store does not.
		 *
		 * @return {number|null}
		 */
		currentFileId() {
			return fileViewerStore.currentFile?.fileId ?? null
		},
		/**
		 * The queue entry for the file currently displayed in the viewer.
		 * Updated reactively whenever `anonymizationStore.files` mutates
		 * or the viewer opens a different file.
		 *
		 * @return {object|undefined} Queue entry or undefined when not yet loaded.
		 */
		entry() {
			const file = fileViewerStore.currentFile
			if (!file) {
				return undefined
			}
			return anonymizationStore.findByFileId(file.fileId)
		},
		/**
		 * True while `ensureExtracted` is running for the current file —
		 * drives the skeleton placeholders.
		 *
		 * @return {boolean}
		 */
		isLoading() {
			const file = fileViewerStore.currentFile
			if (!file) {
				return false
			}
			if (this.loadingFileId === file.fileId) {
				return true
			}
			// Entry exists but extraction is still in flight (initial upload).
			return this.entry?.status === 'extracting' || this.entry?.status === 'uploading'
		},
		/**
		 * True while the anonymise PATCH+POST round-trip is running for
		 * this file.
		 *
		 * @return {boolean}
		 */
		isAnonymising() {
			return this.entry?.status === 'anonymising'
		},
		/**
		 * Number of entities the user has currently marked for anonymisation
		 * — drives the action-button label and disabled state.
		 *
		 * @return {number}
		 */
		includedCount() {
			return (this.entry?.entities || []).filter((e) => e.included !== false).length
		},
		/**
		 * Sidebar header title — detected-entity count once extraction has
		 * produced a result, otherwise fall back to the file name.
		 *
		 * @return {string}
		 */
		sidebarTitle() {
			if (this.entry?.viewMode === 'anonymized') {
				return n(
					'docudesk',
					'%n item anonymised',
					'%n items anonymised',
					this.entry.entities.length,
				)
			}
			if (this.entry && Array.isArray(this.entry.entities)) {
				return n(
					'docudesk',
					'%n entity detected',
					'%n entities detected',
					this.entry.entities.length,
				)
			}
			return fileViewerStore.currentFile?.fileName || t('docudesk', 'Entities')
		},
		/**
		 * Sidebar subtitle — loading/error status while extraction is still
		 * resolving, otherwise the "verify the auto-detected entities"
		 * disclaimer shown during the review step.
		 *
		 * @return {string}
		 */
		sidebarSubtitle() {
			if (this.entry?.viewMode === 'anonymized') {
				return t('docudesk', 'Resolved from the GDPR register.')
			}
			if (this.isLoading) {
				return t('docudesk', 'Detecting entities…')
			}
			if (this.entry?.status === 'error') {
				return t('docudesk', 'Failed to load')
			}
			if (this.entry && Array.isArray(this.entry.entities) && this.entry.entities.length > 0) {
				return t('docudesk', 'Automatic detection. Always verify yourself.')
			}
			return ''
		},
		/**
		 * WebDAV download URL for the anonymised result, when available.
		 *
		 * @return {string}
		 */
		downloadUrl() {
			const path = this.entry?.anonymizedFilePath
			if (!path) {
				return ''
			}
			const parts = path.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				const relativePath = parts.slice(filesIndex + 1).join('/')
				return generateRemoteUrl('webdav') + '/' + relativePath
			}
			return generateRemoteUrl('webdav')
		},
	},
	watch: {
		/**
		 * React to the viewer opening a different file. We watch the local
		 * `currentFileId` computed rather than `fileViewerStore.currentFile`
		 * directly: the store is a `<script setup>` import, so it is NOT a
		 * `this`-property and a string-path watch on it never fires.
		 *
		 * @param {number|null} fileId New file id (null when viewer closed).
		 */
		currentFileId: {
			handler(fileId) {
				const file = fileViewerStore.currentFile
				if (!fileId || !file) {
					return
				}
				this.loadEntitiesForCurrentFile(file)
			},
			immediate: true,
		},
	},
	methods: {
		/**
		 * Ensure the store has entities for the currently open file.
		 * Skips when the entry is already loaded or being loaded.
		 *
		 * @param {object} file fileViewerStore.currentFile descriptor.
		 * @return {Promise<void>}
		 */
		async loadEntitiesForCurrentFile(file) {
			const existing = anonymizationStore.findByFileId(file.fileId)
			if (existing) {
				return
			}
			this.loadingFileId = file.fileId
			const meta = {
				fileId: file.fileId,
				fileName: file.fileName,
				path: file.path,
				mimeType: file.mimeType,
			}
			try {
				// Prefer the anonymised-document path: if the file carries
				// `[<TYPE>: <entity_id>]` placeholders we resolve those to the
				// original entities directly. Falls back to detecting PII in a
				// (still un-anonymised) source file.
				const anonymized = await anonymizationStore.loadAnonymizedEntities(meta)
				if (!anonymized) {
					await anonymizationStore.ensureExtracted(meta)
				}
			} finally {
				if (this.loadingFileId === file.fileId) {
					this.loadingFileId = null
				}
			}
		},
		/**
		 * Anonymise the current entry. Wraps the store action so the
		 * footer button has a single click handler. On success, attach
		 * the anonymised counterpart to the viewer store and switch over
		 * to it — the user can flip back via the toggle in
		 * `FileViewerPage`. The entry is still findable via
		 * `findByFileId` because the getter also matches
		 * `anonymizedFileId`.
		 *
		 * @return {Promise<void>}
		 */
		async onAnonymise() {
			if (!this.entry) {
				return
			}
			await anonymizationStore.anonymiseEntry(this.entry)
			if (this.entry.status === 'completed' && this.entry.anonymizedFileId) {
				fileViewerStore.setAnonymizedVariant({
					fileId: this.entry.anonymizedFileId,
					fileName: this.entry.anonymizedFileName || this.entry.name,
					mimeType: fileViewerStore.originalFile?.mimeType || '',
					path: this.entry.anonymizedFilePath,
				})
			}
		},
	},
}
</script>

<style lang="scss" scoped>

/* Hide the built-in X close button — the user must finish the entity
 * review (or use the viewer's Back button) before the sidebar unmounts.
 * NcAppSidebar v8 exposes no prop for this, so we override the lib's
 * scoped class directly. */
:deep(.app-sidebar__close) {
	display: none !important;
}

/* Solid white header to match the viewer's FileViewerHeader. The sidebar
 * body keeps the translucent card background (set above); only the header
 * band is opaque white so the two headers read as one toolbar row. */
:deep(.app-sidebar-header) {
	/* `.app-sidebar` re-points --color-main-background to white-54, so a var
	 * reference here would stay translucent. The card design is white-on-white
	 * regardless of theme, so use opaque white for the header band. */
	background: #fff;
	border-top-left-radius: 20px;
	border-top-right-radius: 20px;
}

.entities-summary {
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
	margin-bottom: 8px;
}

.entities-list {
	display: flex;
	flex-direction: column;
}

.reveal-note .reveal-toggle {
	margin-top: 6px;
}

.empty-state {
	padding: 24px 12px;
	text-align: center;
	color: var(--color-text-maxcontrast);
}

.muted {
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	margin-top: 4px;
}

.download-link {
	display: inline-block;
	margin-top: 8px;
	color: var(--color-primary);
	font-weight: 500;
	text-decoration: none;

	&:hover {
		text-decoration: underline;
	}
}

/* Sticky action bar — glues itself to the viewport bottom inside the
 * sidebar's scroll container. `bottom: 0` plus a solid background hides
 * the entity list scrolling underneath. */
.sidebar-action-bar {
	position: sticky;
	bottom: 0;
	z-index: 1;
	padding: 12px 16px;
	margin-top: 12px;
	border-top: 1px solid var(--color-border);
	background-color: var(--color-main-background);
	display: flex;
	justify-content: stretch;

	:deep(.button-vue) {
		width: 100%;
	}
}
</style>
