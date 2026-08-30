<template>
	<NcDialog :name="t('filinq', 'Restore version')" @closing="$emit('cancel')">
		<template #default>
			<p>
				{{
					t(
						'filinq',
						'Restore to version {n}? The current state will be saved as a new version first.',
						{ n: versionNumber },
					)
				}}
			</p>
		</template>
		<template #actions>
			<NcButton @click="$emit('cancel')">
				{{ t('filinq', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" @click="$emit('confirm')">
				{{ t('filinq', 'Restore') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script>
import { NcButton, NcDialog } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'

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
