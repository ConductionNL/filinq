/* eslint-disable no-console */
// The store script handles app wide variables (or state).
import pinia from '../pinia.js'
import { useNavigationStore } from './modules/navigation.ts'
import { useConsentStore } from './modules/consent.js'
import { useAnonymizationStore } from './modules/anonymization.js'
import { useBatchAnonymizationStore } from './modules/batchAnonymization.js'
import { useFolderAnonymizationStore } from './modules/folderAnonymization.js'
import { useMyDocumentsStore } from './modules/myDocuments.js'
import { useFileViewerStore } from './modules/fileViewer.js'
import { useSettingsStore } from './modules/settings.js'

const navigationStore = useNavigationStore(pinia)
const consentStore = useConsentStore(pinia)
const anonymizationStore = useAnonymizationStore(pinia)
const batchAnonymizationStore = useBatchAnonymizationStore(pinia)
const folderAnonymizationStore = useFolderAnonymizationStore(pinia)
const myDocumentsStore = useMyDocumentsStore(pinia)
const fileViewerStore = useFileViewerStore(pinia)

/**
 * Initialize all stores that require async setup (e.g. fetching settings).
 *
 * @return {Promise<void>}
 */
async function initializeStores() {
	const settingsStore = useSettingsStore(pinia)
	await settingsStore.fetchSettings()

}

export {
	navigationStore,
	consentStore,
	anonymizationStore,
	batchAnonymizationStore,
	folderAnonymizationStore,
	myDocumentsStore,
	fileViewerStore,
	useSettingsStore,
	initializeStores,
}
