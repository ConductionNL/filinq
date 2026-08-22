<script setup>
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import {
	anonymizationStore,
	fileViewerStore,
	myDocumentsStore,
} from '../store/store.js'
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
		<!-- Review controls — search + grondslagen toggle + Edit. These stay
		     part of the sidebar header (anchored, not scrolling with the list).
		     NcAppSidebar only exposes `#description` as an in-header content slot
		     below the title, so we render through it but wrap our own
		     semantically-named `.review-controls` container inside.
		     Only shown while reviewing a freshly extracted file. -->
		<template v-if="showGrondslagenToggle" #description>
			<div class="review-controls">
				<!-- Filter the entity list by value (letter) or type. -->
				<DdSearchBar
					v-model="searchQuery"
					class="entity-search"
					:placeholder="t('filinq', 'Search by letter or type')"
					:clearLabel="t('filinq', 'Clear')" />
				<!-- Toggle (left) + Add button (right). The toggle switches the
				     cards between editable review and read-only defaults; Add
				     opens the add-entity panel. -->
				<div class="review-controls__row">
					<DdToggle
						class="grondslagen-toggle"
						:checked="grondslagen"
						@update:checked="fileViewerStore.setGrondslagen($event)">
						{{ t('filinq', 'Use legal grounds (grondslagen)') }}
					</DdToggle>
					<NcButton variant="secondary" @click="onEdit">
						<template #icon>
							<Plus :size="20" />
						</template>
						{{ t('filinq', 'Add') }}
					</NcButton>
				</div>
			</div>
		</template>
		<div class="file-viewer-sidebar">
			<!-- Loading state: skeletons while ensureExtracted resolves. -->
			<div v-if="isLoading" class="entities-list">
				<DdEntityCard v-for="i in 4" :key="'skeleton-' + i" loading />
			</div>

			<!-- Anonymising state: the anonymise PATCH+POST round-trip is in
			     flight. The `extracted` action bar (with the button) has
			     unmounted, so show an explicit centred loader here instead of
			     leaving the panel blank while the backend works. -->
			<div v-else-if="isAnonymising" class="anonymising-state">
				<NcLoadingIcon :size="44" />
				<p class="anonymising-state__label">
					{{ t('filinq', 'Anonymising…') }}
				</p>
				<p class="anonymising-state__hint">
					{{
						t(
							'filinq',
							'Removing the selected entities from the document. This can take a moment.',
						)
					}}
				</p>
			</div>

			<!-- Error state. -->
			<NcNoteCard v-else-if="entry && entry.status === 'error'" type="error">
				{{ entry.error || t('filinq', 'Failed to load entities') }}
			</NcNoteCard>

			<!-- Completed result: the file finished anonymising and a downloadable
			     output exists. Shows a success or best-effort warning note with
			     the download link, then the same removed-entity list + reveal
			     toggle as the re-opened view (`viewMode === 'anonymized'`) so the
			     operator can verify what was taken out before downloading. -->
			<div v-else-if="isCompletedResult" class="entities-list">
				<!-- Success: everything the user selected was removed. -->
				<NcNoteCard v-if="entry.complete !== false" type="success">
					<div>{{ t('filinq', 'Anonymisation complete') }}</div>
					<div class="muted">
						{{
							n(
								'filinq',
								'%n entity replaced',
								'%n entities replaced',
								entry.replacementCount || 0,
							)
						}}
					</div>
					<a :href="downloadUrl" download class="download-link">
						{{ t('filinq', 'Download anonymised file') }}
					</a>
				</NcNoteCard>

				<!-- Best-effort warning: the file was produced, but some entities
				     could not be fully removed (e.g. text recognised across table
				     cells that is not contiguous in the document). Refining
				     entities is not part of this result view, so we only flag the
				     residuals and tell the operator to check the file. -->
				<NcNoteCard v-else type="warning">
					<div>{{ t('filinq', 'Anonymisation incomplete') }}</div>
					<div class="muted">
						{{
							n(
								'filinq',
								'%n entity could not be fully removed. Check the file below before using it.',
								'%n entities could not be fully removed. Check the file below before using it.',
								entry.residualCount || 0,
							)
						}}
					</div>
					<ul
						v-if="
							entry.residualEntities && entry.residualEntities.length
						"
						class="residual-list">
						<li
							v-for="(r, idx) in entry.residualEntities"
							:key="'res-' + idx">
							<span class="residual-type">{{
								entityTypeLabel(r.type)
							}}</span
							>: {{ r.text }}
						</li>
					</ul>
					<a :href="downloadUrl" download class="download-link">
						{{ t('filinq', 'Download anonymised file') }}
					</a>
				</NcNoteCard>

				<!-- Removed-entity list with the reveal toggle: shows what was
				     taken out of the document, original values hidden behind an
				     explicit reveal so the result panel doesn't silently re-expose
				     the data the file just hid. -->
				<DdRemovedEntitiesList
					v-if="removedEntities.length"
					:items="removedEntities" />
			</div>

			<!-- Anonymised-document view: a read-only list resolved from the
			     `[<TYPE>: <entity_id>]` placeholders baked into the file.
			     Original values stay hidden behind an explicit reveal so
			     opening the result doesn't silently de-anonymise it. -->
			<DdRemovedEntitiesList
				v-else-if="entry && entry.viewMode === 'anonymized'"
				:items="entry.entities" />

			<!-- Add new data panel. The user selects text in the document
			     (highlighted as pending), picks a type and optionally
			     grondslagen, then adds it as one new entity (which closes
			     the panel). Cancel leaves without adding. -->
			<div v-else-if="entry && isAdding" class="add-entity-panel">
				<NcNoteCard :type="selectedText ? 'info' : 'warning'">
					{{
						selectedText
							? t(
									'filinq',
									'This text will be added to the anonymisation list.',
								)
							: t('filinq', 'Select text in the document to add it.')
					}}
				</NcNoteCard>
				<div class="add-entity-panel__field">
					<span class="add-entity-panel__label">{{
						t('filinq', 'Selected text')
					}}</span>
					<div class="add-entity-panel__selection">
						{{ selectedText || '—' }}
					</div>
				</div>
				<NcSelect
					v-model="newType"
					class="add-entity-panel__select"
					:options="typeOptions"
					label="label"
					:reduce="(o) => o.value"
					:inputLabel="t('filinq', 'Type')"
					:placeholder="t('filinq', 'Pick a type…')" />
				<NcSelect
					v-if="grondslagen"
					v-model="newBases"
					class="add-entity-panel__select"
					:options="basesOptions"
					label="label"
					:reduce="(o) => o.value"
					:multiple="true"
					:inputLabel="t('filinq', 'Grondslagen')"
					:placeholder="t('filinq', 'Pick grondslagen…')" />
				<NcNoteCard v-if="saveError" type="error">
					{{ saveError }}
				</NcNoteCard>
			</div>

			<!-- Empty state. -->
			<div
				v-else-if="entry && entry.entities.length === 0"
				class="empty-state">
				<p>{{ t('filinq', 'No entities detected in this file.') }}</p>
			</div>

			<!-- Entity list — one card per detected entity. Filtered by the
			     header search; `idx` is the original position in
			     `entry.entities` so the store mutators target the right row. -->
			<div v-else-if="entry" class="entities-list">
				<div v-if="filteredEntities.length === 0" class="empty-state">
					<p>{{ t('filinq', 'No entities match your search.') }}</p>
				</div>
				<DdEntityCard
					v-for="{ item, idx } in filteredEntities"
					:key="'entity-' + idx"
					:item="item"
					mode="review"
					:editable="grondslagen"
					:basesOptions="basesOptions"
					@toggle="onToggleEntity(idx)"
					@setBases="
						anonymizationStore.setEntityBases(entry, idx, $event)
					" />
			</div>
			<!-- Read-only files_confidential sensitivity signal (files-confidential-labels).
			     Informational only — carries no action, hidden when no label resolved.
			     Independent of the state chain above so it stays visible alongside
			     whichever review state is currently showing. -->
			<div
				v-if="entry && entry.confidentialityLabel"
				class="confidentiality-chip-row">
				<span
					:class="
						'confidentiality-chip confidentiality-level-'
						+ (entry.confidentialityLevel ?? 0)
					"
					:title="
						t(
							'filinq',
							'Confidentiality label from files_confidential',
						)
					">
					{{
						t('filinq', 'Confidentiality: {label}', {
							label: entry.confidentialityLabel,
						})
					}}
				</span>
			</div>
			<ProhibitionBlockedDialog
				:open="prohibitionOpen"
				:block="prohibitionBlock"
				@update:open="prohibitionOpen = $event"
				@force="onForceSkip" />
		</div>

		<!-- Sticky action bar — `NcAppSidebar` v8 has no `footer` slot, so
		     the button rides inside the default slot and stays glued to
		     the viewport bottom via `position: sticky`. -->
		<!-- Adding process. The user selects text and picks a type, then "Add
		     entity" adds it and closes the panel; "Cancel" leaves without
		     adding. -->
		<div
			v-if="entry && entry.status === 'extracted' && isAdding"
			class="sidebar-action-bar">
			<NcButton variant="tertiary" :disabled="savingNew" @click="onCancelEdit">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton
				variant="primary"
				:disabled="!canSaveNew || savingNew"
				@click="onSaveNew">
				<template v-if="savingNew" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ t('filinq', 'Add entity') }}
			</NcButton>
		</div>
		<!-- Dossier mode: the whole batch footer (anonymise-all + progress
		     summary + download-all) lives here, replacing the per-file button
		     and the navigation footer. Shown whenever the dossier has files to
		     process or already-anonymised results. -->
		<div
			v-else-if="
				inDossier
				&& (batchCount > 0
					|| batchState.running
					|| completedCount > 0
					|| isReanonymizable
					|| reanonReviewActive)
			"
			class="sidebar-action-bar sidebar-action-bar--stacked">
			<NcButton
				v-if="batchCount > 0 || batchState.running"
				wide
				variant="primary"
				:disabled="batchState.running || batchCount === 0"
				@click="anonymizeAll">
				<template #icon>
					<NcLoadingIcon v-if="batchState.running" :size="20" />
					<ShieldLockOutline v-else :size="20" />
				</template>
				{{ batchButtonLabel }}
			</NcButton>
			<p v-if="batchSummary" class="dossier-batch-summary">
				{{ batchSummary }}
			</p>
			<!-- Re-anonymise the currently open file (its original is on screen),
			     alongside the dossier batch action. Recognised via the durable link
			     register, so it works for dossiers anonymised in an earlier session. -->
			<NcButton
				v-if="isReanonymizable"
				wide
				variant="secondary"
				:disabled="reanonymising"
				@click="onReanonymise">
				<template #icon>
					<NcLoadingIcon v-if="reanonymising" :size="20" />
					<ShieldRefreshOutline v-else :size="20" />
				</template>
				{{ t('filinq', 'Re-anonymize') }}
			</NcButton>
			<!-- Second step of a re-anonymise: the source is re-extracted, run the
			     new anonymisation for just this file (overwrites its output). -->
			<NcButton
				v-if="reanonReviewActive"
				wide
				variant="primary"
				:disabled="includedCount === 0 || isAnonymising"
				@click="onAnonymise">
				<template v-if="isAnonymising" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{
					n(
						'filinq',
						'Anonymize %n entity',
						'Anonymize %n entities',
						includedCount,
					)
				}}
			</NcButton>
			<!-- Once files are anonymised, offer a one-click download of every
			     result in the dossier, bundled as a single zip. -->
			<NcButton
				v-if="completedCount > 0 && !batchState.running"
				wide
				variant="secondary"
				:disabled="zipping"
				@click="downloadAll">
				<template #icon>
					<NcLoadingIcon v-if="zipping" :size="20" />
					<Download v-else :size="20" />
				</template>
				{{
					zipping
						? t('filinq', 'Preparing download…')
						: t('filinq', 'Download all anonymised files ({count})', {
								count: completedCount,
							})
				}}
			</NcButton>
			<p
				v-if="zipError"
				class="dossier-batch-summary dossier-batch-summary--error">
				{{ zipError }}
			</p>
		</div>
		<!-- Single-file review: per-file anonymise button. -->
		<div
			v-else-if="entry && entry.status === 'extracted'"
			class="sidebar-action-bar">
			<NcButton
				variant="primary"
				:disabled="includedCount === 0 || isAnonymising"
				@click="onAnonymise">
				<template v-if="isAnonymising" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{
					n(
						'filinq',
						'Anonymize %n entity',
						'Anonymize %n entities',
						includedCount,
					)
				}}
			</NcButton>
		</div>
		<!-- Viewing the original of an already-anonymised file: offer another
		     run. Re-extracts the source (preserving prior decisions) and hands
		     off to the normal review → anonymise flow, which overwrites the
		     existing `_anonymized` output. -->
		<div v-else-if="isAnonymizedSource" class="sidebar-action-bar">
			<NcButton
				variant="primary"
				wide
				:disabled="reanonymising"
				@click="onReanonymise">
				<template #icon>
					<NcLoadingIcon v-if="reanonymising" :size="20" />
					<ShieldRefreshOutline v-else :size="20" />
				</template>
				{{ t('filinq', 'Re-anonymize') }}
			</NcButton>
		</div>
		<!-- Single anonymised file (outside a dossier): offer a one-click
		     export of the finished result. Covers both a file just anonymised
		     this session and one re-opened from the list. In a dossier the batch
		     footer above already provides the "Download all" bundle. -->
		<div
			v-else-if="isViewingAnonymizedResult"
			class="sidebar-action-bar sidebar-action-bar--stacked">
			<NcButton wide variant="primary" :disabled="exporting" @click="onExport">
				<template #icon>
					<NcLoadingIcon v-if="exporting" :size="20" />
					<Download v-else :size="20" />
				</template>
				{{
					exporting
						? t('filinq', 'Preparing download…')
						: t('filinq', 'Export file(s)')
				}}
			</NcButton>
			<p
				v-if="exportError"
				class="dossier-batch-summary dossier-batch-summary--error">
				{{ exportError }}
			</p>
		</div>
	</NcAppSidebar>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateRemoteUrl } from '@nextcloud/router'
import {
	NcAppSidebar,
	NcButton,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
} from '@nextcloud/vue'
import JSZip from 'jszip'
import Download from 'vue-material-design-icons/Download.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import ShieldLockOutline from 'vue-material-design-icons/ShieldLockOutline.vue'
import ShieldRefreshOutline from 'vue-material-design-icons/ShieldRefreshOutline.vue'
import DdEntityCard from '../components/DdEntityCard.vue'
import DdRemovedEntitiesList from '../components/DdRemovedEntitiesList.vue'
import DdSearchBar from '../components/DdSearchBar.vue'
import DdToggle from '../components/DdToggle.vue'
import ProhibitionBlockedDialog from '../dialogs/ProhibitionBlockedDialog.vue'
import { fetchBaseOptions } from '../services/bases.js'
import { ENTITY_TYPES, entityTypeLabel } from '../services/entityTypes.js'

export default {
	name: 'FileViewerSidebar',
	components: {
		NcAppSidebar,
		NcButton,
		DdToggle,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		DdEntityCard,
		DdRemovedEntitiesList,
		DdSearchBar,
		ProhibitionBlockedDialog,
		Plus,
		ShieldLockOutline,
		ShieldRefreshOutline,
		Download,
	},

	data() {
		return {
			basesOptions: [],
			// Prohibition-guard dialog state for a blocked skip decision.
			prohibitionOpen: false,
			prohibitionBlock: null,
			pendingToggleIdx: null,
			loadingFileId: null,
			// "Add new data" panel state (add mode). The chosen type and
			// grondslagen for the entity about to be added from the selection.
			newType: '',
			newBases: [],
			savingNew: false,
			saveError: null,
			// Header search query — filters the review entity list by value
			// (letter) or type. Empty string shows every entity.
			searchQuery: '',
			// True while the dossier "Download all" zip is being fetched + built.
			zipping: false,
			// Set when one or more files could not be added to the zip.
			zipError: '',
			// True while re-extracting the source for another anonymisation run
			// (the "Re-anonymize" button shown when viewing the original).
			reanonymising: false,
			// True while the single-file "Export files" download is in flight.
			exporting: false,
			// Set when the export download could not be produced.
			exportError: '',
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
		 * True when the viewer is showing the ORIGINAL of a file that has
		 * already been anonymised this session — the state in which the user
		 * should be offered a "Re-anonymize" action. Gated on viewing the
		 * original (not the result) so the button never appears on the
		 * anonymised view, where the download lives instead.
		 *
		 * The signal is a completed source entry that carries an anonymised
		 * output (`anonymizedFilePath`/`anonymizedFileId`). A re-opened
		 * anonymised file keyed on the output id does not match — toggling to
		 * its original re-creates the editable source entry directly.
		 *
		 * @return {boolean}
		 */
		isAnonymizedSource() {
			if (fileViewerStore.showAnonymized) {
				return false
			}
			const e = this.entry
			return !!(
				e
				&& e.status === 'completed'
				&& (e.anonymizedFilePath || e.anonymizedFileId)
			)
		},

		/**
		 * True when the viewer shows the ORIGINAL of a file that has an
		 * anonymised output, so a "Re-anonymize" action should be offered —
		 * including inside a dossier, where the batch footer would otherwise
		 * shadow the per-file button.
		 *
		 * Recognised two ways: the session queue (`isAnonymizedSource`, a file
		 * anonymised this session), and — crucially for dossiers anonymised in
		 * an earlier session — the durable `anonymizationLink` register via
		 * `myDocumentsStore.anonymizedFor`. Suppressed while a re-run is already
		 * being reviewed (`reanonReviewActive` takes over with the "Anonymize"
		 * button).
		 *
		 * @return {boolean}
		 */
		isReanonymizable() {
			if (fileViewerStore.showAnonymized || this.reanonReviewActive) {
				return false
			}
			if (this.isAnonymizedSource) {
				return true
			}
			const file = fileViewerStore.currentFile
			return !!(file && myDocumentsStore.anonymizedFor(file))
		},

		/**
		 * True while a re-anonymise sub-flow is mid-review: `prepareReanonymize`
		 * re-extracted the source and flagged the entry, so the per-file review
		 * list + "Anonymize" button should show even in a dossier (where the
		 * batch footer otherwise hides the single-file button). Cleared when the
		 * re-run completes.
		 *
		 * @return {boolean}
		 */
		reanonReviewActive() {
			return !!(
				this.entry
				&& this.entry.reanonymize
				&& this.entry.status === 'extracted'
			)
		},

		/**
		 * True when the open file lives inside a dossier (a subfolder of
		 * /Filinq). Mirrors App.vue's `inDossier`: in this mode the action
		 * bar offers the dossier-wide batch button instead of the per-file one.
		 *
		 * @return {boolean}
		 */
		inDossier() {
			return myDocumentsStore.currentPath !== '/Filinq'
		},

		/**
		 * The dossier's source files: every listed file that isn't a folder
		 * or an already-anonymised `_anonymized` output. This is the full set
		 * the batch run covers — taken from the listing, NOT from the store's
		 * `extracted` queue, because extraction is lazy (only the opened file
		 * is extracted), so the queue under-counts files until each is opened.
		 *
		 * @return {Array<object>}
		 */
		dossierSourceFiles() {
			return myDocumentsStore.documents.filter(
				(d) => !d.isFolder && !d.isAnonymized,
			)
		},

		/**
		 * Dossier source files still awaiting anonymisation. Drives the batch
		 * button count and visibility, so it must reflect the whole dossier —
		 * including files never opened this session.
		 *
		 * A source is done when it already has an anonymised output on disk,
		 * read from the durable `anonymizationLink` register
		 * (`myDocumentsStore.anonymizedFor`) rather than the in-memory queue —
		 * otherwise a dossier anonymised in an earlier session (empty queue)
		 * would count every source as pending and wrongly show "Anonymize all
		 * files". Files with no output yet are pending unless a session run has
		 * already finished or errored them (covers a fresh upload mid-flow).
		 *
		 * @return {Array<object>}
		 */
		pendingSourceFiles() {
			return this.dossierSourceFiles.filter((d) => {
				if (myDocumentsStore.anonymizedFor(d)) {
					return false
				}
				const entry = anonymizationStore.findByFileId(d.fileId)
				return (
					!entry
					|| (entry.status !== 'completed' && entry.status !== 'error')
				)
			})
		},

		/**
		 * Display name of the current dossier (last path segment) — used as the
		 * zip file name for the "Download all" bundle.
		 *
		 * @return {string}
		 */
		dossierName() {
			const parts = (myDocumentsStore.currentPath || '')
				.split('/')
				.filter(Boolean)
			return parts[parts.length - 1] || ''
		},

		/**
		 * Live batch-run progress from the anonymization store.
		 *
		 * @return {{running: boolean, total: number, done: number, failed: number}}
		 */
		batchState() {
			return anonymizationStore.batch
		},

		/**
		 * Number of dossier files still awaiting anonymisation. Counts the
		 * listing's pending source files (see `pendingSourceFiles`) rather than
		 * the store's `extracted` queue, so it covers files not yet opened —
		 * the batch is one unit, so the count must reflect the whole dossier.
		 *
		 * @return {number}
		 */
		batchCount() {
			return this.pendingSourceFiles.length
		},

		/**
		 * Anonymised outputs in the current dossier, ready to bundle into the
		 * "Download all" zip. Read from the durable listing
		 * (`myDocumentsStore.visibleDocuments`) rather than the in-memory queue,
		 * so results anonymised in an earlier session are still downloadable and
		 * a fully-anonymised dossier offers "Download all" instead of an empty
		 * footer. `visibleDocuments` already collapses superseded duplicate
		 * outputs, so each source contributes only its newest result.
		 *
		 * Shaped to match what `downloadAll` / `uniqueZipName` expect: a
		 * `files`-rooted path (`downloadUrlFor` splits on the `files` segment,
		 * mirroring the backend storage path) plus the output's name/id.
		 *
		 * @return {Array<{anonymizedFileId: number, anonymizedFileName: string, anonymizedFilePath: string}>}
		 */
		completedEntries() {
			return myDocumentsStore.visibleDocuments
				.filter((d) => !d.isFolder && d.isAnonymized)
				.map((d) => ({
					anonymizedFileId: d.fileId,
					anonymizedFileName: d.fileName,
					anonymizedFilePath: `files${myDocumentsStore.currentPath}/${d.fileName}`,
				}))
		},

		/**
		 * Number of anonymised dossier files available to download.
		 *
		 * @return {number}
		 */
		completedCount() {
			return this.completedEntries.length
		},

		/**
		 * Label for the dossier batch button: a live progress count while
		 * running, otherwise "Anonymize all files (N)". Mirrors
		 * FolderFilesNavigation so both entry points read identically.
		 *
		 * @return {string}
		 */
		batchButtonLabel() {
			if (this.batchState.running) {
				const processed = this.batchState.done + this.batchState.failed
				return t('filinq', 'Anonymizing… ({processed}/{total})', {
					processed,
					total: this.batchState.total,
				})
			}
			return t('filinq', 'Anonymize all files ({count})', {
				count: this.batchCount,
			})
		},

		/**
		 * One-line summary shown after a finished batch run; empty while idle
		 * or running. Mirrors FolderFilesNavigation.
		 *
		 * @return {string}
		 */
		batchSummary() {
			const { running, total, done, failed } = this.batchState
			if (running || total === 0) {
				return ''
			}
			if (failed > 0) {
				return t('filinq', '{done} anonymized, {failed} failed.', {
					done,
					failed,
				})
			}
			return t('filinq', 'All {total} files anonymized.', { total })
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
			return (
				this.entry?.status === 'extracting'
				|| this.entry?.status === 'uploading'
			)
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
			return (this.entry?.entities || []).filter((e) => e.included !== false)
				.length
		},

		/**
		 * True for the post-anonymise result step: the run finished and a
		 * downloadable output exists. Gates the completed result view (note +
		 * download link + removed-entity list). Distinct from the re-opened
		 * `viewMode === 'anonymized'` view, which carries no download link.
		 *
		 * @return {boolean}
		 */
		isCompletedResult() {
			return (
				this.entry?.status === 'completed'
				&& !!this.entry?.anonymizedFilePath
			)
		},

		/**
		 * True when the anonymised result of a standalone (non-dossier) file is
		 * currently on screen — the state in which the "Export files" footer
		 * appears. Two paths reach it:
		 *  - a file just anonymised this session (`isCompletedResult`), but only
		 *    while its anonymised variant is the one shown (not after toggling
		 *    back to the original, where "Re-anonymize" takes over);
		 *  - an already-anonymised file re-opened from the list
		 *    (`viewMode === 'anonymized'`).
		 * In a dossier the batch footer's "Download all" covers this instead.
		 *
		 * @return {boolean}
		 */
		isViewingAnonymizedResult() {
			if (this.inDossier) {
				return false
			}
			if (this.entry?.viewMode === 'anonymized') {
				return true
			}
			return this.isCompletedResult && fileViewerStore.showAnonymized
		},

		/**
		 * Entities actually removed by the just-finished run, mapped onto the
		 * anonymised-card shape so the result step can show the same reveal
		 * list as the re-opened view. Only `included` entities are taken (the
		 * ones sent to the backend); the original value sits behind the reveal
		 * toggle, with a masked `[<TYPE>]` placeholder shown while hidden.
		 *
		 * @return {Array<object>}
		 */
		/**
		 * Options for the manual "add entity" type picker. Pairs the raw
		 * entity-type token (the value, kept unchanged for logic) with its
		 * translated label (shown to the user). The select reduces back to the
		 * raw value, so `newTypeValue` still yields the machine token.
		 *
		 * @return {Array<{label: string, value: string}>}
		 */
		typeOptions() {
			return ENTITY_TYPES.map((type) => ({
				label: entityTypeLabel(type),
				value: type,
			}))
		},

		removedEntities() {
			if (!this.isCompletedResult) {
				return []
			}
			// Prefer the entities resolved from the produced document: they carry
			// the real emitted marker (localized TYPE + scope-local number) that
			// the file actually contains. Fall back to the pre-anonymise detected
			// set with a reconstructed `[<TYPE>]` marker (translated, but without a
			// number) when resolution was unavailable.
			if (
				Array.isArray(this.entry.resolvedEntities)
				&& this.entry.resolvedEntities.length
			) {
				return this.entry.resolvedEntities
			}
			return (this.entry.entities || [])
				.filter((e) => e.included !== false)
				.map((e) => ({
					type: e.type,
					value: e.value,
					count: e.count || 1,
					bases: Array.isArray(e.bases) ? e.bases : [],
					placeholder: `[${entityTypeLabel(e.type)}]`,
					_resolveError: null,
				}))
		},

		/**
		 * The anonymised-card list currently on screen: the re-opened document's
		 * resolved entities, or the just-finished run's removed entities. Backs
		 * the header summary so the title counts whatever the body shows. Empty
		 * in every other state.
		 *
		 * @return {Array<object>}
		 */
		anonymizedSummaryList() {
			if (this.entry?.viewMode === 'anonymized') {
				return this.entry.entities || []
			}
			if (this.isCompletedResult) {
				return this.removedEntities
			}
			return []
		},

		/**
		 * Unique values in the anonymised view — one per card. Matches the number
		 * of rows the user can count.
		 *
		 * @return {number}
		 */
		summaryFound() {
			return this.anonymizedSummaryList.length
		},

		/**
		 * Review entities filtered by the header search, paired with their
		 * original index in `entry.entities` so the store mutators
		 * (toggle/set-bases) still target the correct row. Matches the query
		 * case-insensitively against the entity value (letter) or its type.
		 *
		 * @return {Array<{item: object, idx: number}>}
		 */
		filteredEntities() {
			const entities = this.entry?.entities || []
			const indexed = entities.map((item, idx) => ({ item, idx }))
			const query = this.searchQuery.trim().toLowerCase()
			if (!query) {
				return indexed
			}
			return indexed.filter(({ item }) => {
				const value = String(item.value || '').toLowerCase()
				const type = String(item.type || '').toLowerCase()
				return value.includes(query) || type.includes(query)
			})
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
		 * Whether the "Add new data" panel is active — the add mode set by the
		 * Bewerken button. Only meaningful during the review step.
		 *
		 * @return {boolean}
		 */
		isAdding() {
			return fileViewerStore.addMode && this.entry?.status === 'extracted'
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
			return (
				this.selectedText.trim().length > 0 && this.newTypeValue.length > 0
			)
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
			return this.entry?.status === 'extracted' && !this.isAdding
		},

		/**
		 * Sidebar header title — detected-entity count once extraction has
		 * produced a result, otherwise fall back to the file name.
		 *
		 * @return {string}
		 */
		sidebarTitle() {
			if (this.isAdding) {
				return t('filinq', 'Add new data')
			}
			// Anonymised result — re-opened document or the just-finished run:
			// the title summarises the removed data (unique values found) instead
			// of a bare "items anonymised" count.
			if (this.entry?.viewMode === 'anonymized' || this.isCompletedResult) {
				return t('filinq', '{found} unique data found', {
					found: this.summaryFound,
				})
			}
			if (this.entry && Array.isArray(this.entry.entities)) {
				return n(
					'filinq',
					'%n entity detected',
					'%n entities detected',
					this.entry.entities.length,
				)
			}
			return fileViewerStore.currentFile?.fileName || t('filinq', 'Entities')
		},

		/**
		 * Sidebar subtitle — loading/error status while extraction is still
		 * resolving, otherwise the "verify the auto-detected entities"
		 * disclaimer shown during the review step.
		 *
		 * @return {string}
		 */
		sidebarSubtitle() {
			if (this.isAdding) {
				return t(
					'filinq',
					'Select text in the document, then choose a type.',
				)
			}
			if (this.entry?.viewMode === 'anonymized') {
				if (this.entry.sourceFileName) {
					return t('filinq', 'Anonymised version of {source}', {
						source: this.entry.sourceFileName,
					})
				}
				return t('filinq', 'Resolved from the GDPR register.')
			}
			if (this.isLoading) {
				return t('filinq', 'Detecting entities…')
			}
			if (this.entry?.status === 'error') {
				return t('filinq', 'Failed to load')
			}
			if (
				this.entry
				&& Array.isArray(this.entry.entities)
				&& this.entry.entities.length > 0
			) {
				return t('filinq', 'Automatic detection. Always verify yourself.')
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
				// Encode each segment so a dossier/file name containing `?`,
				// `#` or `&` doesn't corrupt the download URL.
				const relativePath = parts
					.slice(filesIndex + 1)
					.map(encodeURIComponent)
					.join('/')
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

	async created() {
		// Grondslagen options come from the register (label = name, value = slug),
		// with a slug fallback on error. See services/bases.js.
		this.basesOptions = await fetchBaseOptions()
	},

	methods: {
		entityTypeLabel,
		/**
		 * Toggle an entity's inclusion (= skip decision) through the guarded
		 * store action. A 422 (prohibited) opens the block dialog instead of
		 * flipping the checkbox.
		 *
		 * @param {number} idx Index of the entity in the entry.
		 * @return {Promise<void>}
		 */
		async onToggleEntity(idx) {
			const res = await anonymizationStore.toggleEntity(this.entry, idx)
			if (res && !res.ok && res.status === 422) {
				this.pendingToggleIdx = idx
				this.prohibitionBlock = res.body
				this.prohibitionOpen = true
			}
		},

		/**
		 * Dialog force action: retry the pending skip with force=true. Only
		 * releases sub-threshold matches; absolute ones are refused again.
		 *
		 * @return {Promise<void>}
		 */
		async onForceSkip() {
			const idx = this.pendingToggleIdx
			if (idx == null) {
				return
			}
			const res = await anonymizationStore.setEntitySkip(
				this.entry,
				idx,
				true,
				true,
			)
			if (res && res.ok) {
				this.prohibitionOpen = false
			} else if (res) {
				this.prohibitionBlock = res.body
			}
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
				const anonymized =
					await anonymizationStore.loadAnonymizedEntities(meta)
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
			// When grondslagen are on, ask the backend to append the legal-grounds
			// summary to the output. Both flags must travel together (see
			// anonymiseEntry) or the summary is silently skipped.
			await anonymizationStore.anonymiseEntry(
				this.entry,
				this.grondslagen
					? { appendBasisSummary: true, outputFormat: 'pdf-only' }
					: {},
			)
			if (this.entry.status === 'completed' && this.entry.anonymizedFileId) {
				fileViewerStore.setAnonymizedVariant({
					fileId: this.entry.anonymizedFileId,
					fileName: this.entry.anonymizedFileName || this.entry.name,
					mimeType: fileViewerStore.originalFile?.mimeType || '',
					path: this.entry.anonymizedFilePath,
				})
				// The anonymise call wrote a new `_anonymized` file into the
				// current folder. Refresh the document list so it shows up in
				// the dossier navigation instead of only after a re-entry.
				await myDocumentsStore.fetchDocuments()
			}
		},

		/**
		 * Re-open the current (already-anonymised) file's source for another
		 * anonymisation run. Delegates to the store, which re-extracts the
		 * source and flips the entry back to the editable `extracted` state —
		 * the review list + "Anonymize" button then take over, and the next
		 * run overwrites the existing `_anonymized` output.
		 *
		 * @return {Promise<void>}
		 */
		async onReanonymise() {
			if (!this.entry) {
				return
			}
			this.reanonymising = true
			try {
				await anonymizationStore.prepareReanonymize(this.entry)
			} finally {
				this.reanonymising = false
			}
		},

		/**
		 * Anonymise every extracted file in the current dossier in one action.
		 * The dossier sidebar variant of `onAnonymise` — scopes the run to this
		 * dossier's files and forwards the grondslagen options, mirroring
		 * FolderFilesNavigation's `anonymizeAll` so both entry points behave
		 * identically.
		 *
		 * @return {Promise<void>}
		 */
		async anonymizeAll() {
			// Scope the run to the dossier's pending source files and pass full
			// descriptors: the store extracts any file the user never opened
			// before anonymising, so the batch covers the whole dossier — not
			// just the files that happened to be opened in the viewer.
			const files = this.pendingSourceFiles.map((d) => ({
				fileId: d.fileId,
				fileName: d.fileName,
				path: `${myDocumentsStore.currentPath}/${d.fileName}`,
				mimeType: d.mimeType,
			}))
			const fileIds = files.map((f) => f.fileId)
			// When grondslagen are on, append the basis summary and render to
			// PDF — both flags must travel together (see anonymiseEntry).
			const options = this.grondslagen
				? {
						files,
						fileIds,
						appendBasisSummary: true,
						outputFormat: 'pdf-only',
					}
				: { files, fileIds }
			await anonymizationStore.anonymiseAllExtracted(options)
			// Each run writes a new `_anonymized` file into the dossier folder;
			// refresh so the results show up without leaving and re-entering.
			await myDocumentsStore.fetchDocuments()
		},

		/**
		 * Build the WebDAV download URL for an anonymised result path. Mirrors
		 * the per-file `downloadUrl` computed: strip the leading `.../files/`
		 * segment and append the user-relative remainder.
		 *
		 * @param {string} anonymizedFilePath Absolute storage path of the result.
		 * @return {string} The WebDAV URL, or '' when no path is given.
		 */
		downloadUrlFor(anonymizedFilePath) {
			if (!anonymizedFilePath) {
				return ''
			}
			const parts = anonymizedFilePath.split('/')
			const filesIndex = parts.indexOf('files')
			if (filesIndex >= 0) {
				// Encode each segment so a dossier/file name containing `?`,
				// `#` or `&` doesn't corrupt the download URL.
				const relativePath = parts
					.slice(filesIndex + 1)
					.map(encodeURIComponent)
					.join('/')
				return generateRemoteUrl('webdav') + '/' + relativePath
			}
			return generateRemoteUrl('webdav')
		},

		/**
		 * Download every anonymised result in the dossier as a single zip.
		 *
		 * Frontend-only: each result is fetched over WebDAV, bundled in-memory
		 * with JSZip and saved as one archive — no backend zip endpoint
		 * required. A file that fails to fetch is skipped and reported in
		 * `zipError` rather than aborting the whole bundle.
		 *
		 * @return {Promise<void>}
		 */
		async downloadAll() {
			if (this.zipping) {
				return
			}
			this.zipping = true
			this.zipError = ''
			const zip = new JSZip()
			const usedNames = new Set()
			let failed = 0
			try {
				for (const entry of this.completedEntries) {
					const url = this.downloadUrlFor(entry.anonymizedFilePath)
					if (!url) {
						failed++
						continue
					}
					try {
						const res = await axios.get(url, {
							responseType: 'arraybuffer',
						})
						zip.file(this.uniqueZipName(entry, usedNames), res.data)
					} catch (err) {
						console.error('Download-all: could not fetch', url, err)
						failed++
					}
				}
				if (!usedNames.size) {
					this.zipError = t(
						'filinq',
						'Could not download any of the anonymised files.',
					)
					return
				}
				const blob = await zip.generateAsync({ type: 'blob' })
				this.triggerBlobDownload(
					blob,
					`${this.dossierName || 'dossier'}-anonymised.zip`,
				)
				if (failed > 0) {
					this.zipError = t(
						'filinq',
						'{failed} file(s) could not be added to the download.',
						{ failed },
					)
				}
			} catch (err) {
				console.error('Download-all: zip generation failed', err)
				this.zipError = t('filinq', 'Preparing the download failed.')
			} finally {
				this.zipping = false
			}
		},

		/**
		 * Export (download) the single anonymised file currently open.
		 *
		 * Exports whatever anonymised result is on screen: the viewer's
		 * `currentFile` is the anonymised file in both cases — the variant
		 * switched in after a fresh run (`setAnonymizedVariant`) and the
		 * re-opened anonymised document. Using `entry.filePath` would be wrong
		 * for a fresh run, where it still points at the original source. Fetched
		 * over WebDAV and saved via a blob so the download keeps the file name
		 * instead of opening inline. Mirrors `downloadAll` for one file.
		 *
		 * @return {Promise<void>}
		 */
		async onExport() {
			if (this.exporting) {
				return
			}
			const file = fileViewerStore.currentFile
			const url = this.downloadUrlFor(file?.path)
			if (!url) {
				this.exportError = t(
					'filinq',
					'Could not locate the file to export.',
				)
				return
			}
			this.exporting = true
			this.exportError = ''
			try {
				const res = await axios.get(url, { responseType: 'blob' })
				const name = file?.fileName || 'export'
				this.triggerBlobDownload(res.data, name)
			} catch (err) {
				console.error('Export: could not download', url, err)
				this.exportError = t('filinq', 'Exporting the file failed.')
			} finally {
				this.exporting = false
			}
		},

		/**
		 * Pick a collision-free entry name for the zip. Anonymised results can
		 * share a file name across the dossier, which would otherwise overwrite
		 * each other inside the archive.
		 *
		 * @param {object} entry      Completed queue entry.
		 * @param {Set<string>} used  Names already taken in this archive.
		 * @return {string} A unique name for the zip entry.
		 */
		uniqueZipName(entry, used) {
			const base =
				entry.anonymizedFileName
				|| `file-${entry.anonymizedFileId || entry.fileId}`
			let name = base
			let i = 1
			while (used.has(name)) {
				const dot = base.lastIndexOf('.')
				name =
					dot > 0
						? `${base.slice(0, dot)} (${i})${base.slice(dot)}`
						: `${base} (${i})`
				i++
			}
			used.add(name)
			return name
		},

		/**
		 * Save a Blob to disk via a transient object-URL anchor.
		 *
		 * @param {Blob} blob     The data to save.
		 * @param {string} name   Suggested file name.
		 */
		triggerBlobDownload(blob, name) {
			const url = URL.createObjectURL(blob)
			const link = document.createElement('a')
			link.href = url
			link.download = name
			document.body.appendChild(link)
			link.click()
			document.body.removeChild(link)
			URL.revokeObjectURL(url)
		},

		/**
		 * Enter the "Add new data" panel: switch the viewer into add mode so
		 * text selection drives a pending highlight, and reset the form.
		 *
		 * @return {void}
		 */
		onEdit() {
			this.resetNewEntityForm()
			fileViewerStore.setAddMode(true)
		},

		/**
		 * Leave the adding process without adding — back to the review list.
		 * `setAddMode(false)` also clears the pending selection.
		 *
		 * @return {void}
		 */
		onCancelEdit() {
			fileViewerStore.setAddMode(false)
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
			this.saveError = null
		},

		/**
		 * Add the current selection as a new manual entity via the store.
		 * Persists it, prepends it to the list, then closes the adding process
		 * (back to the review list) — adding one entity completes the process.
		 *
		 * @return {Promise<void>}
		 */
		async onSaveNew() {
			if (!this.canSaveNew || !this.entry || this.savingNew) {
				return
			}
			this.savingNew = true
			this.saveError = null
			try {
				await anonymizationStore.addManualEntity(this.entry, {
					value: this.selectedText,
					type: this.newTypeValue,
					bases: this.grondslagen ? this.newBases : [],
				})
				// Close the adding process: the new entity now highlights as its
				// own type and the pending mark is gone. `setAddMode(false)`
				// clears the selection.
				fileViewerStore.setAddMode(false)
				this.resetNewEntityForm()
			} catch (err) {
				this.saveError =
					err?.message || t('filinq', 'Failed to add the selected text')
			} finally {
				this.savingNew = false
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
	--color-main-background: var(--dd-glass-bg, rgba(255, 255, 255, 0.54));
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

/* Solid header band to match the viewer's DdFileViewerHeader. The sidebar
 * body keeps the translucent card background (set above); only the header
 * band is opaque so the two headers read as one toolbar row. */
:deep(.app-sidebar-header) {
	/* `.app-sidebar` re-points --color-main-background to white-54, so a var
	 * reference here would stay translucent. The card design is white-on-white
	 * regardless of theme, so use opaque white for the header band. */
	display: grid;
	gap: 20px;
	padding-block: 16px 20px;
	padding-inline: 20px;
	background: var(--dd-surface, #fff);
	border-top-left-radius: 20px;
	border-top-right-radius: 20px;
	border-bottom: 1px solid var(--color-border);
	position: sticky;
	top: 0;
	z-index: 2;
}

:deep(.app-sidebar-header__desc) {
	/* Drop the lib's default description padding so the sidebar subtitle
	 * lines up with the DdFileViewerHeader description. */
	padding: 0 !important;
}

:deep(.app-sidebar-header__description) {
	margin-inline: 0 !important;
}

/* Review controls (search + toggle/edit row) in the sticky header band.
 * Stacked, with the search bar on top; the header's own padding handles
 * the spacing above and below. */
.review-controls {
	display: grid;
	gap: 12px;
	width: 100%;
}

/* Toggle on the left, Edit button pushed to the right. */
.review-controls__row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

/* The DdSearchBar carries a 392px min-width tuned for full-page toolbars;
 * relax it so it fits the narrow sidebar and fills the available width. */
.entity-search {
	max-width: none;
}

.entity-search :deep(.dd-search-bar__input) {
	min-width: 0;
}

.entities-summary {
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.entities-list {
	display: flex;
	flex-direction: column;
}

/* Read-only files_confidential chip (files-confidential-labels). Level
 * colours mirror the existing risk-badge scale (info/warning/error) so
 * the two read-only signals read consistently. */
.confidentiality-chip-row {
	padding: 4px 12px 0;
}

.confidentiality-chip {
	display: inline-block;
	padding: 2px 8px;
	border-radius: var(--border-radius-pill);
	font-size: 0.75rem;
	font-weight: 500;
}

.confidentiality-level-0 {
	background-color: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.confidentiality-level-1 {
	background-color: var(--color-info);
	color: var(--color-info-text);
}

.confidentiality-level-2 {
	background-color: var(--color-warning);
	color: var(--color-warning-text);
}

.confidentiality-level-3 {
	background-color: var(--color-error);
	color: var(--color-error-text);
}

/* Add new data panel (add mode). */
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

.empty-state {
	padding: 24px 12px;
	text-align: center;
	color: var(--color-text-maxcontrast);
}

/* Anonymising loader — centred spinner + label shown while the anonymise
 * round-trip runs and the extracted action bar has unmounted. */
.anonymising-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12px;
	padding: 48px 24px;
	text-align: center;
}

.anonymising-state__label {
	margin: 0;
	font-weight: 600;
}

.anonymising-state__hint {
	margin: 0;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.muted {
	color: var(--color-text-maxcontrast);
	font-size: 0.85rem;
	margin-top: 4px;
}

.residual-list {
	margin: 6px 0 0;
	padding-left: 18px;
	font-size: 0.85rem;
	max-height: 160px;
	overflow-y: auto;

	li {
		margin: 2px 0;
	}

	.residual-type {
		font-weight: 600;
		color: var(--color-text-maxcontrast);
	}
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
	/* opaque (the sidebar re-points --color-main-background to translucent
	 * glass) and theme-aware, so the scrolling list is hidden behind it. */
	background-color: var(--dd-surface);
	display: flex;
	gap: 8px;

	:deep(.button-vue) {
		flex: 1;
	}
}

/* Dossier batch footer stacks its button(s) and summary vertically rather
 * than sharing one row. Override --color-main-background to opaque white so
 * the inherited `background-color: var(--color-main-background)` matches the
 * header band (the sidebar re-points that var to translucent white-54). */
.sidebar-action-bar--stacked {
	--color-main-background: #fff;
	flex-direction: column;
	gap: 6px;
}

.dossier-batch-summary {
	margin: 0;
	text-align: center;
	font-size: 0.85rem;
	color: var(--color-text-maxcontrast);
}

.dossier-batch-summary--error {
	color: var(--color-error);
}
</style>
