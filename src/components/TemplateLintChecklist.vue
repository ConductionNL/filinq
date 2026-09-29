<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

The accessibility checklist beside a template preview: advice, never a
block on saving or previewing.

@spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
-->

<template>
	<section class="template-lint" data-testid="template-lint" :aria-label="t('filinq', 'Accessibility checklist')">
		<h4 class="template-lint__title">
			{{ t('filinq', 'Accessibility checklist') }}
		</h4>
		<p v-if="lint.length === 0" class="template-lint__clean">
			{{ t('filinq', 'No accessibility problems found in this preview.') }}
		</p>
		<ul v-else class="template-lint__list">
			<li v-for="(item, index) in lint" :key="index" :data-rule="item.rule">
				{{ message(item) }}
			</li>
		</ul>
	</section>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { lintMessage } from '../services/templateLint.js'

export default {
	name: 'TemplateLintChecklist',
	props: {
		lint: { type: Array, default: () => [] },
	},

	methods: {
		t,

		/**
		 * One finding as a sentence.
		 *
		 * @param {object} item The lint finding.
		 * @return {string} The sentence.
		 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-3.3
		 */
		message(item) {
			return lintMessage(item)
		},
	},
}
</script>

<style scoped>
.template-lint {
	margin: 8px 0;
	padding: 8px 12px;
	border-inline-start: 4px solid var(--color-warning);
	background: var(--color-background-hover);
}

.template-lint__title {
	margin: 0 0 4px;
}

.template-lint__clean {
	color: var(--color-text-maxcontrast);
}

.template-lint__list {
	margin: 0;
	padding-inline-start: 20px;
	list-style: disc;
}
</style>
