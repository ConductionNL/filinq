<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Integrity scan')"
		:description="
			t(
				'filinq',
				'Every night filinq checks every dossier, final document and anonymised copy for files that are missing, changed or orphaned. The check only reads; it changes no file.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<template v-else>
			<p v-if="run === null" class="integrity-scan__hint">
				{{ t('filinq', 'No integrity scan has run yet.') }}
			</p>
			<template v-else>
				<p class="integrity-scan__time">
					{{ t('filinq', 'Last check: {time}', { time: formatTime(run.startedAt) }) }}
					·
					{{ n('filinq', '%n record checked', '%n records checked', run.checked || 0) }}
				</p>
				<ul class="integrity-scan__counts">
					<li
						v-for="count in counts"
						:key="count.kind"
						class="integrity-scan__count" :class="[{ 'integrity-scan__count--found': count.value > 0 }]">
						{{ count.label }}
					</li>
				</ul>
				<ul v-if="findings.length > 0" class="integrity-scan__findings">
					<li
						v-for="(finding, index) in findings"
						:key="index"
						class="integrity-scan__finding">
						<span class="integrity-scan__kind">{{ kindLabel(finding.kind) }}</span>
						<a :href="recordLink(finding)">{{ findingName(finding) }}</a>
						<span class="integrity-scan__file">{{ t('filinq', 'File {fileId}', { fileId: finding.fileId }) }}</span>
					</li>
				</ul>
			</template>

			<NcButton variant="primary" :disabled="busy" @click="start">
				{{ t('filinq', 'Check now') }}
			</NcButton>
		</template>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcLoadingIcon, NcSettingsSection } from '@nextcloud/vue'

// The Integriteitscontrole section (whole-collection-integrity-scan REQ-DIS-02):
// the last stored run with its count per kind and a link per finding, and
// "Nu controleren" to start a run now. Both endpoints are admin only.
export const ENDPOINT = '/apps/filinq/api/settings/integrity-scan'

// Where a finding's record opens, by its schema.
const RECORD_PAGES = {
	dossier: (record) => '/apps/filinq/dossiers/' + encodeURIComponent(record),
	documentVersion: () => '/apps/filinq/versions',
	anonymizationLink: () => '/apps/filinq/anonymization',
}

export default {
	name: 'IntegrityScanSection',
	components: {
		NcButton,
		NcLoadingIcon,
		NcSettingsSection,
	},

	data() {
		return {
			loading: true,
			busy: false,
			run: null,
		}
	},

	computed: {
		/**
		 * The count per kind, in words ("2 ontbrekend").
		 *
		 * @return {Array<object>} Kind, value and label per kind.
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		counts() {
			const run = this.run || {}
			return [
				{ kind: 'missing', value: run.missing || 0, label: n('filinq', '%n missing file', '%n missing files', run.missing || 0) },
				{ kind: 'changed', value: run.changed || 0, label: n('filinq', '%n changed file', '%n changed files', run.changed || 0) },
				{ kind: 'orphaned', value: run.orphaned || 0, label: n('filinq', '%n orphaned file', '%n orphaned files', run.orphaned || 0) },
			]
		},

		/**
		 * The last run's findings.
		 *
		 * @return {Array<object>} The findings, or none.
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		findings() {
			return Array.isArray(this.run?.findings) ? this.run.findings : []
		},
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		n,

		/**
		 * Read the last stored run.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		async load() {
			this.loading = true
			try {
				const { data } = await axios.get(generateUrl(ENDPOINT))
				this.run = data?.run || null
			} catch {
				showError(t('filinq', 'The last integrity scan could not be read'))
			} finally {
				this.loading = false
			}
		},

		/**
		 * Start a run now ("Nu controleren") and show its result.
		 *
		 * @return {Promise<void>}
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		async start() {
			this.busy = true
			try {
				const { data } = await axios.post(generateUrl(ENDPOINT))
				this.run = data?.run || null
				showSuccess(t('filinq', 'The integrity scan has finished'))
			} catch {
				showError(t('filinq', 'The integrity scan could not run'))
			} finally {
				this.busy = false
			}
		},

		/**
		 * A kind in words.
		 *
		 * @param {string} kind missing, changed or orphaned.
		 * @return {string} The label.
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		kindLabel(kind) {
			return {
				missing: t('filinq', 'Missing'),
				changed: t('filinq', 'Changed'),
				orphaned: t('filinq', 'Orphaned'),
			}[kind] || kind
		},

		/**
		 * The link to a finding's record.
		 *
		 * @param {object} finding The finding.
		 * @return {string} The URL.
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		recordLink(finding) {
			const page = RECORD_PAGES[finding.schema]
			if (page) {
				return generateUrl(page(String(finding.record || '')))
			}
			return generateUrl('/f/' + encodeURIComponent(String(finding.fileId)))
		},

		/**
		 * A finding's record name, or its id when it has none.
		 *
		 * @param {object} finding The finding.
		 * @return {string} The name.
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		findingName(finding) {
			return finding.name || String(finding.record || '')
		},

		/**
		 * A stored time in the viewer's locale.
		 *
		 * @param {string} value An ISO 8601 time.
		 * @return {string} The time, or the value as is when it does not parse.
		 *
		 * @spec openspec/changes/archive/2026-11-20-whole-collection-integrity-scan/tasks.md#task-3-the-admin-section-reads-and-starts-a-scan
		 */
		formatTime(value) {
			const date = new Date(value)
			return Number.isNaN(date.getTime()) ? String(value || '') : date.toLocaleString()
		},
	},
}
</script>

<style scoped>
.integrity-scan__counts,
.integrity-scan__findings {
	list-style: none;
	margin: 8px 0;
	padding: 0;
}

.integrity-scan__counts {
	display: flex;
	gap: 16px;
}

.integrity-scan__count--found {
	color: var(--color-error-text);
	font-weight: bold;
}

.integrity-scan__finding {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border);
}

.integrity-scan__kind {
	font-weight: bold;
}

.integrity-scan__file,
.integrity-scan__time,
.integrity-scan__hint {
	color: var(--color-text-maxcontrast);
}
</style>
