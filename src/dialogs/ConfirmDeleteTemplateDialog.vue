<template>
	<NcDialog :name="t('docudesk', 'Delete template')" @closing="$emit('cancel')">
		<template #default>
			<p>
				{{
					t(
						'docudesk',
						'Are you sure you want to delete "{name}"? This action cannot be undone.',
						{ name: templateName },
					)
				}}
			</p>
		</template>
		<template #actions>
			<NcButton @click="$emit('cancel')">
				{{ t('docudesk', 'Cancel') }}
			</NcButton>
			<NcButton variant="error" @click="$emit('confirm')">
				{{ t('docudesk', 'Delete') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcDialog } from '@conduction/nextcloud-vue'

export default {
	name: 'ConfirmDeleteTemplateDialog',
	components: { NcButton, NcDialog },
	props: {
		templateName: {
			type: String,
			default: '',
		},
	},
	emits: ['confirm', 'cancel'],
	methods: { t },
}
</script>
