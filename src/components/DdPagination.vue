<template>
	<div v-if="totalPages > 1 || totalItems > minItemsToShow" class="dd-pagination">
		<div class="dd-pagination__info">
			{{ pageInfoText }}
		</div>

		<div v-if="totalPages > 1" class="dd-pagination__nav">
			<NcButton :disabled="currentPage === 1" @click="changePage(1)">
				{{ firstLabel }}
			</NcButton>
			<NcButton
				:disabled="currentPage === 1"
				@click="changePage(currentPage - 1)">
				{{ previousLabel }}
			</NcButton>

			<div class="dd-pagination__numbers">
				<template v-for="(page, idx) in visiblePages">
					<span
						v-if="page === '...'"
						:key="'el-' + idx"
						class="dd-pagination__ellipsis"
						>...</span
					>
					<NcButton
						v-else
						:key="page"
						:variant="page === currentPage ? 'primary' : 'secondary'"
						:disabled="page === currentPage"
						@click="changePage(page)">
						{{ page }}
					</NcButton>
				</template>
			</div>

			<NcButton
				:disabled="currentPage === totalPages"
				@click="changePage(currentPage + 1)">
				{{ nextLabel }}
			</NcButton>
			<NcButton
				:disabled="currentPage === totalPages"
				@click="changePage(totalPages)">
				{{ lastLabel }}
			</NcButton>
		</div>

		<div class="dd-pagination__page-size">
			<label :for="pageSizeId">{{ itemsPerPageLabel }}</label>
			<NcSelect
				:inputId="pageSizeId"
				class="dd-pagination__page-size-select"
				:modelValue="currentPageSizeOption"
				:options="pageSizeOptions"
				:clearable="false"
				:inputLabel="itemsPerPageLabel"
				@option:selected="changePageSize" />
		</div>
	</div>
</template>

<script>
import { NcButton, NcSelect } from '@nextcloud/vue'

/**
 * Pagination control for Filinq index pages.
 *
 * Renders First/Previous/numbers/Next/Last navigation plus a page-size
 * selector. Hidden entirely when there is only one page and the total
 * item count is below `minItemsToShow`.
 */
export default {
	name: 'DdPagination',
	components: { NcButton, NcSelect },
	props: {
		/** Active page number (1-based). */
		currentPage: { type: Number, default: 1 },
		/** Total number of pages. */
		totalPages: { type: Number, default: 1 },
		/** Total number of items across all pages. */
		totalItems: { type: Number, default: 0 },
		/** Items per page currently in effect. */
		currentPageSize: { type: Number, default: 20 },
		/** Available page size options as `{ value, label }` pairs. */
		pageSizeOptions: {
			type: Array,
			default: () => [
				{ value: 10, label: '10' },
				{ value: 20, label: '20' },
				{ value: 50, label: '50' },
				{ value: 100, label: '100' },
			],
		},

		/** Hide the control entirely below this item count when on a single page. */
		minItemsToShow: { type: Number, default: 10 },
		firstLabel: { type: String, default: 'First' },
		previousLabel: { type: String, default: 'Previous' },
		nextLabel: { type: String, default: 'Next' },
		lastLabel: { type: String, default: 'Last' },
		itemsPerPageLabel: { type: String, default: 'Items per page:' },
		/** `{current}` and `{total}` placeholders are interpolated. */
		pageInfoFormat: { type: String, default: 'Page {current} of {total}' },
	},

	computed: {
		pageSizeId() {
			return 'dd-page-size-' + this._uid
		},

		currentPageSizeOption() {
			return (
				this.pageSizeOptions.find((o) => o.value === this.currentPageSize)
				|| this.pageSizeOptions[0]
			)
		},

		pageInfoText() {
			return this.pageInfoFormat
				.replace('{current}', this.currentPage)
				.replace('{total}', this.totalPages)
		},

		/**
		 * Compact page-number list with leading/trailing ellipses for large
		 * page counts. Always includes the first and last page when truncated.
		 */
		visiblePages() {
			const current = this.currentPage
			const total = this.totalPages
			const pages = []
			if (total <= 7) {
				for (let i = 1; i <= total; i++) pages.push(i)
				return pages
			}
			pages.push(1)
			if (current <= 4) {
				for (let i = 2; i <= 5; i++) pages.push(i)
				pages.push('...')
				pages.push(total)
			} else if (current >= total - 3) {
				pages.push('...')
				for (let i = total - 4; i <= total; i++) pages.push(i)
			} else {
				pages.push('...')
				for (let i = current - 1; i <= current + 1; i++) pages.push(i)
				pages.push('...')
				pages.push(total)
			}
			return pages
		},
	},

	methods: {
		/**
		 * Emit a page change if it differs from the current page and is in range.
		 *
		 * @param {number} page Target page number.
		 */
		changePage(page) {
			if (page !== this.currentPage && page >= 1 && page <= this.totalPages) {
				this.$emit('page-changed', page)
			}
		},

		/**
		 * Emit a page-size change.
		 *
		 * @param {{ value: number, label: string }} option Selected option.
		 */
		changePageSize(option) {
			if (option && option.value !== this.currentPageSize) {
				this.$emit('page-size-changed', option.value)
			}
		},
	},
}
</script>

<style scoped>
.dd-pagination {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: calc(4 * var(--default-grid-baseline));
	padding: calc(5 * var(--default-grid-baseline));
	flex-wrap: nowrap;
}

.dd-pagination__info {
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
	flex-shrink: 0;
}

.dd-pagination__nav {
	display: flex;
	align-items: center;
	gap: calc(2 * var(--default-grid-baseline));
	flex-grow: 1;
	justify-content: center;
}

.dd-pagination__numbers {
	display: flex;
	align-items: center;
	gap: var(--default-grid-baseline);
}

.dd-pagination__ellipsis {
	padding: 0 var(--default-grid-baseline);
	color: var(--color-text-maxcontrast);
	font-size: 0.9rem;
}

.dd-pagination__page-size {
	display: flex;
	align-items: center;
	gap: calc(2 * var(--default-grid-baseline));
	flex-shrink: 0;
}

.dd-pagination__page-size label {
	font-size: 0.9rem;
	color: var(--color-text-maxcontrast);
	white-space: nowrap;
}

.dd-pagination__page-size-select {
	min-width: 100px !important;
	max-width: 120px !important;
}
</style>
