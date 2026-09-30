<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

@spec openspec/changes/document-sanitization/tasks.md#4-1
-->

<template>
	<NcModal :show="show" :name="t('filinq', 'Sanitize document')" @close="$emit('close')">
		<div class="sanitization-report" data-testid="sanitization-report">
			<h2>{{ t('filinq', 'Sanitize document') }}</h2>
			<NcLoadingIcon v-if="loading" :size="32" />
			<NcNoteCard v-else-if="error" type="error">
				{{ error }}
			</NcNoteCard>
			<template v-else-if="result && result.sanitizationSkipped">
				<NcNoteCard type="warning" data-testid="sanitization-skipped">
					{{
						t(
							'filinq',
							'This PDF was not sanitized: OpenRegister cannot clean PDFs yet. Nothing was changed.',
						)
					}}
				</NcNoteCard>
			</template>
			<template v-else-if="result">
				<NcNoteCard type="success">
					{{
						t('filinq', 'Saved as {name} next to the original. The original is unchanged.', {
							name: result.sanitizedFileName,
						})
					}}
				</NcNoteCard>
				<p v-if="rows.length === 0">
					{{ t('filinq', 'Nothing hidden was found.') }}
				</p>
				<table v-else class="sanitization-report__table">
					<thead>
						<tr>
							<th scope="col">{{ t('filinq', 'What was removed') }}</th>
							<th scope="col">{{ t('filinq', 'Count') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="row in rows" :key="row.key" :data-testid="`sanitization-${row.key}`">
							<td>{{ row.label }}</td>
							<td>{{ row.count }}</td>
						</tr>
					</tbody>
				</table>
				<p class="sanitization-report__hint">
					{{ t('filinq', 'Only the counts are kept. The removed content is not stored anywhere.') }}
				</p>
			</template>
		</div>
	</NcModal>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon, NcModal, NcNoteCard } from '@nextcloud/vue'
import { reportRows } from '../services/sanitization.js'

/**
 * The outcome of one sanitization run: where the clean file went and how
 * many items were removed per category, or why nothing was done.
 *
 * @spec openspec/changes/document-sanitization/tasks.md#4-1
 */
export default {
	name: 'SanitizationReportModal',
	components: { NcLoadingIcon, NcModal, NcNoteCard },
	props: {
		show: { type: Boolean, default: false },
		loading: { type: Boolean, default: false },
		error: { type: String, default: '' },
		result: { type: Object, default: null },
	},

	emits: ['close'],
	computed: {
		/**
		 * The report rows.
		 *
		 * @return {Array<object>}
		 * @spec openspec/changes/document-sanitization/tasks.md#4-1
		 */
		rows() {
			return reportRows(this.result?.report)
		},
	},

	methods: { t },
}
</script>

<style scoped>
.sanitization-report {
	padding: calc(var(--default-grid-baseline) * 5);
}

.sanitization-report__table {
	width: 100%;
	border-collapse: collapse;
}

.sanitization-report__table th,
.sanitization-report__table td {
	padding: calc(var(--default-grid-baseline) * 2);
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}

.sanitization-report__hint {
	color: var(--color-text-maxcontrast);
	margin-top: calc(var(--default-grid-baseline) * 3);
}
</style>
