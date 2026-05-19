/* eslint-disable no-console */
import { defineStore } from 'pinia'

/**
 * Pinia store for the in-app file viewer.
 *
 * Tracks which file is currently being previewed and the UI state of the viewer
 * (anonymized-toggle stub, latest text selection). Actual file loading happens
 * inside the per-MIME viewer component.
 *
 * The viewer is rendered as a panel inside the My Documents page when
 * currentFile is set — there is no dedicated route. Opening / closing a file
 * is therefore a pure store mutation; the URL stays on /my-documents.
 *
 * Future-ready hooks (no logic yet, only state):
 *   - selection:        latest highlighted text from the viewer.
 *   - showAnonymized:   toggle between original and anonymized variant.
 */
export const useFileViewerStore = defineStore(
	'fileViewer',
	{
		state: () => ({
			currentFile: null, // { fileId, fileName, mimeType, path }
			showAnonymized: false,
			selection: '',
		}),
		actions: {
			/**
			 * Open the viewer for a given file.
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
			},
			/**
			 * Close the viewer. Host page reverts to file list.
			 */
			close() {
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
