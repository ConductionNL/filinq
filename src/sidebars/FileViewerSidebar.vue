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
				<div v-for="i in 4" :key="'skeleton-' + i" class="entity-card skeleton-card">
					<DdSkeleton variant="text" :rows="2" />
					<div class="skeleton-row">
						<DdSkeleton variant="row" width="40%" height="24px" />
						<DdSkeleton variant="row" width="32px" height="24px" />
					</div>
				</div>
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

			<!-- Empty state. -->
			<div v-else-if="entry && entry.entities.length === 0" class="empty-state">
				<p>{{ t('docudesk', 'No entities detected in this file.') }}</p>
			</div>

			<!-- Entity list — one card per detected entity. -->
			<div v-else-if="entry" class="entities-list">
				<div
					v-for="(item, idx) in entry.entities"
					:key="'entity-' + idx"
					class="entity-card"
					:class="{ 'entity-card--excluded': !item.included }">
					<div class="entity-card__header">
						<input
							type="checkbox"
							class="entity-card__checkbox"
							:checked="item.included"
							:aria-label="t('docudesk', 'Include in anonymisation')"
							@change="anonymizationStore.toggleEntity(entry, idx)">
						<span class="entity-card__type">{{ item.type }}</span>
						<span class="entity-card__confidence">
							{{ ((item.confidence || 0) * 100).toFixed(0) }}%
						</span>
					</div>
					<div class="entity-card__value" :title="item.value">
						{{ item.value }}
					</div>
					<div class="entity-card__controls">
						<NcSelect
							class="entity-card__bases"
							:value="item._decisionBases || []"
							:options="basesOptions"
							:multiple="true"
							:input-label="t('docudesk', 'Grondslagen')"
							:placeholder="t('docudesk', 'Pick grondslagen…')"
							:disabled="!hasRelation(item)"
							@input="anonymizationStore.setEntityBases(entry, idx, $event)" />
					</div>
					<div v-if="item._patchError" class="entity-card__error" :title="item._patchError">
						{{ item._patchError }}
					</div>
				</div>
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
import { NcAppSidebar, NcButton, NcLoadingIcon, NcNoteCard, NcSelect } from '@nextcloud/vue'
import { generateRemoteUrl } from '@nextcloud/router'
import DdSkeleton from '../components/DdSkeleton.vue'

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
		NcSelect,
		DdSkeleton,
	},
	data() {
		return {
			basesOptions: BASES_OPTIONS,
			loadingFileId: null,
		}
	},
	computed: {
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
		 * When the viewer opens a different file, make sure the store
		 * holds an entry for it — fetch entities if not.
		 *
		 * @param {object|null} newFile
		 */
		'fileViewerStore.currentFile': {
			handler(newFile) {
				if (!newFile) {
					return
				}
				this.loadEntitiesForCurrentFile(newFile)
			},
			immediate: true,
		},
	},
	methods: {
		/**
		 * Whether an entity has any relation id — bases control only
		 * persists when the entity is backed by an OR relation.
		 *
		 * @param {object} item Entity row.
		 * @return {boolean}
		 */
		hasRelation(item) {
			return Array.isArray(item.relationIds) && item.relationIds.length > 0
		},
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
			try {
				await anonymizationStore.ensureExtracted({
					fileId: file.fileId,
					fileName: file.fileName,
					path: file.path,
					mimeType: file.mimeType,
				})
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
/* Match MainMenu / NcAppContent chrome: rounded card, soft shadow,
 * translucent background. Lib does not expose a variable for radius/shadow,
 * so override directly. */
.app-sidebar {
	--color-main-background: var(--color-white-54, rgba(255, 255, 255, 0.54));
	border-radius: 20px;
	box-shadow: 0 4px 22px -3px rgba(0, 0, 0, 0.08);
	margin-left: 8px;
}

/* Hide the built-in X close button — the user must finish the entity
 * review (or use the viewer's Back button) before the sidebar unmounts.
 * NcAppSidebar v8 exposes no prop for this, so we override the lib's
 * scoped class directly. */
:deep(.app-sidebar__close) {
	display: none !important;
}

.file-viewer-sidebar {
	padding: 12px 16px;
}

.entities-summary {
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
	margin-bottom: 8px;
}

.entities-list {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.entity-card {
	padding: 10px 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	background-color: var(--color-main-background);
	display: flex;
	flex-direction: column;
	gap: 6px;
	transition: opacity 0.15s ease;

	&--excluded {
		opacity: 0.55;
	}
}

.entity-card__header {
	display: flex;
	align-items: center;
	gap: 8px;
}

.entity-card__checkbox {
	flex: 0 0 auto;
}

.entity-card__type {
	flex: 1 1 auto;
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	padding: 2px 8px;
	border-radius: 12px;
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element);
	display: inline-block;
	max-width: max-content;
}

.entity-card__confidence {
	flex: 0 0 auto;
	font-size: 0.8rem;
	color: var(--color-text-maxcontrast);
}

.entity-card__value {
	font-size: 0.95rem;
	font-weight: 500;
	overflow-wrap: anywhere;
}

.entity-card__controls {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.entity-card__bases {
	width: 100%;
}

.entity-card__error {
	color: var(--color-error);
	font-size: 0.75rem;
}

.skeleton-card {
	padding: 12px;
}

.skeleton-row {
	display: flex;
	gap: 8px;
	align-items: center;
	margin-top: 8px;
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
