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
	 *
	 * @return void
	 *
	 * @spec openspec/specs/document-signing/spec.md
	 */
	public function __construct(
		public readonly SigningAuditService $auditService,
		public readonly SignedArtifactProducer $artifactProducer,
		public readonly SigningConclusionEmitter $emitter,
	) {

	}//end __construct()
}//end class
