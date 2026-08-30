module.exports = {
	extends: '@nextcloud/stylelint-config',
	rules: {
		'selector-pseudo-element-no-unknown': [
			true,
			{
				ignorePseudoElements: ['v-deep'],
			},
		],
		// Indentation is prettier's, not stylelint's. The two genuinely disagree
		// on continuation lines, and two formatters with overlapping jurisdiction
		// make a repo unfixable. Prettier's glob also covers more than the
		// `stylelint` script's did, so the handover gains coverage rather than
		// losing it.
		//
		// The rule is not merely disabled here: stylelint removed `indentation`
		// in 16, and 17 errors on an unknown rule even when it is set to null.
	},
}
