<template>
	<div class="document-compare" data-testid="document-compare">
		<section
			v-for="pane in panes"
			:key="pane.side"
			class="document-compare__pane"
			:data-testid="'document-compare-' + pane.side"
			:aria-label="pane.label">
			<header class="document-compare__header">
				<h3 class="document-compare__title">
					{{ pane.label }}
				</h3>
				<span class="document-compare__name">{{ pane.fileName }}</span>
				<a
					v-if="pane.downloadUrl"
					class="document-compare__download"
					:href="pane.downloadUrl"
					download>
					{{ t('filinq', 'Download') }}
				</a>
			</header>
			<div class="document-compare__body">
				<component
					:is="pane.component"
					v-if="pane.component"
					v-bind="pane.props" />
				<p v-else class="document-compare__unsupported">
					{{ t('filinq', 'This file type cannot be previewed.') }}
				</p>
			</div>
		</section>
	</div>
</template>

<script>
/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The original and the delivered file side by side, each in the viewer the
 * file viewer uses for its type. Mounted by other apps through
 * `OCA.Filinq.mountCompare` (src/compare.js).
 */
import { translate as t } from '@nextcloud/l10n'
import OdtViewer from '../viewers/OdtViewer.vue'
import PdfViewer from '../viewers/PdfViewer.vue'
import TextViewer from '../viewers/TextViewer.vue'
import WordViewer from '../viewers/WordViewer.vue'
import { COMPARE_SIDES, comparePane } from '../../compare/comparePanes.js'

export default {
	name: 'DocumentCompare',

	components: {
		OdtViewer,
		PdfViewer,
		TextViewer,
		WordViewer,
	},

	props: {
		original: {
			type: Object,
			required: true,
		},

		delivered: {
			type: Object,
			required: true,
		},

		// Optional `{ original, delivered }` pane titles from the host.
		labels: {
			type: Object,
			default: () => ({}),
		},
	},

	computed: {
		/**
		 * One entry per side, in reading order: the original first.
		 *
		 * @return {Array<object>}
		 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-split-view-is-mountable-by-another-app-req-ddarw-014
		 */
		panes() {
			const defaults = {
				original: t('filinq', 'Original'),
				delivered: t('filinq', 'Delivered'),
			}
			return COMPARE_SIDES.map((side) => {
				const file = this[side]
				return {
					side,
					label: this.labels[side] || defaults[side],
					fileName: file.fileName || '',
					...comparePane(file),
				}
			})
		},
	},

	methods: {
		t,
	},
}
</script>

<style scoped>
.document-compare {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 8px;
	height: 100%;
	min-height: 0;
}

@media (max-width: 768px) {
	.document-compare {
		grid-template-columns: 1fr;
	}
}

.document-compare__pane {
	display: flex;
	flex-direction: column;
	min-height: 0;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	overflow: hidden;
}

.document-compare__header {
	display: flex;
	align-items: baseline;
	gap: 8px;
	padding: 8px 12px;
	border-bottom: 1px solid var(--color-border);
	background: var(--color-main-background);
}

.document-compare__title {
	margin: 0;
	font-size: 1em;
	font-weight: bold;
}

.document-compare__name {
	flex: 1;
	min-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	color: var(--color-text-maxcontrast);
}

.document-compare__download {
	color: var(--color-primary-element);
	text-decoration: underline;
}

.document-compare__body {
	flex: 1;
	min-height: 0;
	overflow: auto;
	background: var(--color-background-dark);
}

.document-compare__unsupported {
	padding: 48px 16px;
	color: var(--color-text-maxcontrast);
	text-align: center;
}
</style>
