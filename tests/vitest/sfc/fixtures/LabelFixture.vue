<!--
	SPDX-FileCopyrightText: 2026 Conduction B.V.
	SPDX-License-Identifier: EUPL-1.2
-->
<script>
import { NcButton } from '@nextcloud/vue'

export default {
	name: 'LabelFixture',
	components: { NcButton },
	props: {
		text: { type: String, default: '' },
	},
}
</script>

<template>
	<NcButton v-tooltip="text" variant="tertiary">
		<b>{{ text }}</b>
	</NcButton>
</template>
