/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The sanitization report: a clean copy of a sealed original warns that the
 * copy needs a new seal; any other run shows no such warning.
 *
 * @spec openspec/changes/document-sanitization/tasks.md#4-2
 */

jest.mock('@nextcloud/l10n', () => ({
	translate: (app, text, vars) => text.replace(/\{(\w+)\}/g, (m, k) => (vars && k in vars ? vars[k] : m)),
}))

const { renderStubbed } = require('../../tests/jest/renderGolden.js')
const SanitizationReportModal = require('./SanitizationReportModal.vue').default

const SEAL_WARNING = 'The original carries a seal. This clean copy does not: seal it again before you publish it.'

let rendered

/**
 * Mount the report over one run's answer.
 *
 * @param {object} result The sanitize answer.
 * @return {Promise<object>} The rendered report.
 */
async function mountReport(result) {
	rendered = await renderStubbed(SanitizationReportModal, { props: { show: true, result } })
	return rendered
}

const notes = (wrapper) => [...wrapper.vm.$el.querySelectorAll('x-ncnotecard')].map((n) => n.textContent.trim())

afterEach(() => {
	rendered?.unmount()
	rendered = null
})

describe('SanitizationReportModal', () => {
	it('warns that the clean copy of a sealed original needs a new seal', async () => {
		const wrapper = await mountReport({ sanitized: true, sanitizedFileName: 'a_sanitized.docx', report: {}, sourceSealed: true })

		expect(notes(wrapper)).toEqual([
			'Saved as a_sanitized.docx next to the original. The original is unchanged.',
			SEAL_WARNING,
		])
	})

	it('shows no seal warning for an unsealed original', async () => {
		const wrapper = await mountReport({ sanitized: true, sanitizedFileName: 'a_sanitized.docx', report: {}, sourceSealed: false })

		expect(notes(wrapper)).not.toContain(SEAL_WARNING)
		expect(notes(wrapper)).toHaveLength(1)
	})

	it('shows no seal warning when nothing was sanitized', async () => {
		const wrapper = await mountReport({ sanitized: false, sanitizationSkipped: true, sourceSealed: true })

		expect(notes(wrapper)).not.toContain(SEAL_WARNING)
	})
})
