/* eslint-disable no-console */
import { defineStore } from 'pinia'

interface NavigationStoreState {
    selected: 'dashboard' | 'publication' | 'catalogi' | 'publicationType' | 'organizations' | 'themes' | 'search' | 'directory' | 'pages' | 'files' | 'reports' | 'anonymization' | 'templates';
    selectedCatalogus: string;
    modal: string;
    dialog: string;
    transferData: string;
    sidebarState: {
        reports: boolean;
        report: boolean;
    };
}

export const useNavigationStore = defineStore('ui', {
	state: () => ({
		// The currently active menu item, defaults to '' which triggers the dashboard
		selected: 'dashboard',
		// The currently selected catalogi within 'publications'
		selectedCatalogus: null,
		// The currently active modal, managed trough the state to ensure that only one modal can be active at the same time
		modal: null,
		// The currently active dialog
		dialog: null,
		// Any data needed in various models, dialogs, views which cannot be transferred through normal means or without writing crappy/excessive code
		transferData: null,
		// State for managing sidebars
		sidebarState: {
			reports: false,
			report: false,
		},
	} as NavigationStoreState),
	actions: {
		setSelected(selected: NavigationStoreState['selected']) {
			this.selected = selected
			console.log('Active menu item set to ' + selected)
		},
		setSelectedCatalogus(selectedCatalogus: string) {
			this.selectedCatalogus = selectedCatalogus
			console.log('Active catalogus menu set to ' + selectedCatalogus)
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
		setSidebar(sidebarName: keyof NavigationStoreState['sidebarState'], isOpen: boolean) {
			this.sidebarState[sidebarName] = isOpen
			console.log(`Sidebar ${sidebarName} set to ${isOpen}`)
		},
		closeSidebar(sidebarName: keyof NavigationStoreState['sidebarState']) {
			this.sidebarState[sidebarName] = false
			console.log(`Sidebar ${sidebarName} closed`)
		},
		openSidebar(sidebarName: keyof NavigationStoreState['sidebarState']) {
			this.sidebarState[sidebarName] = true
			console.log(`Sidebar ${sidebarName} opened`)
		},
		closeAllSidebars() {
			Object.keys(this.sidebarState).forEach(key => {
				this.sidebarState[key as keyof NavigationStoreState['sidebarState']] = false
			})
			console.log('All sidebars closed')
		},
	},
})
