<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
@spec openspec/changes/entity-search/tasks.md#task-3.1
@visual exclude No pixel baseline yet: results depend on what OpenRegister's detection found on
	the instance, and a baseline needs a seeded catalogue.
-->
<template>
	<div class="entity-search">
		<div class="entity-search__header">
			<h2>{{ t('filinq', 'Entity search') }}</h2>
			<p class="entity-search__subtitle">
				{{ t('filinq', 'Find the documents a person, email address, IBAN or other detected value appears in. Every search is recorded in the processing log.') }}
			</p>
		</div>

		<form class="entity-search__form" @submit.prevent="onSearch">
			<NcTextField
				v-model="query"
				:label="t('filinq', 'Value to look for')"
				data-testid="entity-search-query" />
			<NcSelect
				v-model="type"
				:options="typeOptions"
				:inputLabel="t('filinq', 'Type')"
				:placeholder="t('filinq', 'Any type')"
				:clearable="true" />
			<NcButton type="submit" variant="primary" :disabled="loading">
				{{ t('filinq', 'Search') }}
			</NcButton>
		</form>

		<NcNoteCard v-if="error" type="error">
			{{ error }}
		</NcNoteCard>
		<NcLoadingIcon v-if="loading" :size="32" />
		<NcEmptyContent
			v-else-if="searched && results.length === 0 && !error"
			:name="t('filinq', 'Nothing found')"
			:description="t('filinq', 'Only documents that were extracted can be searched. A document nobody extracted has no detected values yet.')" />
		<template v-else-if="results.length > 0">
			<p>{{ t('filinq', 'Values found: {total}', { total }) }}</p>
			<CnDataTable
				:columns="columns"
				:rows="results"
				rowKey="uuid"
				:tableLabel="t('filinq', 'Detected values')"
				data-testid="entity-search-results"
				@row-click="open" />
		</template>

		<section
			v-if="detail"
			class="entity-search__detail"
			:aria-label="t('filinq', 'Where this value occurs')">
			<h3>{{ detail.value }}</h3>
			<p>{{ t('filinq', '{type}. Occurrences: {count}', { type: detail.type, count: detail.occurrenceCount }) }}</p>
			<CnDataTable
				v-if="documentRows.length > 0"
				:columns="documentColumns"
				:rows="documentRows"
				rowKey="fileId"
				:tableLabel="t('filinq', 'Documents')"
				data-testid="entity-search-documents" />
			<p v-if="detail.noAccess > 0" data-testid="entity-search-no-access">
				{{ t('filinq', 'Documents you have no access to: {count}', { count: detail.noAccess }) }}
			</p>
			<ul v-if="detail.other.length > 0">
				<li v-for="item in detail.other" :key="item.kind">
					{{ otherLabel(item.kind, item.count) }}
				</li>
			</ul>
		</section>
	</div>
</template>

<script>
import { CnDataTable } from '@conduction/nextcloud-vue'
import {
	NcButton,
	NcEmptyContent,
	NcLoadingIcon,
	NcNoteCard,
	NcSelect,
	NcTextField,
} from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import {
	ENTITY_TYPES,
	anonymisationLabel,
	entityDetail,
	otherLabel,
	searchEntities,
} from '../../services/entitySearch.js'

export default {
	name: 'EntitySearch',

	components: {
		CnDataTable,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcSelect,
		NcTextField,
	},

	data() {
		return {
			query: '',
			type: null,
			results: [],
			total: 0,
			searched: false,
			loading: false,
			error: '',
			detail: null,
		}
	},

	computed: {
		typeOptions() {
			return ENTITY_TYPES
		},

		columns() {
			return [
				{ key: 'value', label: t('filinq', 'Value') },
				{ key: 'type', label: t('filinq', 'Type') },
				{ key: 'category', label: t('filinq', 'Category') },
				{ key: 'occurrences', label: t('filinq', 'Occurrences') },
			]
		},

		documentColumns() {
			return [
				{ key: 'name', label: t('filinq', 'Document') },
				{ key: 'dossierName', label: t('filinq', 'Dossier') },
				{ key: 'anonymisationLabel', label: t('filinq', 'Anonymisation') },
				{ key: 'riskLevel', label: t('filinq', 'Risk') },
				{ key: 'confidence', label: t('filinq', 'Confidence') },
			]
		},

		documentRows() {
			if (!this.detail) {
				return []
			}
			return this.detail.documents.map((document) => ({
				...document,
				dossierName: document.dossier ? document.dossier.name : '',
				anonymisationLabel: anonymisationLabel(document.anonymisation.state),
				confidence: Math.max(...document.occurrences.map((o) => o.confidence)).toFixed(2),
			}))
		},
	},

	methods: {
		t,
		otherLabel,

		async onSearch() {
			this.loading = true
			this.error = ''
			this.detail = null
			const result = await searchEntities({ query: this.query.trim(), type: this.type || '' })
			this.loading = false
			this.searched = true
			if (!result.ok) {
				this.results = []
				this.total = 0
				this.error = result.error
				return
			}
			this.results = result.data.results
			this.total = result.data.total
		},

		async open(row) {
			this.error = ''
			const result = await entityDetail(row.uuid)
			if (!result.ok) {
				this.detail = null
				this.error = result.error
				return
			}
			this.detail = result.data
		},
	},
}
</script>

<style scoped>
.entity-search {
	padding: calc(var(--default-grid-baseline) * 4);
}

.entity-search__subtitle {
	color: var(--color-text-maxcontrast);
}

.entity-search__form {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 2);
	align-items: flex-end;
	margin: calc(var(--default-grid-baseline) * 4) 0;
}

.entity-search__detail {
	margin-top: calc(var(--default-grid-baseline) * 6);
}
</style>
