<template>
	<NcDialog :name="t('docudesk', 'Restore version')"
		@closing="$emit('cancel')">
		<template #default>
			<p>
				{{ t('docudesk', 'Restore to version {n}? The current state will be saved as a new version first.', { n: versionNumber }) }}
			</p>
		</template>
		<template #actions>
			<NcButton @click="$emit('cancel')">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" @click="$emit('confirm')">
				{{ t('docudesk', 'Restore') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog } from '@conduction/nextcloud-vue'

export default {
	name: 'ConfirmRestoreVersionDialog',
	components: { NcButton, NcDialog },
	props: {
		versionNumber: {
			type: Number,
			default: 0,
		},
	},
	emits: ['confirm', 'cancel'],
	methods: { t },
}
</script>
