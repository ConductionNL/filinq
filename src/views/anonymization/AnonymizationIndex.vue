<template>
	<div class="anonymization-container">
		<NcAppContent>
			<div class="view-header">
				<h1>{{ t('docudesk', 'Anonymization') }}</h1>
				<p>{{ t('docudesk', 'Manage document anonymization and PII detection') }}</p>
			</div>

			<div class="content-area">
				<NcEmptyContent
					:name="t('docudesk', 'Anonymization Tools')"
					:description="t('docudesk', 'Use the anonymization API endpoints to anonymize documents')">
					<template #icon>
						<ShieldLockOutline :size="64" />
					</template>
				</NcEmptyContent>
			</div>
		</NcAppContent>
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { NcAppContent, NcEmptyContent } from '@nextcloud/vue'
import ShieldLockOutline from 'vue-material-design-icons/ShieldLockOutline.vue'

export default {
	name: 'AnonymizationIndex',
	components: {
		NcAppContent,
		NcEmptyContent,
		ShieldLockOutline,
	},
	methods: {
		t,
	},
}
</script>

<style scoped>
.anonymization-container {
	padding: 20px;
}

.view-header {
	margin-bottom: 20px;
}

.view-header h1 {
	margin: 0 0 8px 0;
	font-size: 28px;
	font-weight: 600;
}

.view-header p {
	color: var(--color-text-maxcontrast);
	margin: 0;
}

.content-area {
	margin-top: 20px;
}
</style>


