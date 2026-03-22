<template>
	<div class="merge-field-menu">
		<div class="merge-field-menu__backdrop" @click="$emit('close')" />
		<div class="merge-field-menu__panel">
			<h3>{{ t('docudesk', 'Insert merge field') }}</h3>
			<NcTextField :value.sync="customField"
				:label="t('docudesk', 'Field name')"
				:placeholder="t('docudesk', 'e.g. naam, datum, zaaktype')"
				@keyup.enter="onInsert" />
			<div class="merge-field-menu__suggestions">
				<NcButton v-for="field in commonFields"
					:key="field"
					type="tertiary"
					@click="$emit('insert', field)">
					{{ '{{ ' + field + ' }}' }}
				</NcButton>
			</div>
			<div class="merge-field-menu__actions">
				<NcButton type="primary"
					:disabled="!customField"
					@click="onInsert">
					{{ t('docudesk', 'Insert') }}
				</NcButton>
				<NcButton type="tertiary" @click="$emit('close')">
					{{ t('docudesk', 'Cancel') }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { NcButton, NcTextField } from '@nextcloud/vue'

export default {
	name: 'MergeFieldMenu',
	components: {
		NcButton,
		NcTextField,
	},
	data() {
		return {
			customField: '',
			commonFields: [
				'naam',
				'datum',
				'zaaktype',
				'zaaknummer',
				'adres',
				'organisatie',
				'onderwerp',
				'kenmerk',
			],
		}
	},
	methods: {
		t,
		onInsert() {
			if (this.customField) {
				this.$emit('insert', this.customField)
				this.customField = ''
			}
		},
	},
}
</script>

<style scoped lang="scss">
.merge-field-menu {
	position: fixed;
	inset: 0;
	z-index: 1000;
	display: flex;
	align-items: center;
	justify-content: center;

	&__backdrop {
		position: absolute;
		inset: 0;
		background: rgba(0, 0, 0, 0.3);
	}

	&__panel {
		position: relative;
		background: var(--color-main-background);
		border-radius: var(--border-radius-large);
		padding: 20px;
		max-width: 400px;
		width: 100%;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);

		h3 {
			margin: 0 0 16px;
		}
	}

	&__suggestions {
		display: flex;
		flex-wrap: wrap;
		gap: 4px;
		margin: 12px 0;
	}

	&__actions {
		display: flex;
		gap: 8px;
		justify-content: flex-end;
		margin-top: 16px;
	}
}
</style>
