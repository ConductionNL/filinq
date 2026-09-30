<?php

/**
 * Signing Envelope Roll-up
 *
 * An envelope's status, derived from the statuses of its member requests.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SigningEnvelope;

/**
 * Pure rules: which envelope status a set of member statuses means.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SigningEnvelope
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
class SigningEnvelopeRollUp {

	/**
	 * Member statuses a signer can still act on.
	 *
	 * @var list<string>
	 */
	public const OPEN = ['PENDING', 'IN_PROGRESS'];

	/**
	 * The envelope status for these member statuses.
	 *
	 * In this order: an envelope its initiator cancelled stays cancelled; one
	 * declined member makes it `partially_declined` (the members already signed
	 * stay signed); every member completed makes it `completed`; while any
	 * member is open it is `pending` (nobody acted yet) or `in_progress`; a
	 * set of ended members that is none of these (some expired or were
	 * cancelled one by one) is `incomplete`.
	 *
	 * @param list<string> $memberStatuses The member requests' statuses.
	 * @param string       $current        The envelope's stored status.
	 *
	 * @return string The envelope status.
	 *
	 * @spec openspec/changes/bulk-signing-field-builder/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function statusOf(array $memberStatuses, string $current): string {
		if ($current === 'cancelled') {
			return 'cancelled';
		}

		if (in_array('DECLINED', $memberStatuses, true) === true) {
			return 'partially_declined';
		}

		$distinct = array_values(array_unique($memberStatuses));
		if ($distinct === ['COMPLETED']) {
			return 'completed';
		}

		if ($distinct === ['PENDING']) {
			return 'pending';
		}

		if (array_intersect($distinct, self::OPEN) !== []) {
			return 'in_progress';
		}

		return 'incomplete';

	}//end statusOf()
}//end class
