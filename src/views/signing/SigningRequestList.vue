<template>
	<div class="signing-request-list">
		<div class="signing-header">
			<h2>{{ t('docudesk', 'Signing Requests') }}</h2>
		</div>
		<NcLoadingIcon v-if="signingStore.loading" :size="44" />
		<NcEmptyContent v-else-if="signingStore.signingRequests.length === 0"
			:name="t('docudesk', 'No signing requests')" />
		<table v-else class="signing-table">
			<thead>
				<tr>
					<th>{{ t('docudesk', 'Document') }}</th>
					<th>{{ t('docudesk', 'Status') }}</th>
					<th>{{ t('docudesk', 'Level') }}</th>
					<th>{{ t('docudesk', 'Mode') }}</th>
					<th>{{ t('docudesk', 'Deadline') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="request in signingStore.signingRequests"
					:key="request.id || request.uuid">
					<td>{{ request.documentName }}</td>
					<td>
						<span class="status-badge">{{ request.status }}</span>
					</td>
					<td>{{ request.signatureLevel }}</td>
					<td>{{ request.signingMode }}</td>
					<td>{{ request.deadline ? new Date(request.deadline).toLocaleDateString() : '-' }}</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import { NcLoadingIcon, NcEmptyContent } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningRequestList',
	components: { NcLoadingIcon, NcEmptyContent },
	setup() {
		const signingStore = useSigningStore()
		signingStore.fetchSigningRequests()
		return { signingStore, t }
	},
}
</script>

<style scoped>
.signing-request-list {
	padding: 20px;
}

.signing-header {
	display: flex;
	justify-content: space-between;
	margin-bottom: 20px;
}

.signing-table {
	width: 100%;
	border-collapse: collapse;
}

.signing-table th,
.signing-table td {
	padding: 12px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}

.status-badge {
	padding: 4px 8px;
	border-radius: var(--border-radius);
	font-size: 0.85em;
	font-weight: bold;
}
</style>
