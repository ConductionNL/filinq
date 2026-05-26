import { defineStore } from 'pinia'

interface NavigationStoreState {
    selected: 'dashboard' | 'consent' | 'consentDetail' | 'settings' | 'anonymization' | 'anonymizationPoc' | 'batchAnonymization' | 'folderAnonymization' | 'templates' | 'templateDetail' | 'standingConsents' | 'prohibitions';
    modal: string;
    dialog: string;
    transferData: string;
}

export const useNavigationStore = defineStore('ui', {
	state: () => ({
		modal: null,
		dialog: null,
		transferData: null,
	} as NavigationStoreState),
	actions: {
		setModal(modal: NavigationStoreState['modal']) {
			this.modal = modal
		},
		setDialog(dialog: NavigationStoreState['dialog']) {
			this.dialog = dialog
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
