<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<NcSettingsSection
		:name="t('filinq', 'Apps that request signatures')"
		:description="
			t(
				'filinq',
				'Other apps ask filinq for signatures through one shared rail. These apps have raised signing requests here.',
			)
		">
		<NcLoadingIcon v-if="loading" :size="32" />
		<p v-else-if="failed" class="signing-sources__error" role="alert">
			{{ t('filinq', 'The signing requests could not be counted.') }}
		</p>
		<p v-else-if="sources.length === 0" class="signing-sources__empty">
			{{ t('filinq', 'No app has raised a signing request yet.') }}
		</p>
		<table v-else class="signing-sources__table">
			<thead>
				<tr>
					<th scope="col">
						{{ t('filinq', 'App') }}
					</th>
					<th scope="col" class="signing-sources__count">
						{{ t('filinq', 'Signing requests') }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="source in sources" :key="source.app">
					<td>{{ source.app }}</td>
					<td class="signing-sources__count">
						{{ source.count }}
					</td>
				</tr>
			</tbody>
		</table>
		<p v-if="truncated" class="signing-sources__hint">
			{{ t('filinq', 'Only the first requests were counted; there are more.') }}
		</p>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { NcLoadingIcon, NcSettingsSection } from '@nextcloud/vue'

/**
 * The admin list of the apps that use the signing rail, with their request
 * counts (one-signing-rail-for-the-fleet REQ-DDSRC-004). No request is an
 * empty case, said as such; only a failed load is shown as an error.
 *
 * @spec openspec/changes/one-signing-rail-for-the-fleet/specs/signing-rail-consolidation/spec.md#requirement-an-admin-can-see-which-apps-use-the-rail-req-ddsrc-004
 */
export default {
	name: 'SigningSourcesSettings',
	components: { NcLoadingIcon, NcSettingsSection },
	data() {
		return { loading: true, failed: false, sources: [], truncated: false }
	},

	async mounted() {
		await this.load()
	},

	methods: {
		t,
		/**
		 * Read the counts from the admin endpoint.
		 *
		 * @return {Promise<void>}
		 */
		async load() {
			this.loading = true
			this.failed = false
			try {
				const { data } = await axios.get(
					generateUrl('/apps/filinq/api/settings/signing-sources'),
				)
				this.sources = Array.isArray(data?.sources) ? data.sources : []
				this.truncated = data?.truncated === true
			} catch {
				this.failed = true
				this.sources = []
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style scoped>
.signing-sources__table {
	border-collapse: collapse;
	min-width: 320px;
}

.signing-sources__table th,
.signing-sources__table td {
	padding: 6px 12px 6px 0;
	text-align: start;
	border-bottom: 1px solid var(--color-border);
}

.signing-sources__count {
	text-align: end;
}

.signing-sources__empty,
.signing-sources__hint {
	color: var(--color-text-maxcontrast);
}

.signing-sources__error {
	color: var(--color-error-text);
}
</style>
