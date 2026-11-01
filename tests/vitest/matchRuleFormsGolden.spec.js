/**
 * Golden: the script logic of the two entity-policy form modals (publish
 * always, publish never), recorded on the per-modal implementations.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of existing policy form behaviour
 */

import { join } from 'node:path'
import { describe, it, vi } from 'vitest'
import { golden } from './golden/golden.js'
import { createInstance, loadSfcOptions } from './sfc/sfcOptions.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[' + text + ']',
}))

const MODALS = {
	standingConsent: 'dialogs/StandingConsentFormModal.vue',
	prohibition: 'dialogs/ProhibitionFormModal.vue',
}

const RECORD = {
	entityText: 'Jan',
	primaryName: 'Jan',
	reason: 'court order',
	consentMethod: 'paper',
	severity: 'high',
	active: false,
	matchRules: [
		{ type: 'bsn', value: '123' },
		{ type: 'exact', value: 'Jan' },
	],
}

/**
 * Observable state of a modal instance, plus the events since last time.
 *
 * @param {object} vm Instance.
 * @return {object} Snapshot.
 */
function snapshot(vm) {
	return {
		form: vm.form,
		editing: vm.editing,
		dialogTitle: vm.dialogTitle,
		canSubmit: vm.canSubmit,
		onlyNameRules: vm.onlyNameRules ?? '(none)',
		emitted: vm.$emitted.splice(0),
	}
}

describe('match rule forms golden', () => {
	for (const [key, rel] of Object.entries(MODALS)) {
		it(key, async () => {
			const options = await loadSfcOptions(
				join(import.meta.dirname, '../../src', rel),
			)
			const parts = [...(options.mixins || []), options]
			const declared = Object.assign({}, ...parts.map((o) => o.props))
			const emits = parts.flatMap((o) => o.emits || [])
			const props = Object.fromEntries(
				Object.entries(declared).map(([name, def]) => [
					name,
					{ ...def, type: def.type.name },
				]),
			)
			const record = { props, emits }
			const fresh = createInstance(options, { props: { open: false } })
			record.initial = snapshot(fresh)
			record.choices = {
				matchTypeOptions: fresh.matchTypeOptions,
				entityTypeOptions: fresh.entityTypeOptions,
				severityOptions: fresh.severityOptions ?? '(none)',
				consentMethodOptions: fresh.consentMethodOptions ?? '(none)',
			}
			await fresh.$fireWatch('open', false, true)
			record.closedWatch = snapshot(fresh)
			fresh.form.entityText = ' '
			fresh.form.primaryName = ' '
			await fresh.$fireWatch('open', true, false)
			record.openedBlank = snapshot(fresh)
			fresh.form.entityText = 'X'
			fresh.form.primaryName = 'X'
			fresh.form.reason = 'R'
			fresh.form.consentMethod = 'paper'
			record.namedNoRules = snapshot(fresh)
			fresh.addRule()
			record.oneEmptyRule = snapshot(fresh)
			fresh.form.matchRules[0].value = 'Jan'
			record.oneNameRule = snapshot(fresh)
			fresh.addRule()
			fresh.form.matchRules[1].type = 'kvk'
			fresh.form.matchRules[1].value = '999'
			record.twoRules = snapshot(fresh)
			fresh.form.matchRules[1].type = ''
			record.untypedRule = snapshot(fresh)
			fresh.removeRule(1)
			record.removed = snapshot(fresh)
			fresh.form.reason = ''
			fresh.form.consentMethod = ''
			record.missingSecondField = snapshot(fresh)
			fresh.submit()
			const sent = fresh.$emitted[0][1]
			sent.matchRules[0].value = 'changed after submit'
			record.submitted = snapshot(fresh)
			fresh.onCancel()
			record.cancelled = snapshot(fresh)

			const source = JSON.parse(JSON.stringify(RECORD))
			const edit = createInstance(options, {
				props: { open: true, editingRecord: source, saving: true },
			})
			await edit.$fireWatch('open', true, false)
			edit.form.matchRules[0].value = 'edited'
			record.editOpened = { ...snapshot(edit), source }
			edit.submit()
			record.editSubmitted = snapshot(edit)

			const bare = createInstance(options, {
				props: { open: true, editingRecord: { matchRules: 'x' } },
			})
			await bare.$fireWatch('open', true, false)
			record.editNoRules = snapshot(bare)

			golden('matchRuleForms-' + key, record)
		})
	}
})
