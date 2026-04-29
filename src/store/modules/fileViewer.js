/* eslint-disable no-console */
import { defineStore } from 'pinia'

/**
 * Pinia store for the in-app file viewer modal.
 *
 * Tracks which file is currently being previewed and the UI state of the modal
 * (open/closed, anonymized-toggle stub, latest text selection). Actual file
 * loading happens inside the per-MIME viewer component.
 *
 * Future-ready hooks (no logic yet, only state):
 *   - selection:        latest highlighted text from the viewer.
 *   - showAnonymized:   toggle between original and anonymized variant.
 */
export const useFileViewerStore = defineStore(
	'fileViewer',
	{
		state: () => ({
			isOpen: false,
			currentFile: null, // { fileId, fileName, mimeType, path }
			showAnonymized: false,
			selection: '',
		}),
		actions: {
			/**
			 * Open the modal for a given file.
			 *
			 * @param {object} file File descriptor.
			 * @param {number} file.fileId   Nextcloud file id.
			 * @param {string} file.fileName File name with extension.
			 * @param {string} file.mimeType MIME type.
			 * @param {string} file.path     Absolute path inside the user's storage (e.g. /DocuDesk/foo.pdf).
			 */
			open(file) {
				this.currentFile = file
				this.showAnonymized = false
				this.selection = ''
				this.isOpen = true
			},
			/**
			 * Close the modal and reset state.
			 */
			close() {
				this.isOpen = false
				this.currentFile = null
				this.selection = ''
			},
			/**
			 * Toggle preview between original and anonymized variant.
			 * NOTE: this is a UI stub; the actual swap is wired up in a follow-up task.
			 */
			toggleAnonymized() {
				this.showAnonymized = !this.showAnonymized
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
