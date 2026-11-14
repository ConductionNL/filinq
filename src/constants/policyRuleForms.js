/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * The blank form of each policy surface: its fields in form order, each with
 * the value an empty field starts from.
 */

export const POLICY_RULE_BLANK_FORMS = {
	prohibition: () => ({
		primaryName: '',
		entityType: 'PERSON',
		reason: '',
		legalAuthority: '',
		caseReference: '',
		severity: 'medium', // matches the publicationProhibition.severity default in filinq_register.json
		jurisdiction: '',
		validUntil: '',
		active: true,
		matchRules: [],
	}),
	standingConsent: () => ({
		entityText: '',
		entityType: 'PERSON',
		consentMethod: '',
		consentDocument: '',
		consentScope: '',
		legalBasis: '',
		validFrom: '',
		validUntil: '',
		active: true,
		matchRules: [],
		consentStatus: 'consent_given',
		publicationDecision: 'publish_with_consent',
		notificationStatus: 'skipped',
	}),
}
