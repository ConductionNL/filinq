<template>
	<div class="dd-card-grid">
		<div v-if="loading" class="dd-card-grid__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<div v-else-if="objects.length === 0" class="dd-card-grid__empty">
			{{ emptyText }}
		</div>

		<div v-else class="dd-card-grid__grid">
			<div
				v-for="object in objects"
				:key="object[rowKey]"
				class="dd-card-grid__cell"
				@click="$emit('row-click', object)">
				<slot name="card" :object="object">
					{{ object[rowKey] }}
				</slot>
			</div>
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'

/**
 * Responsive grid for tile/card view in DocuDesk index pages.
 *
 * The card content is fully consumer-defined via the `card` scoped slot.
 * Whole-cell click triggers `row-click` with the object.
 */
export default {
	name: 'DdCardGrid',
	components: { NcLoadingIcon },
	props: {
		/** Objects to render as cards. */
		objects: {
			type: Array,
			default: () => [],
		},
		/** Show a loading spinner instead of the grid. */
		loading: {
			type: Boolean,
			default: false,
		},
		/** Property name used as unique identifier (and Vue list key). */
		rowKey: {
			type: String,
			default: 'id',
		},
		/** Text shown when objects is empty and not loading. */
		emptyText: {
			type: String,
			default: 'No items found',
		},
	},
}
</script>

<style scoped>
.dd-card-grid__grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
	grid-auto-rows: 1fr;
	gap: 16px;
}

.dd-card-grid__cell {
	cursor: pointer;
	display: flex;
}

.dd-card-grid__cell > * {
	flex: 1;
}

.dd-card-grid__loading {
	display: flex;
	justify-content: center;
	padding: 40px;
}

.dd-card-grid__empty {
	padding: 40px 20px;
	text-align: center;
	color: var(--color-text-maxcontrast);
}
</style>
