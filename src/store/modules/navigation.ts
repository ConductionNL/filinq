/* eslint-disable no-console */
import { defineStore } from 'pinia'

interface NavigationStoreState {
    selected: 'dashboard' | 'consent' | 'consentDetail' | 'settings' | 'anonymization' | 'batchAnonymization' | 'folderAnonymization' | 'templates' | 'templateDetail';
    modal: string;
    dialog: string;
    transferData: string;
}

export const useNavigationStore = defineStore('ui', {
	state: () => ({
		selected: 'dashboard',
		modal: null,
		dialog: null,
		transferData: null,
	} as NavigationStoreState),
	actions: {
		/**
		 * Switch the active DocuDesk view in the SPA navigation shell.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-navigation-menu-req-dash-03
		 */
		setSelected(selected: NavigationStoreState['selected']) {
			this.selected = selected
			console.log('Active menu item set to ' + selected)
		},
		/**
		 * Set the currently active modal identifier.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-navigation-menu-req-dash-03
		 */
		setModal(modal: NavigationStoreState['modal']) {
			this.modal = modal
			console.log('Active modal set to ' + modal)
		},
		/**
		 * Set the currently active dialog identifier.
		 *
		 * @spec openspec/specs/dashboard/spec.md#requirement-navigation-menu-req-dash-03
		 */
		setDialog(dialog: NavigationStoreState['dialog']) {
			this.dialog = dialog
			console.log('Active dialog set to ' + dialog)
		},
		setTransferData(transferData: NavigationStoreState['transferData']) {
			this.transferData = transferData
		},
		getTransferData(): NavigationStoreState['transferData'] {
			const tempData = this.transferData
			this.transferData = null
			return tempData
		},
	},
})
