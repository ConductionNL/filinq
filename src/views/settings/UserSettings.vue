<!-- SPDX-License-Identifier: EUPL-1.2 -->
<template>
	<NcAppSettingsDialog
		:open="open"
		:show-navigation="false"
		:name="t('docudesk', 'DocuDesk settings')"
		@update:open="$emit('update:open', $event)">
		<NcAppSettingsSection id="general" :name="t('docudesk', 'General')">
			<template #icon>
				<CogIcon :size="20" />
			</template>
			<p>{{ t('docudesk', 'User preferences will appear here.') }}</p>
		</NcAppSettingsSection>
	</NcAppSettingsDialog>
</template>
<script>
import { NcAppSettingsDialog, NcAppSettingsSection } from '@nextcloud/vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
export default {
	name: 'UserSettings',
	components: { NcAppSettingsDialog, NcAppSettingsSection, CogIcon },
	props: { open: { type: Boolean, default: false } },
}
</script>
