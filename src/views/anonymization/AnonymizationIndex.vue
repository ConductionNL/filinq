<template>
	<div class="anonymization-page">
		<!--
			Page-level heading. `/anonymization` is a manifest `type:"custom"`
			page, so — unlike the sibling `type:"index"` pages (/templates,
			/signing) whose heading the shell renders — nothing supplied one
			here and the page shipped with no h1/h2 at all. That is a WCAG 2.1
			AA gap (a page must be identifiable by its heading, and screen
			readers rely on it for orientation) and it made the page
			inconsistent with every other surface. Caught by the e2e routing
			regression check on 2026-07-24.
		-->
		<h2 class="anonymization-page__title">
			{{ t('docudesk', 'Anonymization') }}
		</h2>
		<AnonymizationWidget />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import AnonymizationWidget from './AnonymizationWidget.vue'

export default {
	name: 'AnonymizationIndex',
	components: {
		AnonymizationWidget,
	},

	methods: {
		t,
	},
}
</script>

<style scoped>
.anonymization-page {
	padding: 20px;
}

.anonymization-page__title {
	margin-block-end: 12px;
}
</style>
