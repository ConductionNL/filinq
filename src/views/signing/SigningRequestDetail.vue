<template>
	<div class="signing-request-detail">
		<NcLoadingIcon v-if="signingStore.loading && !signingStore.signingRequest" :size="44" />
		<template v-else-if="signingStore.signingRequest">
			<h2>{{ signingStore.signingRequest.documentName }}</h2>
			<div class="detail-grid">
				<div><strong>{{ t('docudesk', 'Status') }}</strong>: {{ signingStore.signingRequest.status }}</div>
				<div><strong>{{ t('docudesk', 'Level') }}</strong>: {{ signingStore.signingRequest.signatureLevel }}</div>
				<div><strong>{{ t('docudesk', 'Mode') }}</strong>: {{ signingStore.signingRequest.signingMode }}</div>
				<div><strong>{{ t('docudesk', 'Provider') }}</strong>: {{ signingStore.signingRequest.provider }}</div>
			</div>
			<h3>{{ t('docudesk', 'Audit Trail') }}</h3>
			<table v-if="signingStore.auditTrail.length > 0" class="audit-table">
				<thead>
					<tr>
						<th>{{ t('docudesk', 'Action') }}</th>
						<th>{{ t('docudesk', 'Actor') }}</th>
						<th>{{ t('docudesk', 'Timestamp') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="entry in signingStore.auditTrail" :key="entry.id || entry.uuid">
						<td>{{ entry.action }}</td>
						<td>{{ entry.actorDisplayName }}</td>
						<td>{{ entry.timestamp ? new Date(entry.timestamp).toLocaleString() : '-' }}</td>
					</tr>
				</tbody>
			</table>
			<p v-else>
				{{ t('docudesk', 'No audit entries yet.') }}
			</p>
		</template>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { useSigningStore } from '../../store/modules/signing.js'

export default {
	name: 'SigningRequestDetail',
	components: { NcLoadingIcon },
	props: { requestId: { type: String, required: true } },
	setup(props) {
		const signingStore = useSigningStore()
		signingStore.fetchSigningRequest(props.requestId)
		signingStore.fetchAuditTrail(props.requestId)
		return { signingStore, t }
	},
}
</script>

<style scoped>
.signing-request-detail {
	padding: 20px;
}

.detail-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 12px;
	margin-bottom: 20px;
}

.audit-table {
	width: 100%;
	border-collapse: collapse;
}

.audit-table th,
.audit-table td {
	padding: 10px;
	text-align: left;
	border-bottom: 1px solid var(--color-border);
}
</style>
