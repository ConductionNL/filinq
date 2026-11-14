<!--
  - SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  - SPDX-License-Identifier: EUPL-1.2
-->
<template>
	<div class="woo-grounds-source" data-testid="woo-grounds-source">
		<NcNoteCard v-if="summary === null && !loading" type="warning">
			{{ t('filinq', 'The Woo refusal grounds cannot be read.') }}
		</NcNoteCard>
		<template v-else-if="summary !== null">
			<NcNoteCard v-if="summary.source === 'dossiq'" type="info">
				{{ t('filinq', 'The Woo refusal grounds are maintained in dossiq. Filinq reads them and does not edit them.') }}
				<a :href="dossiqUrl" class="woo-grounds-source__link">
					{{ t('filinq', 'Open the Woo refusal grounds in dossiq') }}
				</a>
			</NcNoteCard>
			<NcNoteCard v-else type="info">
				{{ t('filinq', 'Read-only list, version {version}. Install dossiq to maintain the Woo refusal grounds.', { version: summary.version }) }}
			</NcNoteCard>
			<ul class="woo-grounds-source__list">
				<li v-for="ground in summary.grounds" :key="ground.code">
					<strong>{{ ground.code }}</strong> {{ ground.label }}
				</li>
			</ul>
		</template>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcNoteCard } from '@nextcloud/vue'
import { dossiqGroundsUrl, fetchGroundsSource } from '../../services/groundsSource.js'

// Read-only: the Woo refusal grounds come from dossiq or its snapshot, so
// this section shows where they come from and offers no editor
// (grondslagen-read-from-dossiq REQ-GRD-004).
export default {
	name: 'WooGroundsSource',
	components: {
		NcNoteCard,
	},

	data() {
		return {
			loading: true,
			summary: null,
		}
	},

	computed: {
		dossiqUrl() {
			return dossiqGroundsUrl()
		},
	},

	async mounted() {
		this.summary = await fetchGroundsSource()
		this.loading = false
	},

	methods: {
		t,
	},
}
</script>

<style scoped>
.woo-grounds-source__list {
	margin-top: 8px;
	color: var(--color-main-text);
}

.woo-grounds-source__link {
	display: block;
	margin-top: 4px;
	color: var(--color-primary-element);
}
</style>
