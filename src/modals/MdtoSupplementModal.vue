<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2

Edit the MDTO supplement of a record: the attributes OpenRegister does not
model. The core archival attributes are not on this form; they come from the
record's retention block and are read only.

@spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
-->

<template>
	<CnFormDialog
		ref="formDialog"
		:schema="schema"
		:item="item"
		:dialogTitle="t('filinq', 'Edit MDTO metadata')"
		data-testid="mdto-supplement-modal"
		@confirm="onConfirm"
		@close="$emit('close')" />
</template>

<script>
import { CnFormDialog } from '@conduction/nextcloud-vue'
import { translate as t } from '@nextcloud/l10n'
import { saveSupplement } from '../services/mdto.js'

/**
 * The editable supplement fields as a form schema.
 *
 * @return {object} The schema.
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
 */
export function supplementFormSchema() {
	return {
		title: t('filinq', 'MDTO supplement'),
		required: ['aggregatieniveau'],
		properties: {
			aggregatieniveau: {
				type: 'string',
				title: t('filinq', 'Aggregation level'),
				enum: ['archiefstuk', 'dossier'],
				'x-enum-labels': {
					archiefstuk: t('filinq', 'Archiefstuk (document)'),
					dossier: t('filinq', 'Dossier'),
				},
				order: 1,
			},
			omschrijving: {
				type: 'string',
				title: t('filinq', 'Description'),
				order: 2,
			},
			taal: {
				type: 'string',
				title: t('filinq', 'Language'),
				description: t('filinq', 'Language of the record as an RFC 5646 code, e.g. nl or en-GB.'),
				order: 3,
			},
			dekkingInTijdBegin: {
				type: 'string',
				format: 'date',
				title: t('filinq', 'Coverage start'),
				order: 4,
			},
			dekkingInTijdEind: {
				type: 'string',
				format: 'date',
				title: t('filinq', 'Coverage end'),
				order: 5,
			},
			archiefvormerOverride: {
				type: 'string',
				title: t('filinq', 'Archive creator override'),
				description: t('filinq', 'The archive creator for this record when it is not the organisation configured in OpenRegister.'),
				order: 6,
			},
		},
	}
}

export default {
	name: 'MdtoSupplementModal',
	components: { CnFormDialog },

	props: {
		/** The stored supplement, `{}` when the record has none yet. */
		supplement: { type: Object, default: () => ({}) },
		/** The record the supplement describes: { objectRef, objectType }. */
		subject: { type: Object, required: true },
	},

	emits: ['close', 'saved'],

	computed: {
		/**
		 * The form schema.
		 *
		 * @return {object}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		schema() {
			return supplementFormSchema()
		},

		/**
		 * The values the form opens with: the stored supplement.
		 *
		 * @return {object}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		item() {
			return { ...this.supplement }
		},
	},

	methods: {
		t,

		/**
		 * Save what was typed. An edited field counts as confirmed, so it
		 * leaves the list of unconfirmed proposals.
		 *
		 * @param {object} formData The form values.
		 * @return {Promise<void>}
		 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/tasks.md#3-frontend
		 */
		async onConfirm(formData) {
			const edited = Object.keys(supplementFormSchema().properties)
			const changes = {}
			for (const key of edited) {
				if (formData[key] !== undefined) {
					changes[key] = formData[key]
				}
			}
			changes.proposals = (this.supplement.proposals || []).filter(
				(name) => !edited.includes(name),
			)
			const result = await saveSupplement(this.supplement, changes, this.subject)
			if (result.ok) {
				this.$refs.formDialog?.setResult({ success: true })
				this.$emit('saved', result.data)
				return
			}
			this.$refs.formDialog?.setResult({ error: result.error })
		},
	},
}
</script>
