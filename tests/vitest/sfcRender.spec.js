/**
 * The SFC renderer renders a component's template with its UI components as
 * stubs, and renders a named child component for real on request.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude test of the characterization test harness
 */

import { join } from 'node:path'
import { describe, expect, it, vi } from 'vitest'
import { renderSfc } from './sfc/sfcRender.js'

vi.mock('@nextcloud/l10n', () => ({
	translate: (app, text) => '[' + text + ']',
}))

const DIR = join(import.meta.dirname, 'sfc', 'fixtures')

describe('sfcRender', () => {
	it('stubs child components and passes scoped slot props', async () => {
		const html = await renderSfc(join(DIR, 'PanelFixture.vue'), {
			data: { rows: ['x', 'y'] },
			slotProps: { 'row-actions': { row: 'r1' } },
		})
		expect(html).toBe(
			[
				'<stub-CnIndexPage data-objects="[&quot;x&quot;,&quot;y&quot;]" data-title="[Panel]">',
				'<template-slot name="row-actions">',
				'<stub-LabelFixture data-text="r1">',
				'</stub-LabelFixture>',
				'</template-slot>',
				'</stub-CnIndexPage>',
			].join('\n'),
		)
	})

	it('renders an expanded child for real', async () => {
		const html = await renderSfc(join(DIR, 'PanelFixture.vue'), {
			slotProps: { 'row-actions': { row: 'r1' } },
			expand: { LabelFixture: join(DIR, 'LabelFixture.vue') },
		})
		expect(html).toContain(
			'<stub-NcButton data-variant="tertiary">\n<template-slot name="default">\n<b>r1</b>',
		)
	})
})
