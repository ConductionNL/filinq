/* eslint-disable no-console */
import { defineStore } from 'pinia'

/**
 * Pinia store for the in-app file viewer.
 *
 * Tracks which file is currently being previewed and the UI state of the viewer.
 * Actual file loading happens inside the per-MIME viewer component.
 *
 * The viewer is rendered as a panel inside the My Documents page when
 * currentFile is set — there is no dedicated route. Opening / closing a file
 * is therefore a pure store mutation; the URL stays on /my-documents.
 *
 * To support toggling between the original and the anonymised version of the
 * same logical file, the store keeps both variants side-by-side in
 * `originalFile` / `anonymizedFile`. `currentFile` is always a reference to
 * one of the two. `showAnonymized` reflects which one is active.
 */
export const useFileViewerStore = defineStore(
	'fileViewer',
	{
		state: () => ({
			currentFile: null, // { fileId, fileName, mimeType, path }
			originalFile: null,
			anonymizedFile: null,
			showAnonymized: false,
			// Latest text selection from the viewer surface. In add mode this
			// doubles as the pending candidate text for a new manual entity
			// (see setAddMode / T10).
			selection: '',
			// Whether the user may edit the detected entities (set legal
			// grounds / toggle inclusion) before anonymising. Set by the
			// upload modal and live-switchable from the sidebar header.
			// `true` keeps the existing reviewable UI; `false` makes the
			// entity cards read-only with default values (see T03/T04).
			grondslagen: true,
			// "Add new data" mode: the sidebar swaps to the add-entity panel
			// and the viewer enables text selection for highlighting (T10).
			addMode: false,
			// Entities the viewer should highlight in the rendered document,
			// as `{ value, type }`. Pushed by the sidebar from the current
			// entity list; consumed by the viewers (T09).
			highlightEntities: [],
		}),
		getters: {
			/**
			 * Whether both the original and the anonymised variant are known —
			 * drives the enabled state of the viewer's toggle button.
			 *
			 * @param {object} state Store state.
			 * @return {boolean}
			 */
			canToggleVariant: (state) => Boolean(state.originalFile && state.anonymizedFile),
		},
		actions: {
			/**
			 * Open the viewer for a given file. Resets any previously
			 * attached anonymised variant — callers attach a new one via
			 * `setAnonymizedVariant` once anonymisation completes.
			 *
			 * @param {object} file File descriptor.
			 * @param {number} file.fileId   Nextcloud file id.
			 * @param {string} file.fileName File name with extension.
			 * @param {string} file.mimeType MIME type.
			 * @param {string} file.path     Absolute path inside the user's storage (e.g. /DocuDesk/foo.pdf).
			 * @param {object} [options]             Viewer options.
			 * @param {boolean} [options.grondslagen] Whether the entity cards
			 *        start editable (review mode). Defaults to `true` so callers
			 *        that don't pass it keep the existing reviewable behaviour.
			 */
			open(file, options = {}) {
				this.currentFile = file
				this.originalFile = file
				this.anonymizedFile = null
				this.showAnonymized = false
				this.selection = ''
				this.grondslagen = options.grondslagen ?? true
				this.addMode = false
				this.highlightEntities = []
			},
			/**
			 * Attach the anonymised counterpart of the currently-open file and
			 * switch the viewer to it. Used by the sidebar after a successful
			 * anonymise so the user sees the result inline without losing the
			 * link back to the original.
			 *
			 * @param {object} file Anonymised file descriptor (same shape as `open`).
			 */
			setAnonymizedVariant(file) {
				this.anonymizedFile = file
				this.currentFile = file
				this.showAnonymized = true
				this.selection = ''
				this.addMode = false
				this.highlightEntities = []
			},
			/**
			 * Close the viewer. Host page reverts to file list.
			 */
			close() {
				this.currentFile = null
				this.originalFile = null
				this.anonymizedFile = null
				this.showAnonymized = false
				this.selection = ''
				this.grondslagen = true
				this.addMode = false
				this.highlightEntities = []
			},
			/**
			 * Swap `currentFile` between the original and the anonymised variant.
			 * No-op when only one of the two is known.
			 */
			toggleAnonymized() {
				if (!this.canToggleVariant) {
					return
				}
				this.showAnonymized = !this.showAnonymized
				this.currentFile = this.showAnonymized ? this.anonymizedFile : this.originalFile
				this.selection = ''
				this.addMode = false
				this.highlightEntities = []
			},
			/**
			 * Toggle whether the detected entities may be edited. Driven by the
			 * sidebar-header switch so the user can switch into review mode
			 * (add legal grounds) after opening a file that started read-only,
			 * or back out again. Does not reload or re-extract entities — only
			 * the editability of the cards changes (see T03).
			 *
			 * Switching back to read-only (AAN→UIT) does NOT discard decisions
			 * the user already made while editing: the per-entity
			 * `_decisionBases` / `_decisionSkip` live on the entity rows in the
			 * anonymization store, untouched here. They stay frozen in state and
			 * are still applied on the next `anonymiseEntry` (its PATCH step
			 * compares against the extracted defaults). This keeps deliberate
			 * edits from silently vanishing on an accidental toggle; flipping
			 * the switch only locks further editing, it does not roll back (T04).
			 *
			 * @param {boolean} value `true` = editable review mode, `false` = read-only.
			 */
			setGrondslagen(value) {
				this.grondslagen = Boolean(value)
			},
			/**
			 * Record the latest text selection from the viewer surface. In
			 * add mode this is the pending candidate value for a new manual
			 * entity (T10/T11).
			 *
			 * @param {string} text Selected text.
			 */
			setSelection(text) {
				this.selection = text || ''
			},
			/**
			 * Toggle the "Add new data" mode. Turning it off clears the
			 * pending selection so a stale highlight does not linger.
			 *
			 * @param {boolean} value `true` enters the add-entity panel; `false` leaves it.
			 */
			setAddMode(value) {
				this.addMode = Boolean(value)
				if (!this.addMode) {
					this.selection = ''
				}
			},
			/**
			 * Replace the list of entities the viewer should highlight.
			 *
			 * @param {Array<{value: string, type: string}>} list Entities to mark.
			 */
			setHighlightEntities(list) {
				this.highlightEntities = Array.isArray(list) ? list : []
			},
		},
	},
)
