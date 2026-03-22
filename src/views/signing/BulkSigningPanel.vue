<template>
	<div class="bulk-signing-panel">
		<h2>{{ t('docudesk', 'Bulk Signing') }}</h2>
		<NcLoadingIcon v-if="signingStore.loading" :size="44" />
		<NcEmptyContent v-else-if="pending.length === 0"
			:name="t('docudesk', 'No pending signing requests')" />
		<template v-else>
			<NcButton type="primary" :disabled="selected.length === 0" @click="bulkSign">
				{{ t('docudesk', 'Sign Selected') }} ({{ selected.length }})
			</NcButton>
			<table class="bulk-table">
				<thead>
					<tr>
						<th />
						<th>{{ t('docudesk', 'Document') }}</th>
						<th>{{ t('docudesk', 'Level') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="req in pending" :key="req.id || req.uuid" @click="toggle(req.id || req.uuid)">
						<td><input type="checkbox" :checked="selected.includes(req.id || req.uuid)"></td>
						<td>{{ req.documentName }}</td>
						<td>{{ req.signatureLevel }}</td>
					</tr>
				</tbody>
			</table>
		</template>
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon, NcEmptyContent } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { useSigningStore } from '../../store/modules/signing.js'
import { showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'BulkSigningPanel',
	components: { NcButton, NcLoadingIcon, NcEmptyContent },
	data() { return { selected: [] } },
	computed: {
		signingStore() { return useSigningStore() },
		pending() { return this.signingStore.signingRequests.filter((r) => ['PENDING', 'IN_PROGRESS'].includes(r.status)) },
	},
	mounted() { this.signingStore.fetchSigningRequests() },
	methods: {
		t,
		toggle(id) {
			const idx = this.selected.indexOf(id)
			if (idx === -1) { this.selected.push(id) } else { this.selected.splice(idx, 1) }
		},
		async bulkSign() {
			await this.signingStore.bulkSign(this.selected)
			showSuccess(t('docudesk', 'Bulk signing completed'))
			this.selected = []
		},
	},
}
</script>

<style scoped>
.bulk-signing-panel { padding: 20px; }
.bulk-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
.bulk-table th, .bulk-table td { padding: 10px; text-align: left; border-bottom: 1px solid var(--color-border); }
.bulk-table tr { cursor: pointer; }
.bulk-table tr:hover { background: var(--color-background-hover); }
</style>
