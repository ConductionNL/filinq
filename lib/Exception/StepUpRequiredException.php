<?php

/**
 * Step-Up Required Exception
 *
 * A signing act was refused because the signer's identity evidence is
 * missing, stale, from an unregistered provider, or below the assurance the
 * act needs.
 *
 * @category  Exception
 * @package   OCA\Filinq\Exception
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use RuntimeException;

/**
 * A 403 that tells the signer how to get past it (REQ-DDSIR-003).
 *
 * The code is 403, so every controller that honours an exception code
 * answers 403 without knowing this class. The ones that do know it add the
 * step-up hint: which assurance is needed and which provider can reach it.
 * It is thrown only after the ownership check, so the hint reaches only the
 * signer the record belongs to.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
 */
class StepUpRequiredException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $reason `missing`, `stale`, `unregistered` or `insufficient`.
	 * @param string $requiredAssurance The assurance the act needs.
	 * @param string $heldAssurance The assurance the evidence carried, '' when there was none.
	 * @param string $provider The provider that can step the signer up.
	 *
	 * @return void
	 */
	public function __construct(
		public readonly string $reason,
		public readonly string $requiredAssurance,
		public readonly string $heldAssurance,
		public readonly string $provider,
	) {
		parent::__construct(
			message: 'Identity evidence ' . $reason . ': this act needs assurance ' . $requiredAssurance,
			code: 403
		);

	}//end __construct()

	/**
	 * The hint a controller returns beside the 403.
	 *
	 * @return array{required: bool, reason: string, requiredAssurance: string, heldAssurance: string, provider: string}
	 *
	 * @spec openspec/changes/signer-identity-rails/specs/signer-identity-rails/spec.md
	 */
	public function stepUp(): array {
		return [
			'required' => true,
			'reason' => $this->reason,
			'requiredAssurance' => $this->requiredAssurance,
			'heldAssurance' => $this->heldAssurance,
			'provider' => $this->provider,
		];

	}//end stepUp()
}//end class
