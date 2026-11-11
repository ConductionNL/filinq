<?php

/**
 * Backend Walk
 *
 * The walk both conversion runs share: ask each backend in order until one
 * produces the PDF, and fail with what the run recorded when none does.
 * {@see PdfConversionCascade} and {@see TaggedPdfConversion} say what asking
 * one backend means and which failure is thrown.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
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

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\StripsFileExtension;

/**
 * Asks the backends in order; the first answer wins.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
 */
abstract class BackendWalk {
	// The runs name the PDF after the source and pass its extension on.
	use StripsFileExtension {
		extractExtension as protected;
		stripExtension as protected;
	}

	/**
	 * Constructor.
	 *
	 * @param array<int, mixed> $backends Ordered list of backends; first success wins.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly array $backends,
	) {

	}//end __construct()

	/**
	 * Walk the backends.
	 *
	 * @return array{file: \OCP\Files\File, backend: string} The PDF and the backend that produced it.
	 *
	 * @throws ConversionFailedException When no backend produced it.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	public function run(): array {
		foreach ($this->backends as $backend) {
			$result = $this->tryBackend(backend: $backend);
			if ($result !== null) {
				return $result;
			}
		}

		throw $this->failure();

	}//end run()

	/**
	 * Ask one backend, recording why it could not answer.
	 *
	 * @param mixed $backend The backend, as registered.
	 *
	 * @return array{file: \OCP\Files\File, backend: string}|null The PDF, or null to ask the next one.
	 */
	abstract protected function tryBackend(mixed $backend): ?array;

	/**
	 * The failure to throw when no backend answered.
	 *
	 * @return ConversionFailedException The failure, with every recorded attempt.
	 */
	abstract protected function failure(): ConversionFailedException;
}//end class
