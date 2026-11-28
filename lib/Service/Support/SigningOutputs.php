<?php

/**
 * Signing outputs
 *
 * What a signing act leaves behind: the audit trail entry, the signed
 * artifact and the conclusion event for the app that delegated the request.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Support;

use OCA\Filinq\Service\SignedArtifactProducer;
use OCA\Filinq\Service\SigningAuditService;
use OCA\Filinq\Service\SigningConclusionEmitter;
use OCA\Filinq\Service\Signing\SigningMail;

/**
 * What a signing act leaves behind.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-signing/spec.md
 */
final class SigningOutputs {

	/**
	 * Constructor.
	 *
	 * @param SigningAuditService      $auditService     Writes the signing audit trail
	 * @param SignedArtifactProducer   $artifactProducer Produces the signed document and the initiator's source document
	 * @param SigningConclusionEmitter $emitter          Emits the conclusion event for a delegated request
	 * @param SigningMail|null         $mail             Mails a signer whose turn it is and the initiator on a decline; null mails nobody
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 * @spec openspec/changes/signer-consent-notifications-to-email-leaf/tasks.md#d-1-signerinitiator-notifications-via-the-email-leaf-m
	 */
	public function __construct(
		public readonly SigningAuditService $auditService,
		public readonly SignedArtifactProducer $artifactProducer,
		public readonly SigningConclusionEmitter $emitter,
		public readonly ?SigningMail $mail = null,
	) {

	}//end __construct()
}//end class
