/* eslint-disable no-console */
// The store script handles app wide variables (or state), for the use of these variables and there governing concepts read the design.md
import pinia from '../pinia.js'
import { useNavigationStore } from './modules/navigation.ts'
import { useObjectStore } from './modules/object.js'
import { useReportStore } from './modules/report.js'
import { useTemplateStore } from './modules/template.js'

const navigationStore = useNavigationStore(pinia)
const objectStore = useObjectStore(pinia)
const reportStore = useReportStore(pinia)
const templateStore = useTemplateStore(pinia)

// Create an alias for searchStore to objectStore for backward compatibility
const searchStore = objectStore

export {
	// generic
	navigationStore,
	objectStore,
	reportStore,
	templateStore,
	searchStore,
}
