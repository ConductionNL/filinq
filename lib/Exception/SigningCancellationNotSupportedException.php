<?php

/**
 * Filinq SigningCancellationNotSupportedException
 *
 * Raised when a signing provider cannot withdraw a request it issued.
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

use RuntimeException;

/**
 * A provider that cannot cancel says so, rather than returning success.
 *
 * This exception exists because of a measured defect. `ValidSignProvider::
 * cancelSigning()` was, in full:
 *
 *     public function cancelSigning(string $externalId): bool {
 *         return true;
 *     }
 *
 * No call to ValidSign. Connected to a UI, a user would cancel a request, be told
 * it succeeded, and the request would stay live at the provider — signatories could
 * still open and sign a document the user believed withdrawn, producing a legally
 * valid signature nobody expected.
 *
 * A `bool` return is what made that look like an implementation. Void-or-throw
 * removes the option: a provider either completes, or raises something the caller
 * must handle. "I cannot do this" is information a user can act on; `false` is
 * indistinguishable from a transient failure.
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
 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
 */
class SigningCancellationNotSupportedException extends RuntimeException {

	/**
	 * Constructor.
	 *
	 * @param string $provider The provider that cannot cancel.
	 * @param string $reason   Why, in terms a user can act on.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/signing-cancellation/specs/signing-cancellation/spec.md
	 */
	public function __construct(string $provider, string $reason = '') {
		$message = sprintf(
			'The "%s" signing provider cannot withdraw a signing request. '
			. 'The request is still live and its signatories can still sign. '
			. 'Withdraw it directly with the provider.',
			$provider
		);

		if ($reason !== '') {
			$message .= ' ' . $reason;
		}

		parent::__construct(message: $message);
	}//end __construct()
}//end class
