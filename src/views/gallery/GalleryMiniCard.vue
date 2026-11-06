<!--
  SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
  SPDX-License-Identifier: EUPL-1.2

  A small card for one demo document in the component gallery.
-->
<template>
	<div class="dd-mini-card">
		<DdIcon :name="item.isFolder ? 'folder' : 'article'" :size="24" />
		<span class="dd-mini-card__name">{{ item.fileName }}</span>
	</div>
</template>

<script>
import DdIcon from '../../components/DdIcon.vue'

export default {
	name: 'GalleryMiniCard',
	components: { DdIcon },
	props: {
		/** The demo document. */
		item: { type: Object, required: true },
	},
}
</script>

<style scoped>
.dd-mini-card {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 16px;
}

.dd-mini-card__name {
	font-weight: 500;
}
</style>
