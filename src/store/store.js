/* eslint-disable no-console */
// The store script handles app wide variables (or state)
import pinia from '../pinia.js'
import { useNavigationStore } from './modules/navigation.ts'
import { useConsentStore } from './modules/consent.js'
import { useAnonymizationStore } from './modules/anonymization.js'

const navigationStore    = useNavigationStore(pinia)
const consentStore       = useConsentStore(pinia)
const anonymizationStore = useAnonymizationStore(pinia)

export {
    navigationStore,
    consentStore,
    anonymizationStore,
}
