/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * Golden test for the admin settings page: the rendered template (children
 * stubbed) for several setting states, and which setting every switch and
 * radio writes when it changes. RECORD_GOLDEN=1 rewrites the golden file.
 */

import fs from 'fs'
import path from 'path'
import Settings from './Settings.vue'
import { renderStubbed } from '../../../tests/jest/renderGolden.js'
import { defaultSettings } from '../../services/settingsDefaults.js'

jest.mock(
	'@nextcloud/dialogs',
	() => ({
		showError: () => {},
		showSuccess: () => {},
	}),
	{ virtual: true },
)
jest.mock('@conduction/nextcloud-vue', () => ({ CnAdminSettingsShell: {} }), {
	virtual: true,
})
jest.mock(
	'@nextcloud/vue',
	() => ({
		NcButton: {},
		NcCheckboxRadioSwitch: {},
		NcLoadingIcon: {},
		NcNoteCard: {},
		NcSelect: {},
		NcSettingsSection: {},
		NcTextField: {},
	}),
	{ virtual: true },
)
jest.mock('vue-material-design-icons/AccountSearchOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/FileExportOutline.vue', () => ({}))
jest.mock('vue-material-design-icons/OpenInNew.vue', () => ({}))
jest.mock('vue-material-design-icons/Plus.vue', () => ({}))
jest.mock('vue-material-design-icons/Restart.vue', () => ({}))
jest.mock('../../components/AnonymiserBackendWarning.vue', () => ({}))
jest.mock('./EmailIngestionSettings.vue', () => ({}))
jest.mock('./EntityTypeSelector.vue', () => ({}))
jest.mock('./PageLayoutSettings.vue', () => ({}))
jest.mock('./SignerIdentitySettings.vue', () => ({}))
jest.mock('../../services/conformance.js', () => ({
	fetchValidatorStatus: () => new Promise(() => {}),
}))

const GOLDEN = path.join(__dirname, '../../../tests/jest/golden/settings-page.json')
const RECORD = process.env.RECORD_GOLDEN === '1'
const golden = RECORD ? {} : JSON.parse(fs.readFileSync(GOLDEN, 'utf8'))

/**
 * Every boolean setting flipped from its default.
 *
 * @return {object} Settings.
 */
function flippedSettings() {
	const settings = defaultSettings()
	for (const [key, value] of Object.entries(settings)) {
		if (typeof value === 'boolean') {
			settings[key] = !value
		}
	}
	settings.signing_provider = 'libresign'
	settings['filinq.anonymisation.default_output_format'] = 'preserve'
	return settings
}

const CASES = {
	defaults: {},
	flipped: {
		isAdmin: true,
		libresignAvailable: true,
		openRegisterInstalled: true,
		settings: flippedSettings(),
		anonymiserBackend: { warningDismissed: true },
		ocrStatus: { tesseractAvailable: true, tesseractVersion: '5.3' },
		veraPdfStatus: { available: true, version: '1.26' },
	},
	unset: {
		settings: {
			...defaultSettings(),
			enable_language_detection: undefined,
			ocr_enabled: 'true',
			signing_enabled: null,
			'filinq.confidentiality.prioritise_analysis': 0,
		},
	},
	mixed: {
		isAdmin: true,
		settings: {
			...defaultSettings(),
			enable_keyword_extraction: false,
			ocr_on_arrival: false,
			signing_enabled: true,
			'filinq.anonymisation.default_output_format': 'pdf',
		},
	},
}

/**
 * What changes in the page data when a listener fires with a sentinel.
 *
 * @param {object} vm The page.
 * @param {Function} fire Fires the listener.
 * @return {object} Changed data paths and their new values.
 */
function effectOf(vm, fire) {
	const before = JSON.parse(JSON.stringify(vm.$data))
	fire()
	const after = JSON.parse(JSON.stringify(vm.$data))
	const changed = {}
	for (const key of Object.keys(after)) {
		if (key === 'settings') {
			for (const s of Object.keys(after.settings)) {
				if (
					JSON.stringify(after.settings[s])
					!== JSON.stringify(before.settings[s])
				) {
					changed['settings.' + s] = after.settings[s]
				}
			}
		} else if (JSON.stringify(after[key]) !== JSON.stringify(before[key])) {
			changed[key] = after[key]
		}
	}
	return changed
}

beforeAll(() => {
	global.t = (appId, text, vars) =>
		vars ? text + ' ' + JSON.stringify(vars) : text
	global.n = (appId, one, many, count) => (count === 1 ? one : many)
	global.fetch = () => new Promise(() => {})
})

afterAll(() => {
	if (RECORD) {
		fs.writeFileSync(GOLDEN, JSON.stringify(golden, null, '\t') + '\n')
	}
})

describe('Settings page golden', () => {
	for (const [name, data] of Object.entries(CASES)) {
		it(`renders the ${name} state as recorded`, async () => {
			const page = await renderStubbed(Settings, {
				data,
				real: ['SettingSwitchRow'],
			})
			const actual = { html: page.html().split('><').join('>\n<').split('\n') }
			const switches = page.stubs.filter(
				(s) => s.name === 'NcCheckboxRadioSwitch',
			)
			actual.switches = switches.map((s, index) => {
				const listener = s.attrs['onUpdate:modelValue']
				return {
					label: s.attrs['aria-label'] ?? s.attrs.value ?? null,
					writes: listener
						? effectOf(page.vm, () => listener('SENTINEL-' + index))
						: null,
				}
			})
			page.unmount()
			if (RECORD) {
				golden[name] = actual
			} else {
				expect(actual.html).toEqual(golden[name].html)
				expect(actual.switches).toEqual(golden[name].switches)
			}
		})
	}
})
