<?php

/**
 * Envelope Caller
 *
 * Who is asking for an envelope, and what that caller may do with it: the
 * initiator, a signer the envelope names, or an admin may read it; only the
 * initiator or an admin may manage (cancel) it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SigningEnvelope
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SigningEnvelope;

/**
 * The caller of an envelope operation. Built by SigningEnvelopeService.
 *
 * @category Service
 * @package  OCA\Filinq\Service\SigningEnvelope
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
 */
final class EnvelopeCaller {

	/**
	 * Constructor.
	 *
	 * @param string $userId  The caller
	 * @param bool   $isAdmin Whether the caller is an admin
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $userId,
		private readonly bool $isAdmin,
	) {

	}//end __construct()

	/**
	 * Whether the caller may read the envelope: an admin, its initiator or a signer it names.
	 *
	 * @param array $envelope The stored envelope
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function mayRead(array $envelope): bool {
		$initiator = $this->isInitiatorOf(envelope: $envelope);
		$signer = $this->isSignerOf(envelope: $envelope);

		return $this->isAdmin === true || $initiator === true || $signer === true;

	}//end mayRead()

	/**
	 * Whether the caller may manage (cancel) the envelope: an admin or its initiator.
	 *
	 * @param array $envelope The stored envelope
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function mayManage(array $envelope): bool {
		return $this->isAdmin === true || $this->isInitiatorOf(envelope: $envelope) === true;

	}//end mayManage()

	/**
	 * Whether the envelope names the caller as a signer.
	 *
	 * @param array $envelope The stored envelope
	 *
	 * @return bool
	 *
	 * @spec openspec/specs/bulk-signing-field-builder/spec.md#requirement-envelopes-group-documents-into-one-ceremony-with-per-document-records-req-ddbsf-004
	 */
	public function isSignerOf(array $envelope): bool {
		return in_array($this->userId, (array) ($envelope['signerUserIds'] ?? []), true);

	}//end isSignerOf()

	/**
	 * Whether the caller started the envelope.
	 *
	 * @param array $envelope The stored envelope
	 *
	 * @return bool
	 */
	private function isInitiatorOf(array $envelope): bool {
		return ($envelope['initiatorUserId'] ?? '') === $this->userId;

	}//end isInitiatorOf()
}//end class
