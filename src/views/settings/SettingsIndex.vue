<script setup>
import { translate as t } from '@nextcloud/l10n'
import DdPageHeader from '../../components/DdPageHeader.vue'
</script>

<template>
	<div class="settings-wrapper">
		<DdPageHeader :title="t('docudesk', 'Settings')" />
	</div>
</template>

<script>
export default {
	name: 'SettingsIndex',
}
</script>

<style scoped>
.settings-wrapper {
	padding: 16px;
}
</style>
