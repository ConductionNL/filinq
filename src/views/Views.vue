<template>
	<NcAppContent>
		<router-view />
	</NcAppContent>
</template>

<script>
import { NcAppContent } from '@nextcloud/vue'

export default {
	name: 'Views',
	components: {
		NcAppContent,
	},
}
</script>
