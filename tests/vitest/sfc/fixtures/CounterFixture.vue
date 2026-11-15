<!--
	SPDX-FileCopyrightText: 2026 Conduction B.V.
	SPDX-License-Identifier: EUPL-1.2
-->
<script setup>
import { translate as t } from '@nextcloud/l10n'
</script>

<script>
import { NcButton } from '@nextcloud/vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import counterMixin from './counterMixin.js'

// The fixture pairs `<script setup>` with an options export, as the app's
// components do.
// eslint-disable-next-line vue/prefer-define-options
export default {
	name: 'CounterFixture',
	components: { NcButton, Plus },
	mixins: [counterMixin],
	props: {
		step: { type: Number, default: 1 },
		tags: { type: Array, default: () => ['a'] },
	},

	emits: ['changed'],

	data() {
		return { source: 'component', log: [] }
	},

	computed: {
		title() {
			return t('filinq', 'Count {n}', { n: this.count })
		},
	},

	watch: {
		count(now, before) {
			this.$emit('changed', now, before)
		},
	},

	mounted() {
		this.log.push('component mounted')
	},

	methods: {
		label() {
			return 'component'
		},

		open() {
			this.$router.push({ name: 'Detail' })
		},
	},
}
</script>

<template>
	<NcButton @click="increment">
		<template #icon>
			<Plus :size="20" />
		</template>
		{{ title }}
	</NcButton>
</template>
