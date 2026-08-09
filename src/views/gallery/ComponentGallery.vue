<template>
	<div class="dd-gallery">
		<header class="dd-gallery__masthead">
			<h1 class="dd-gallery__title">
				DocuDesk component gallery
			</h1>
			<p class="dd-gallery__lead">
				Living overview of the reusable <code>Dd*</code> design-system
				components, each shown with its main variants and states.
				Built with the app's own Vue 2 stack — no extra tooling.
			</p>
			<nav class="dd-gallery__toc" aria-label="Components">
				<a
					v-for="entry in toc"
					:key="entry.id"
					class="dd-gallery__toc-link"
					:href="`#${entry.id}`">
					{{ entry.label }}
				</a>
			</nav>
		</header>

		<!-- DdPageHeader -->
		<section id="dd-page-header" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdPageHeader
				</h2>
				<p class="dd-section__desc">
					Page-level title with optional icon, description and an actions slot.
				</p>
			</div>
			<div class="dd-demo dd-demo--stack">
				<DdPageHeader :title="t('docudesk', 'Documents')" />
				<DdPageHeader
					:title="t('docudesk', 'Anonymization')"
					icon="lock"
					description="Review detected entities before removing them." />
				<DdPageHeader :title="t('docudesk', 'Templates')" icon="article" description="With a right-aligned action.">
					<template #actions>
						<DdButton variant="primary" icon="add" :label="t('docudesk', 'New template')" />
					</template>
				</DdPageHeader>
			</div>
		</section>

		<!-- DdButton -->
		<section id="dd-button" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdButton
				</h2>
				<p class="dd-section__desc">
					Pill button in three variants. Optional leading icon; native attributes forwarded.
				</p>
			</div>
			<div class="dd-demo">
				<div class="dd-row">
					<DdButton variant="primary" label="Primary" />
					<DdButton variant="secondary" label="Secondary" />
					<DdButton variant="tertiary" label="Tertiary" />
				</div>
				<div class="dd-row">
					<DdButton variant="primary" icon="add" label="With icon" />
					<DdButton variant="secondary" icon="download" label="Download" />
					<DdButton variant="tertiary" icon="delete" label="Delete" />
				</div>
				<div class="dd-row">
					<DdButton variant="primary" label="Disabled" disabled />
					<DdButton variant="secondary" label="Disabled" disabled />
					<DdButton variant="tertiary" label="Disabled" disabled />
				</div>
			</div>
		</section>

		<!-- DdIcon -->
		<section id="dd-icon" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdIcon
				</h2>
				<p class="dd-section__desc">
					SVG icons resolved by filename from <code>src/assets/icons/</code>, recolored via <code>currentColor</code>.
				</p>
			</div>
			<div class="dd-demo">
				<div class="dd-icon-grid">
					<figure v-for="name in iconNames" :key="name" class="dd-icon-cell">
						<DdIcon :name="name" :size="28" />
						<figcaption>{{ name }}</figcaption>
					</figure>
				</div>
			</div>
		</section>

		<!-- DdViewToggle -->
		<section id="dd-view-toggle" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdViewToggle
				</h2>
				<p class="dd-section__desc">
					Segmented tiles/list switch (v-model). Current value: <strong>{{ viewToggle }}</strong>.
				</p>
			</div>
			<div class="dd-demo">
				<DdViewToggle v-model="viewToggle" />
			</div>
		</section>

		<!-- DdSearchBar -->
		<section id="dd-search-bar" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdSearchBar
				</h2>
				<p class="dd-section__desc">
					Debounced search input with clear button. Emitted value: <strong>{{ searchValue || '—' }}</strong>.
				</p>
			</div>
			<div class="dd-demo">
				<DdSearchBar v-model="searchValue" placeholder="Search documents…" />
			</div>
		</section>

		<!-- DdSkeleton -->
		<section id="dd-skeleton" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdSkeleton
				</h2>
				<p class="dd-section__desc">
					Loading placeholders in three shapes.
				</p>
			</div>
			<div class="dd-demo dd-demo--stack">
				<div>
					<span class="dd-demo__label">variant="text" :rows="3"</span>
					<DdSkeleton variant="text" :rows="3" />
				</div>
				<div>
					<span class="dd-demo__label">variant="row" :height="48"</span>
					<DdSkeleton variant="row" :height="48" />
				</div>
				<div>
					<span class="dd-demo__label">variant="circle" :width="48"</span>
					<DdSkeleton variant="circle" :width="48" />
				</div>
			</div>
		</section>

		<!-- DdPagination -->
		<section id="dd-pagination" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdPagination
				</h2>
				<p class="dd-section__desc">
					First/prev/numbers/next/last with a page-size selector. Live state below.
				</p>
			</div>
			<div class="dd-demo">
				<DdPagination
					:current-page="pager.page"
					:total-pages="pager.pages"
					:total-items="pager.total"
					:current-page-size="pager.limit"
					@page-changed="onPageChanged"
					@page-size-changed="onPageSizeChanged" />
			</div>
		</section>

		<!-- DdCardGrid -->
		<section id="dd-card-grid" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdCardGrid
				</h2>
				<p class="dd-section__desc">
					Responsive tile grid; card content via the <code>card</code> scoped slot.
					The grid is layout only — a card that should be activatable is
					an interactive element in the slot, so keyboard users reach it.
				</p>
			</div>
			<div class="dd-demo">
				<DdCardGrid :objects="documents" row-key="id">
					<template #card="{ object }">
						<div class="dd-mini-card">
							<DdIcon :name="object.isFolder ? 'folder' : 'article'" :size="24" />
							<span class="dd-mini-card__name">{{ object.fileName }}</span>
						</div>
					</template>
				</DdCardGrid>
			</div>
		</section>

		<!-- DdDocumentCard -->
		<section id="dd-document-card" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdDocumentCard
				</h2>
				<p class="dd-section__desc">
					Document tile with asset icon, name, date and a kind pill (dossier / concept / anonymized).
				</p>
			</div>
			<div class="dd-demo">
				<div class="dd-row dd-row--wrap">
					<DdDocumentCard :item="documents[0]" @click="noop" />
					<DdDocumentCard :item="documents[1]" @click="noop" />
					<DdDocumentCard :item="documents[2]" @click="noop" />
					<DdDocumentCard
						:item="documents[1]"
						selectable
						:selected="true"
						@click="noop"
						@toggle-select="noop" />
				</div>
			</div>
		</section>

		<!-- DdDataTable -->
		<section id="dd-data-table" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdDataTable
				</h2>
				<p class="dd-section__desc">
					Manual-column table with selection, custom cell slots and a row-actions slot.
				</p>
			</div>
			<div class="dd-demo">
				<DdDataTable
					:columns="tableColumns"
					:rows="documents"
					row-key="id"
					selectable
					:selected-keys="selectedKeys"
					@update:selected-keys="onSelect"
					@row-click="noop">
					<template #column-kind="{ row }">
						<span class="dd-tag">{{ row.isFolder ? 'Dossier' : (row.isAnonymized ? 'Anonymized' : 'Concept') }}</span>
					</template>
					<template #row-actions>
						<DdButton variant="tertiary" icon="discover_tune" label="" />
					</template>
				</DdDataTable>
			</div>
		</section>

		<!-- DdEntityCard -->
		<section id="dd-entity-card" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdEntityCard
				</h2>
				<p class="dd-section__desc">
					Detected-entity card: review (editable), anonymized (read-only) and loading states.
				</p>
			</div>
			<div class="dd-demo dd-demo--stack">
				<div>
					<span class="dd-demo__label">mode="review" :editable="true"</span>
					<DdEntityCard
						:item="reviewEntity"
						mode="review"
						:editable="true"
						:bases-options="basesOptions"
						@toggle="noop"
						@set-bases="noop" />
				</div>
				<div>
					<span class="dd-demo__label">mode="review" :editable="false"</span>
					<DdEntityCard
						:item="reviewEntity"
						mode="review"
						:editable="false"
						:bases-options="basesOptions" />
				</div>
				<div>
					<span class="dd-demo__label">mode="anonymized"</span>
					<DdEntityCard :item="anonymizedEntity" mode="anonymized" />
				</div>
				<div>
					<span class="dd-demo__label">:loading="true"</span>
					<DdEntityCard :loading="true" />
				</div>
			</div>
		</section>

		<!-- DdIndexPage -->
		<section id="dd-index-page" class="dd-section">
			<div class="dd-section__head">
				<h2 class="dd-section__title">
					DdIndexPage
				</h2>
				<p class="dd-section__desc">
					Composite index view: toolbar + table/cards toggle + pagination. Combines the components above.
				</p>
			</div>
			<div class="dd-demo">
				<DdIndexPage
					:columns="tableColumns"
					:objects="documents"
					:pagination="{ page: 1, pages: 3, total: 24, limit: 10 }"
					row-key="id"
					view-mode="table">
					<template #header-actions>
						<DdSearchBar v-model="indexSearch" placeholder="Filter…" />
					</template>
					<template #column-kind="{ row }">
						<span class="dd-tag">{{ row.isFolder ? 'Dossier' : (row.isAnonymized ? 'Anonymized' : 'Concept') }}</span>
					</template>
					<template #card="{ object }">
						<div class="dd-mini-card">
							<DdIcon :name="object.isFolder ? 'folder' : 'article'" :size="24" />
							<span class="dd-mini-card__name">{{ object.fileName }}</span>
						</div>
					</template>
				</DdIndexPage>
			</div>
		</section>
	</div>
</template>

<script>
import DdButton from '../../components/DdButton.vue'
import DdIcon from '../../components/DdIcon.vue'
import DdViewToggle from '../../components/DdViewToggle.vue'
import DdSearchBar from '../../components/DdSearchBar.vue'
import DdSkeleton from '../../components/DdSkeleton.vue'
import DdPagination from '../../components/DdPagination.vue'
import DdCardGrid from '../../components/DdCardGrid.vue'
import DdDataTable from '../../components/DdDataTable.vue'
import DdDocumentCard from '../../components/DdDocumentCard.vue'
import DdEntityCard from '../../components/DdEntityCard.vue'
import DdPageHeader from '../../components/DdPageHeader.vue'
import DdIndexPage from '../../components/DdIndexPage.vue'

/**
 * Component gallery for DocuDesk.
 *
 * A self-contained, Storybook-style showcase that renders every reusable
 * `Dd*` design-system component with its main variants and states, using
 * the app's own Vue 2 / Nextcloud stack. Pure presentation: it holds only
 * local demo state and never touches stores, services or the router.
 *
 * To view it, register a route pointing here (no existing file is modified
 * by this component itself), e.g.:
 *   { path: '/gallery', component: () => import('./views/gallery/ComponentGallery.vue') }
 */
export default {
	name: 'ComponentGallery',
	components: {
		DdButton,
		DdIcon,
		DdViewToggle,
		DdSearchBar,
		DdSkeleton,
		DdPagination,
		DdCardGrid,
		DdDataTable,
		DdDocumentCard,
		DdEntityCard,
		DdPageHeader,
		DdIndexPage,
	},
	data() {
		return {
			// Table of contents entries (id must match each section's id).
			toc: [
				{ id: 'dd-page-header', label: 'PageHeader' },
				{ id: 'dd-button', label: 'Button' },
				{ id: 'dd-icon', label: 'Icon' },
				{ id: 'dd-view-toggle', label: 'ViewToggle' },
				{ id: 'dd-search-bar', label: 'SearchBar' },
				{ id: 'dd-skeleton', label: 'Skeleton' },
				{ id: 'dd-pagination', label: 'Pagination' },
				{ id: 'dd-card-grid', label: 'CardGrid' },
				{ id: 'dd-document-card', label: 'DocumentCard' },
				{ id: 'dd-data-table', label: 'DataTable' },
				{ id: 'dd-entity-card', label: 'EntityCard' },
				{ id: 'dd-index-page', label: 'IndexPage' },
			],
			// All SVG icons currently in src/assets/icons (png assets excluded —
			// DdIcon only registers .svg files).
			iconNames: [
				'pdf', 'add', 'article', 'close', 'delete', 'discover_tune',
				'download', 'edit_square', 'error', 'folder',
				'information-circle-outline', 'list', 'lock', 'search',
				'tiles', 'visibility_off',
			],
			// Interactive demo state.
			viewToggle: 'tiles',
			searchValue: '',
			indexSearch: '',
			selectedKeys: [2],
			pager: { page: 2, pages: 5, total: 96, limit: 20 },
			// Mock documents for card/table demos.
			documents: [
				{ id: 1, fileName: 'Project dossier', isFolder: true, isAnonymized: false, modified: 1717500000 },
				{ id: 2, fileName: 'Contract-2026.docx', isFolder: false, isAnonymized: false, modified: 1717400000, mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
				{ id: 3, fileName: 'Verslag-anon.pdf', isFolder: false, isAnonymized: true, modified: 1717300000, mimeType: 'application/pdf' },
			],
			// Column defs for the table demos.
			tableColumns: [
				{ key: 'fileName', label: 'Name' },
				{ key: 'kind', label: 'Kind', width: '160px' },
			],
			// Entity-card demo data.
			basesOptions: [
				{ id: 'consent', label: 'Consent' },
				{ id: 'contract', label: 'Contract' },
				{ id: 'legal', label: 'Legal obligation' },
			],
			reviewEntity: {
				type: 'PERSON',
				value: 'Jane Doe',
				confidence: 0.97,
				included: true,
				_decisionBases: [],
				relationIds: ['rel-1'],
				_patchError: null,
			},
			anonymizedEntity: {
				type: 'PERSON',
				value: 'Jane Doe',
				placeholder: '[PERSON: 1]',
				count: 3,
				bases: ['Consent'],
				_resolveError: null,
			},
		}
	},
	methods: {
		/** No-op handler so interactive demos can emit without side effects. */
		noop() {},
		/**
		 * Reflect a pagination page change in the local demo state.
		 *
		 * @param {number} page Target page.
		 */
		onPageChanged(page) {
			this.pager = { ...this.pager, page }
		},
		/**
		 * Reflect a page-size change in the local demo state.
		 *
		 * @param {number} limit New page size.
		 */
		onPageSizeChanged(limit) {
			this.pager = { ...this.pager, limit, page: 1 }
		},
		/**
		 * Reflect a table selection change in the local demo state.
		 *
		 * @param {Array} keys Selected row keys.
		 */
		onSelect(keys) {
			this.selectedKeys = keys
		},
	},
}
</script>

<style scoped>
.dd-gallery {
	max-width: 1080px;
	margin: 0 auto;
	padding: 24px;
	color: var(--color-main-text);
}

.dd-gallery__masthead {
	padding-bottom: 16px;
	border-bottom: 1px solid var(--color-border);
	margin-bottom: 8px;
}

.dd-gallery__title {
	font-size: 28px;
	font-weight: 700;
	margin: 0 0 8px;
}

.dd-gallery__lead {
	max-width: 60ch;
	color: var(--color-text-maxcontrast);
	margin: 0 0 16px;
}

.dd-gallery__toc {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
}

.dd-gallery__toc-link {
	font-size: 13px;
	padding: 4px 10px;
	border-radius: var(--border-radius-pill, 16px);
	background: var(--color-background-hover);
	color: var(--color-main-text);
	text-decoration: none;
}

.dd-gallery__toc-link:hover {
	background: var(--color-primary-element-light);
}

.dd-section {
	padding: 24px 0;
	border-bottom: 1px solid var(--color-border);
	scroll-margin-top: 16px;
}

.dd-section__title {
	font-size: 20px;
	font-weight: 600;
	margin: 0 0 4px;
}

.dd-section__desc {
	color: var(--color-text-maxcontrast);
	margin: 0 0 16px;
	max-width: 70ch;
}

.dd-demo {
	padding: 20px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	background: var(--color-main-background);
}

.dd-demo--stack {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.dd-demo__label {
	display: block;
	font-family: monospace;
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	margin-bottom: 6px;
}

.dd-row {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 12px;
}

.dd-row:last-child {
	margin-bottom: 0;
}

.dd-row--wrap {
	flex-wrap: wrap;
	align-items: stretch;
}

.dd-icon-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
	gap: 12px;
}

.dd-icon-cell {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 16px 8px;
	margin: 0;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius, 8px);
}

.dd-icon-cell figcaption {
	font-size: 11px;
	color: var(--color-text-maxcontrast);
	text-align: center;
	word-break: break-word;
}

.dd-mini-card {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 16px;
}

.dd-mini-card__name {
	font-weight: 500;
}

.dd-tag {
	display: inline-block;
	font-size: 12px;
	padding: 2px 8px;
	border-radius: var(--border-radius-pill, 16px);
	background: var(--color-background-hover);
}

code {
	font-family: monospace;
	font-size: 0.9em;
	background: var(--color-background-hover);
	padding: 1px 5px;
	border-radius: 4px;
}
</style>
