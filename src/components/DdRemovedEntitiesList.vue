<script setup>
import DdEntityCard from './DdEntityCard.vue'
</script>

<template>
	<div class="entities-list">
		<DdEntityCard
			v-for="(item, idx) in items"
			:key="'removed-' + idx"
			:item="item"
			mode="anonymized" />
	</div>
</template>

<script>
/**
 * Read-only list of entities removed from an anonymised document.
 *
 * Renders one `DdEntityCard` (mode `anonymized`) per item; each card shows the
 * original value stacked above the anonymised placeholder it was replaced with.
 * Used in two places that previously each duplicated this markup: the
 * post-anonymise download step and the re-opened anonymised-document view in
 * `FileViewerSidebar`. The unique-values / occurrences summary lives in the
 * sidebar header title (see `FileViewerSidebar.sidebarTitle`).
 */
export default {
	name: 'DdRemovedEntitiesList',
	components: {
		DdEntityCard,
	},
	props: {
		/**
		 * Anonymised-card rows to render. Each item follows the
		 * `mode="anonymized"` shape: { type, value, placeholder, count, bases,
		 * _resolveError }.
		 */
		items: {
			type: Array,
			default: () => [],
		},
	},
}
</script>

<style lang="scss" scoped>
.entities-list {
	display: flex;
	flex-direction: column;
}
</style>
