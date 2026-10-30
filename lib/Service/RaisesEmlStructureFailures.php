<?php

/**
 * RaisesEmlStructureFailures — the conversion failures of the EML anonymise API.
 *
 * Both EML paths ask OpenRegister's FileService for an anonymised EML
 * structure and refuse to fall back to a raw parse, because leaking an
 * un-redacted mail is the worse failure. This trait builds the typed failures
 * they raise; each path keeps its own logging and catch order.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use Throwable;

/**
 * Builds the ConversionFailedExceptions of the EML anonymise API.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec exclude failure plumbing — carries no product behaviour of its own
 */
trait RaisesEmlStructureFailures {
	/**
	 * The failure for an anonymise call that threw.
	 *
	 * @param Throwable $error What the call threw.
	 *
	 * @return ConversionFailedException The failure to throw.
	 */
	private function emlApiFailed(Throwable $error): ConversionFailedException {
		return new ConversionFailedException(
			message: 'OpenRegister anonymise-EML API failed: ' . $error->getMessage(),
			attempts: [$this->emlAttempt(available: true, reason: 'anonymizeEmlStructured threw: ' . $error->getMessage())],
			previous: $error
		);
	}//end emlApiFailed()

	/**
	 * The structure the anonymise call returned, refused when it is not an object.
	 *
	 * @param mixed $structure What the call returned.
	 *
	 * @return object The structure.
	 *
	 * @throws ConversionFailedException When there is no structure.
	 */
	private function requireEmlStructure(mixed $structure): object {
		if (is_object($structure) === false) {
			throw new ConversionFailedException(
				message: 'OpenRegister anonymise-EML API returned no structure.',
				attempts: [$this->emlAttempt(available: true, reason: 'anonymizeEmlStructured returned non-object')]
			);
		}

		return $structure;
	}//end requireEmlStructure()

	/**
	 * One attempt entry for the EML backend, as the error body lists it.
	 *
	 * @param bool $available Whether the backend could run at all.
	 * @param string $reason Why it did not convert.
	 *
	 * @return array<string, mixed> The attempt.
	 */
	private function emlAttempt(bool $available, string $reason): array {
		return ['name' => 'eml', 'available' => $available, 'supports' => true, 'reason' => $reason];
	}//end emlAttempt()
}//end trait
