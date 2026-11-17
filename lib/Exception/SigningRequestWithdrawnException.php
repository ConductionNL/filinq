<?php

/**
 * Filinq SigningRequestWithdrawnException
 *
 * Raised when a signatory signs a request its creator has withdrawn.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

namespace OCA\Filinq\Exception;

use OCP\AppFramework\Http;
use RuntimeException;

/**
 * The request was cancelled, so the signature is refused and the signatory is told why.
 *
 * Thrown only after the signer is authorised, so only the signatory the record
 * belongs to learns that the request was withdrawn.
 *
 * @category Exception
 * @package  OCA\Filinq\Exception
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/archive/2026-11-17-signing-cancellation/specs/signing-cancellation/spec.md
 */
class SigningRequestWithdrawnException extends RuntimeException {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(message: 'The signing request was withdrawn.', code: Http::STATUS_CONFLICT);
	}//end __construct()
}//end class
