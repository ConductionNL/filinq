<?php

/**
 * Portal sign act
 *
 * What a portal sign act carries (consent, an optional drawn signature and
 * the values typed into the signer's fields), and how a refused value is
 * answered.
 *
 * @category  Controller
 * @package   OCA\Filinq\Controller\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Controller\Support;

use OCA\Filinq\Exception\SigningFieldsRefusedException;
use OCP\IRequest;
use Throwable;

/**
 * The input of the portal's signDocument act.
 *
 * A portal sign act is one document at a time, so it always carries a field
 * map: an empty one when the portal sent none. A request with required fields
 * is then refused per field until the portal collects them (D7).
 *
 * @category Controller
 * @package  OCA\Filinq\Controller\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.3
 */
final class PortalSignAct {

	/**
	 * Consent and the optional drawn signature.
	 *
	 * @var array<string, mixed>
	 */
	public readonly array $signatureData;

	/**
	 * What the signer typed, by placement position.
	 *
	 * @var array<array-key, mixed>
	 */
	public readonly array $fields;

	/**
	 * Read the act from the request: `consent`, `signature` and `fields`.
	 *
	 * @param IRequest $request The portal's forwarded request.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/portal-signing-surface/spec.md
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.3
	 */
	public function __construct(IRequest $request) {
		$signatureData = ['consent' => (bool) $request->getParam('consent', false)];
		$signature     = $request->getParam('signature');
		if (is_string($signature) === true && $signature !== '') {
			$signatureData['signature'] = $signature;
		}

		$this->signatureData = $signatureData;
		$this->fields        = array_filter((array) $request->getParam('fields', []), static fn (mixed $value): bool => is_string($value));

	}//end __construct()

	/**
	 * The answer to a refused field value, or null for any other failure.
	 *
	 * Nothing was signed. The body names each refused field and never the
	 * value that was typed.
	 *
	 * @param Throwable $exception What the sign act threw.
	 *
	 * @return array<string, mixed>|null `{error: fields_refused, fieldErrors}`, or null.
	 *
	 * @spec openspec/changes/signing-field-validation/tasks.md#task-2.3
	 */
	public function refusal(Throwable $exception): ?array {
		if ($exception instanceof SigningFieldsRefusedException === false) {
			return null;
		}

		return ['error' => 'fields_refused', 'fieldErrors' => $exception->fieldErrors()];

	}//end refusal()
}//end class
