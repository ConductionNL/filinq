<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
  -
  - @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Case system sources')"
		:description="t('filinq', 'The case systems Filinq takes documents from, and whether each one is still syncing.')">
		<NcLoadingIcon v-if="loading" :size="32" />
		<NcNoteCard v-else-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<p v-else-if="sources.length === 0" class="bridge-sources__empty" data-testid="bridge-sources-empty">
			{{ t('filinq', 'No case system sources yet. Sources are set up in OpenConnector, which syncs the documents into Filinq.') }}
		</p>
		<table v-else class="bridge-sources__table" data-testid="bridge-sources">
			<thead>
				<tr>
					<th scope="col">
						{{ t('filinq', 'Name') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Vendor') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Type') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Last sync') }}
					</th>
					<th scope="col">
						{{ t('filinq', 'Health') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="source in sources" :key="source.uuid || source.name" data-testid="bridge-source">
					<td>{{ source.name }}</td>
					<td>{{ source.vendor }}</td>
					<td>{{ typeLabel(source.sourceType) }}</td>
					<td>{{ formatDate(source.lastSyncAt) }}</td>
					<td>
						<CnStatusBadge :label="health(source.health)" :colorMap="colorMap" />
						<p
							v-if="source.health === 'failing' && source.lastSyncError"
							class="bridge-sources__error"
							data-testid="bridge-source-error">
							{{ source.lastSyncError }}
						</p>
					</td>
				</tr>
			</tbody>
		</table>
	</NcSettingsSection>
</template>

<script>
import { CnStatusBadge } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon, NcNoteCard, NcSettingsSection } from '@nextcloud/vue'
import { fetchBridgeSources, healthColorMap, healthLabel, sourceTypeLabel } from '../../services/bridge.js'

/**
 * The bridge status panel (zgw-document-bridge REQ-DDZGW-008): each case
 * system source with its vendor, type, last sync and health. Without any
 * source it explains that sources come from OpenConnector.
 *
 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
 */
export default {
	name: 'BridgeSourcesSettings',
	components: { CnStatusBadge, NcLoadingIcon, NcNoteCard, NcSettingsSection },

	data() {
		return { loading: true, error: '', sources: [], colorMap: healthColorMap() }
	},

	mounted() {
		this.load()
	},

	methods: {
		t,
		health: healthLabel,
		typeLabel: sourceTypeLabel,

		/**
		 * Read the sources.
		 *
		 * @return {Promise<void>}
		 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
		 */
		async load() {
			try {
				this.sources = await fetchBridgeSources()
			} catch {
				this.error = t('filinq', 'Could not load the case system sources')
			} finally {
				this.loading = false
			}
		},

		/**
		 * A sync moment in the reader's format, or "Never".
		 *
		 * @param {string} value ISO 8601.
		 * @return {string}
		 * @spec openspec/changes/zgw-document-bridge/tasks.md#3-1
		 */
		formatDate(value) {
			if (!value) {
				return t('filinq', 'Never')
			}
			const parsed = new Date(value)
			return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString()
		},
	},
}
</script>

<style scoped>
.bridge-sources__table {
	width: 100%;
	border-collapse: collapse;
}

.bridge-sources__table th,
.bridge-sources__table td {
	padding: calc(var(--default-grid-baseline) * 2);
	text-align: start;
	border-bottom: 1px solid var(--color-border);
	vertical-align: top;
}

.bridge-sources__empty,
.bridge-sources__error {
	color: var(--color-text-maxcontrast);
}

.bridge-sources__error {
	margin: var(--default-grid-baseline) 0 0;
}
</style>
