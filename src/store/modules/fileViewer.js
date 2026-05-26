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
			selection: '',
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
			 */
			open(file) {
				this.currentFile = file
				this.originalFile = file
				this.anonymizedFile = null
				this.showAnonymized = false
				this.selection = ''
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
			},
			/**
			 * Record the latest text selection from the viewer surface.
			 *
			 * @param {string} text Selected text.
			 */
			setSelection(text) {
				this.selection = text || ''
			},
		},
	},
)
