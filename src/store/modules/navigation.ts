import { defineStore } from 'pinia'

interface NavigationStoreState {
    modal: string | null;
    dialog: string | null;
    transferData: string | null;
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
