<!--
	SPDX-FileCopyrightText: 2026 Conduction B.V.
	SPDX-License-Identifier: EUPL-1.2
-->
<script setup>
import { translate as t } from '@nextcloud/l10n'
</script>

<script>
import { CnIndexPage } from '@conduction/nextcloud-vue'
import LabelFixture from './LabelFixture.vue'

// The fixture pairs `<script setup>` with an options export, as the app's
// components do.
// eslint-disable-next-line vue/prefer-define-options
export default {
	name: 'PanelFixture',
	components: { CnIndexPage, LabelFixture },
	data() {
		return { rows: ['a'] }
	},
}
</script>

<template>
	<CnIndexPage :objects="rows" :title="t('filinq', 'Panel')">
		<template #row-actions="{ row }">
			<LabelFixture :text="row" />
		</template>
	</CnIndexPage>
</template>
