/* eslint-disable no-console */
import { defineStore } from 'pinia'
import { useNavigationStore } from './navigation.ts'

/**
 * Pinia store for the in-app file viewer page.
 *
 * Tracks which file is currently being previewed and the UI state of the viewer
 * (anonymized-toggle stub, latest text selection). Actual file loading happens
 * inside the per-MIME viewer component.
 *
 * Navigation: opening a file switches the active view to 'fileViewer' and
 * remembers the previous view so close() can return to where the user came from.
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
			previousView: null,
			showAnonymized: false,
			selection: '',
		}),
		actions: {
			/**
			 * Open the viewer page for a given file.
			 * Remembers the current navigation view so close() can return to it.
			 *
			 * @param {object} file File descriptor.
			 * @param {number} file.fileId   Nextcloud file id.
			 * @param {string} file.fileName File name with extension.
			 * @param {string} file.mimeType MIME type.
			 * @param {string} file.path     Absolute path inside the user's storage (e.g. /DocuDesk/foo.pdf).
			 */
			open(file) {
				const navigationStore = useNavigationStore()
				this.previousView = navigationStore.selected
				this.currentFile = file
				this.showAnonymized = false
				this.selection = ''
				navigationStore.setSelected('fileViewer')
			},
			/**
			 * Close the viewer page and navigate back to the previous view.
			 */
			close() {
				const navigationStore = useNavigationStore()
				const back = this.previousView || 'myDocuments'
				this.currentFile = null
				this.selection = ''
				this.previousView = null
				navigationStore.setSelected(back)
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
