/**
 * Golden: the component gallery's markup, with the kind cell of its table
 * demos rendered for a dossier, an anonymised file and a concept. The page
 * reads `t` from the app's global properties, so the test hands it in.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec exclude characterization test of the component gallery
 */

import { join } from 'node:path'
import { describe, it } from 'vitest'
import { golden } from './golden/golden.js'
import { renderSfc } from './sfc/sfcRender.js'

const GALLERY = join(import.meta.dirname, '../../src/views/gallery')
const FILE = join(GALLERY, 'ComponentGallery.vue')

const ROWS = {
	dossier: { isFolder: true, isAnonymized: true },
	anonymized: { isFolder: false, isAnonymized: true },
	concept: { isFolder: false, isAnonymized: false },
}

describe('component gallery', () => {
	for (const [name, row] of Object.entries(ROWS)) {
		it(name, async () => {
			const html = await renderSfc(FILE, {
				data: { t: (app, text) => '[' + text + ']' },
				expand: { GalleryMiniCard: join(GALLERY, 'GalleryMiniCard.vue') },
				slotProps: {
					'column-kind': { row },
					card: { object: { ...row, fileName: name } },
				},
			})
			golden('component-gallery-' + name, html.split('\n'))
		})
	}
})
