<template>
	<div class="anonymization-page">
		<AnonymizationWidget />
	</div>
</template>

<script>
import AnonymizationWidget from './AnonymizationWidget.vue'

export default {
	name: 'AnonymizationIndex',
	components: {
		AnonymizationWidget,
	},
}
</script>

<style scoped>
.anonymization-page {
	padding: 20px;
}
</style>
