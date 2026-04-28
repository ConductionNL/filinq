/* eslint-disable no-console */
import { defineStore } from 'pinia'

interface NavigationStoreState {
    selected: 'dashboard' | 'consent' | 'consentDetail' | 'settings' | 'anonymization' | 'batchAnonymization' | 'folderAnonymization' | 'templates' | 'templateDetail' | 'myDocuments';
    modal: string | null;
    dialog: string | null;
    transferData: string | null;
}

export const useNavigationStore = defineStore('ui', {
	state: () => ({
		selected: 'anonymization',
		modal: null,
		dialog: null,
		transferData: null,
	} as NavigationStoreState),
	actions: {
		setSelected(selected: NavigationStoreState['selected']) {
			this.selected = selected
			console.log('Active menu item set to ' + selected)
		},
		setModal(modal: NavigationStoreState['modal']) {
			this.modal = modal
			console.log('Active modal set to ' + modal)
		},
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
