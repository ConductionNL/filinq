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
		<!-- Header toggle: switches the entity cards between editable review
		     (grondslagen on) and read-only with defaults (off). Lives in the
		     header band's `description` slot so it reads as part of the header.
		     Only shown while reviewing a freshly extracted file — irrelevant
		     for the anonymised/completed views. -->
		<template v-if="showGrondslagenToggle" #description>
			<NcCheckboxRadioSwitch
				type="switch"
				class="grondslagen-toggle"
				:checked="grondslagen"
				@update:checked="fileViewerStore.setGrondslagen($event)">
				{{ t('docudesk', 'Edit legal grounds (grondslagen)') }}
			</NcCheckboxRadioSwitch>
		</template>
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

			<!-- Anonymised file recognised via the source↔anonymised DB link,
			     but its text carries no readable placeholders (e.g. a flattened
			     PDF). The per-entity list can't be shown without a mutating
			     re-extract, so we surface the link summary read-only. -->
			<NcNoteCard
				v-else-if="entry && entry.viewMode === 'anonymized' && entry.detailUnavailable"
				type="success">
				<div>{{ t('docudesk', 'This file is anonymised.') }}</div>
				<div class="muted">
					{{ n('docudesk', '%n item removed', '%n items removed', entry.replacementCount || 0) }}
				</div>
				<div v-if="entry.sourceFileName" class="muted">
					{{ t('docudesk', 'Open {source} to review which items were removed.', { source: entry.sourceFileName }) }}
				</div>
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

			<!-- Add new data panel — edit mode. The user selects text in the
			     document (highlighted as pending), picks a type and optionally
			     grondslagen, then saves it as one new entity. -->
			<div v-else-if="entry && isEditing" class="add-entity-panel">
				<NcNoteCard :type="selectedText ? 'info' : 'warning'">
					{{ selectedText
						? t('docudesk', 'This text will be added to the anonymisation list.')
						: t('docudesk', 'Select text in the document to add it.') }}
				</NcNoteCard>
				<div class="add-entity-panel__field">
					<span class="add-entity-panel__label">{{ t('docudesk', 'Selected text') }}</span>
					<div class="add-entity-panel__selection">
						{{ selectedText || '—' }}
					</div>
				</div>
				<NcSelect
					v-model="newType"
					class="add-entity-panel__select"
					:options="typeOptions"
					:input-label="t('docudesk', 'Type')"
					:placeholder="t('docudesk', 'Pick a type…')" />
				<NcSelect
					v-if="grondslagen"
					v-model="newBases"
					class="add-entity-panel__select"
					:options="basesOptions"
					:multiple="true"
					:input-label="t('docudesk', 'Grondslagen')"
					:placeholder="t('docudesk', 'Pick grondslagen…')" />
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
					:editable="grondslagen"
					:bases-options="basesOptions"
					@toggle="anonymizationStore.toggleEntity(entry, idx)"
					@set-bases="anonymizationStore.setEntityBases(entry, idx, $event)" />
			</div>
		</div>

		<!-- Sticky action bar — `NcAppSidebar` v8 has no `footer` slot, so
		     the button rides inside the default slot and stays glued to
		     the viewport bottom via `position: sticky`. -->
		<div v-if="entry && entry.status === 'extracted'" class="sidebar-action-bar">
			<!-- Edit mode: cancel / save the new entity. -->
			<template v-if="isEditing">
				<NcButton type="tertiary" @click="onCancelEdit">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
				<NcButton
					type="primary"
					:disabled="!canSaveNew"
					@click="onSaveNew">
					{{ t('docudesk', 'Save change') }}
				</NcButton>
			</template>
			<!-- Review mode: open the add-entity panel / anonymise. -->
			<template v-else>
				<NcButton type="secondary" @click="onEdit">
					<template #icon>
						<Pencil :size="20" />
					</template>
					{{ t('docudesk', 'Edit') }}
				</NcButton>
				<NcButton
					type="primary"
					:disabled="includedCount === 0 || isAnonymising"
					@click="onAnonymise">
					<template v-if="isAnonymising" #icon>
						<NcLoadingIcon :size="20" />
					</template>
					{{ n('docudesk', 'Anonymize %n entity', 'Anonymize %n entities', includedCount) }}
				</NcButton>
			</template>
		</div>
	</NcAppSidebar>
</template>

<script>
import { NcAppSidebar, NcButton, NcCheckboxRadioSwitch, NcLoadingIcon, NcNoteCard, NcSelect } from '@nextcloud/vue'
import { generateRemoteUrl } from '@nextcloud/router'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import DdEntityCard from '../components/DdEntityCard.vue'
import { ENTITY_TYPES } from '../services/entityTypes.js'

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
		NcCheckboxRadioSwitch,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		DdEntityCard,
		Pencil,
	},
	data() {
		return {
			basesOptions: BASES_OPTIONS,
			typeOptions: ENTITY_TYPES,
			loadingFileId: null,
			// Anonymised-document view shows the original values by default —
			// the whole point of the panel is to review what was removed. The
			// toggle still lets the user hide them again (e.g. screen-sharing).
			revealValues: true,
			// "Add new data" panel state (edit mode). The chosen type and
			// grondslagen for the entity about to be added from the selection.
			newType: '',
			newBases: [],
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
		 * Entities to highlight in the document viewer — the detected values
		 * with their type, fed to the viewer via `setHighlightEntities`. Only
		 * for the review step: the anonymised view shows placeholders, not the
		 * original values, so there is nothing to match there.
		 *
		 * @return {Array<{value: string, type: string}>}
		 */
		highlightList() {
			if (!this.entry || this.entry.viewMode === 'anonymized') {
				return []
			}
			return (this.entry.entities || [])
				.filter((e) => e && e.value)
				.map((e) => ({ value: e.value, type: e.type }))
		},
		/**
		 * Whether the user may edit the detected entities. Mirrors the
		 * shared viewer state set by the upload modal and the header switch.
		 * Read through a computed (not a template store-path) so the toggle
		 * and the cards react reliably — a string-path watch on the imported
		 * store never fires.
		 *
		 * @return {boolean}
		 */
		grondslagen() {
			return fileViewerStore.grondslagen
		},
		/**
		 * Whether the "Add new data" panel is active — the edit mode set by the
		 * Bewerken button. Only meaningful during the review step.
		 *
		 * @return {boolean}
		 */
		isEditing() {
			return fileViewerStore.editMode && this.entry?.status === 'extracted'
		},
		/**
		 * The text the user selected in the document viewer — the candidate
		 * value for the new manual entity.
		 *
		 * @return {string}
		 */
		selectedText() {
			return fileViewerStore.selection || ''
		},
		/**
		 * Selected type unwrapped to a plain string. NcSelect may bind a plain
		 * string or a `{ value, label }` object; we feed plain strings but
		 * defend anyway.
		 *
		 * @return {string}
		 */
		newTypeValue() {
			const raw = this.newType
			if (typeof raw === 'string') {
				return raw
			}
			if (raw && typeof raw === 'object') {
				return raw.value || raw.label || ''
			}
			return ''
		},
		/**
		 * Whether the new-entity form can be saved: a non-empty selection and a
		 * chosen type. Grondslagen are optional.
		 *
		 * @return {boolean}
		 */
		canSaveNew() {
			return this.selectedText.trim().length > 0 && this.newTypeValue.length > 0
		},
		/**
		 * Whether to show the grondslagen toggle in the header. Only relevant
		 * while reviewing a freshly extracted file — hidden for the
		 * anonymised, completed and loading/error views, and while the
		 * add-entity panel is open.
		 *
		 * @return {boolean}
		 */
		showGrondslagenToggle() {
			return this.entry?.status === 'extracted' && !this.isEditing
		},
		/**
		 * Sidebar header title — detected-entity count once extraction has
		 * produced a result, otherwise fall back to the file name.
		 *
		 * @return {string}
		 */
		sidebarTitle() {
			if (this.isEditing) {
				return t('docudesk', 'Add new data')
			}
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
			if (this.isEditing) {
				return t('docudesk', 'Select text in the document, then choose a type.')
			}
			if (this.entry?.viewMode === 'anonymized') {
				if (this.entry.sourceFileName) {
					return t('docudesk', 'Anonymised version of {source}', { source: this.entry.sourceFileName })
				}
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
		/**
		 * Push the current detected-entity values to the viewer so it can
		 * highlight them in the rendered document (T09). Fires on load and
		 * whenever the entity list changes.
		 *
		 * @param {Array<{value: string, type: string}>} list Entities to mark.
		 */
		highlightList: {
			handler(list) {
				fileViewerStore.setHighlightEntities(list)
			},
			deep: true,
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
		/**
		 * Enter the "Add new data" panel: switch the viewer into edit mode so
		 * text selection drives a pending highlight, and reset the form.
		 *
		 * @return {void}
		 */
		onEdit() {
			this.resetNewEntityForm()
			fileViewerStore.setEditMode(true)
		},
		/**
		 * Leave the add-entity panel without saving — back to the review list.
		 * `setEditMode(false)` also clears the pending selection.
		 *
		 * @return {void}
		 */
		onCancelEdit() {
			fileViewerStore.setEditMode(false)
			this.resetNewEntityForm()
		},
		/**
		 * Reset the add-entity form fields.
		 *
		 * @return {void}
		 */
		resetNewEntityForm() {
			this.newType = ''
			this.newBases = []
		},
		/**
		 * Save the selected text as a new manual entity. Wired to the backend
		 * in T11 (addManualEntity); for now it only guards the form state.
		 *
		 * @return {Promise<void>}
		 */
		async onSaveNew() {
			if (this.canSaveNew && this.entry) {
				// TODO(T11): call anonymizationStore.addManualEntity(this.entry, {
				//   value: this.selectedText, type: this.newTypeValue,
				//   bases: this.newBases,
				// }) — prepend the result to the list and persist; then reset.
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
	border-radius: var(--dd-radius-panel);
	box-shadow: var(--dd-shadow-panel);
	margin-left: 8px;
}

/* Hide the built-in X close button — the user must finish the entity
 * review (or use the viewer's Back button) before the sidebar unmounts.
 * NcAppSidebar v8 exposes no prop for this, so we override the lib's
 * scoped class directly. */
:deep(.app-sidebar__close) {
	display: none !important;
}

/* Solid white header to match the viewer's DdFileViewerHeader. The sidebar
 * body keeps the translucent card background (set above); only the header
 * band is opaque white so the two headers read as one toolbar row. */
:deep(.app-sidebar-header) {
	/* `.app-sidebar` re-points --color-main-background to white-54, so a var
	 * reference here would stay translucent. The card design is white-on-white
	 * regardless of theme, so use opaque white for the header band. */
	padding-block: 16px;
	padding-inline: 20px;
	background: #fff;
	border-top-left-radius: 20px;
	border-top-right-radius: 20px;
	border-bottom: 1px solid var(--color-border);
	position: sticky;
	top: 0;
	z-index: 1;
}

:deep(.app-sidebar-header__desc) {
	/* Drop the lib's default description padding so the sidebar subtitle
	 * lines up with the DdFileViewerHeader description. */
	padding: 0 !important;
}

/* Grondslagen toggle in the header `description` slot — small top margin so
 * it clears the subname, font-size matched to the surrounding header text. */
.grondslagen-toggle {
	margin-top: 4px;
	font-size: 0.85rem;
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

/* Add new data panel (edit mode). */
.add-entity-panel {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 4px 0;
}

.add-entity-panel__field {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.add-entity-panel__label {
	font-size: 0.8rem;
	color: var(--color-text-maxcontrast);
}

.add-entity-panel__selection {
	padding: 8px 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--dd-radius-md);
	background-color: var(--color-background-hover);
	font-weight: 500;
	overflow-wrap: anywhere;
}

.add-entity-panel__select {
	width: 100%;
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
	gap: 8px;

	:deep(.button-vue) {
		flex: 1;
	}
}
</style>
