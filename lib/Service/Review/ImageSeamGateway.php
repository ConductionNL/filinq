<?php

/**
 * Filinq's door to OpenRegister's image seam.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Review
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use OCA\Filinq\Exception\ScopeDecisionRefusedException;
use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\Files\File;
use Throwable;

/**
 * Hands regions to `ImageRedactionService::redactImage()` in OpenRegister.
 *
 * OpenRegister does the pixel work and writes the `_anonymized` sibling;
 * filinq only builds the regions. When OpenRegister has no seam the gateway
 * says so, and when the seam refuses a region nothing is written.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/image-redaction/specs/image-redaction/spec.md#requirement-a-page-a-page-range-or-a-whole-document-is-redacted-in-one-action-req-ddimr-009
 */
class ImageSeamGateway {

	/**
	 * OpenRegister's image seam.
	 */
	public const SEAM_CLASS = 'OCA\OpenRegister\Service\File\ImageRedactionService';

	/**
	 * OpenRegister's region value.
	 */
	public const REGION_CLASS = 'OCA\OpenRegister\Service\File\ImageRegion';

	/**
	 * What a scope region holds.
	 */
	public const ENTITY_TYPE = 'WITHHELD';

	/**
	 * The source recorded on a scope region.
	 */
	public const SOURCE = 'filinq-scope';

	/**
	 * Constructor.
	 *
	 * @param OpenRegisterServiceLocator $locator Resolver for OpenRegister services.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly OpenRegisterServiceLocator $locator,
	) {

	}//end __construct()

	/**
	 * Whether OpenRegister offers the image seam.
	 *
	 * @return bool True when the seam and its region value exist and resolve.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function available(): bool {
		if (class_exists(self::SEAM_CLASS) === false || class_exists(self::REGION_CLASS) === false) {
			return false;
		}

		try {
			return is_object($this->locator->get(className: self::SEAM_CLASS)) === true;
		} catch (Throwable) {
			return false;
		}

	}//end available()

	/**
	 * Detect the regions of an image-bearing file through the seam.
	 *
	 * @param File $file The file.
	 *
	 * @return array{detected: bool, reason: string|null, regions: list<array<string, mixed>>,
	 *     detectors: array<string, mixed>, objectDetection: string, objectDetectionReason: string|null} The outcome.
	 *
	 * @throws ScopeDecisionRefusedException When the seam is absent.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.3
	 */
	public function detect(File $file): array {
		if ($this->available() === false) {
			throw new ScopeDecisionRefusedException(message: ScopeRedactionService::NO_SEAM);
		}

		$outcome = $this->locator->get(className: self::SEAM_CLASS)->detectImage(file: $file)->toArray();

		return [
			'detected' => ($outcome['detected'] ?? false) === true,
			'reason' => ($outcome['reason'] ?? null),
			'regions' => array_values(array_filter((array) ($outcome['regions'] ?? []), 'is_array')),
			'detectors' => (array) ($outcome['detectors'] ?? []),
			'objectDetection' => (string) ($outcome['objectDetection'] ?? 'not-run'),
			'objectDetectionReason' => ($outcome['objectDetectionReason'] ?? null),
		];

	}//end detect()

	/**
	 * How many decided regions the seam holds for a file.
	 *
	 * @param File $file The file.
	 *
	 * @return int The regions not marked skip, 0 without the seam.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-3.3
	 */
	public function decidedRegionCount(File $file): int {
		if ($this->available() === false) {
			return 0;
		}

		return count($this->locator->get(className: self::SEAM_CLASS)->decidedRegions(file: $file));

	}//end decidedRegionCount()

	/**
	 * Burn the regions through the seam.
	 *
	 * For a standalone image the seam's regions carry no page, so page 1 of
	 * an image becomes the image itself.
	 *
	 * @param File                                                                    $file    The source document.
	 * @param list<array{page: int, box: array{x: float, y: float, w: float, h: float}}> $regions The regions.
	 * @param list<string>                                                            $bases   The grounds, recorded as the source.
	 *
	 * @return File The `_anonymized` sibling OpenRegister wrote.
	 *
	 * @throws ScopeDecisionRefusedException When the seam is absent or refuses; nothing is written.
	 *
	 * @spec openspec/changes/image-redaction/tasks.md#task-6.2
	 */
	public function burn(File $file, array $regions, array $bases): File {
		if ($this->available() === false) {
			throw new ScopeDecisionRefusedException(message: ScopeRedactionService::NO_SEAM);
		}

		$seam = $this->locator->get(className: self::SEAM_CLASS);
		$standalone = in_array((string) $file->getMimeType(), constant(self::SEAM_CLASS . '::STANDALONE_TYPES'), true);
		$regionClass = self::REGION_CLASS;
		$values = [];
		foreach ($regions as $region) {
			$page = $region['page'];
			if ($standalone === true) {
				$page = null;
			}

			$values[] = new $regionClass(
				page: $page,
				box: $region['box'],
				entityType: self::ENTITY_TYPE,
				confidence: 1.0,
				source: self::SOURCE . ':' . implode(',', $bases)
			);
		}

		try {
			$output = $seam->redactImage(file: $file, regions: $values);
		} catch (Throwable $e) {
			throw new ScopeDecisionRefusedException(
				message: 'OpenRegister could not burn these pages, so nothing was written: ' . $e->getMessage(),
				previous: $e
			);
		}

		if (($output instanceof File) === false) {
			throw new ScopeDecisionRefusedException(message: 'OpenRegister wrote no redacted file, so nothing was written.');
		}

		return $output;

	}//end burn()
}//end class
