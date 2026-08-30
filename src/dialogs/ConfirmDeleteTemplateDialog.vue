<template>
	<NcDialog :name="t('filinq', 'Delete template')" @closing="$emit('cancel')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Are you sure you want to delete "{name}"? This action cannot be undone.',
						{ name: templateName },
					)
				}}
			</p>
		</template>
		<template #actions>
			<NcButton @click="$emit('cancel')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton variant="error" @click="$emit('confirm')">
				{{ t('filinq', 'Delete') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

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
