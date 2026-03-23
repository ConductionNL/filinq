/* eslint-disable no-console */
// The store script handles app wide variables (or state).
import pinia from '../pinia.js'
import { useNavigationStore } from './modules/navigation.ts'
import { useConsentStore } from './modules/consent.js'
import { useAnonymizationStore } from './modules/anonymization.js'
import { useBatchAnonymizationStore } from './modules/batchAnonymization.js'
import { useSettingsStore } from './modules/settings.js'

const navigationStore    = useNavigationStore(pinia)
const consentStore       = useConsentStore(pinia)
const anonymizationStore      = useAnonymizationStore(pinia)
const batchAnonymizationStore = useBatchAnonymizationStore(pinia)

/**
 * Initialize all stores that require async setup (e.g. fetching settings).
 *
 * @return {Promise<void>}
 */
async function initializeStores()
{
    const settingsStore = useSettingsStore(pinia)
    await settingsStore.fetchSettings()

}

export {
    navigationStore,
    consentStore,
    anonymizationStore,
    batchAnonymizationStore,
    useSettingsStore,
    initializeStores,
}
