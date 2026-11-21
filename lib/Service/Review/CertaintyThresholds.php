<?php

/**
 * The two confidence numbers of the review workbench: the detection floor and certain-from.
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
 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Review;

use OCA\Filinq\Service\OpenRegisterServiceLocator;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Reads OpenRegister's detection floor and filinq's certain-from, and classifies a finding.
 *
 * The floor is the confidence below which OpenRegister reports nothing at all
 * (`anonymisation.confidenceThreshold`, read through OpenRegister's
 * FileSettingsHandler::confidenceThreshold()). Certain-from is filinq's own
 * setting: a detected finding below it is uncertain and needs an explicit
 * decision before the document can be marked reviewed. Certain-from below the
 * floor would make the uncertain band empty by accident, so it is refused.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Review
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/anonymization-review-workbench/specs/anonymization-review-workbench/spec.md#requirement-the-screen-tells-a-certain-finding-from-an-uncertain-one-by-organisation-settings-req-ddarw-013
 */
class CertaintyThresholds {

	/**
	 * App-config key of certain-from.
	 *
	 * @var string
	 */
	public const CERTAIN_FROM_KEY = 'filinq.review.certain_from';

	/**
	 * Certain-from when none is stored.
	 *
	 * @var float
	 */
	public const DEFAULT_CERTAIN_FROM = 0.85;

	/**
	 * The floor assumed when OpenRegister does not expose one (its own default).
	 *
	 * @var float
	 */
	public const FALLBACK_FLOOR = 0.5;

	/**
	 * OpenRegister's file settings handler, which owns the detection floor.
	 *
	 * @var string
	 */
	private const FLOOR_OWNER = 'OCA\OpenRegister\Service\Settings\FileSettingsHandler';

	/**
	 * The detection method OpenRegister records on a manual entity.
	 *
	 * @var string
	 */
	private const METHOD_MANUAL = 'manual';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig                 $appConfig App config (certain-from).
	 * @param OpenRegisterServiceLocator $locator   Resolver for OpenRegister's file settings.
	 * @param LoggerInterface            $logger    Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly OpenRegisterServiceLocator $locator,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Both numbers and where each comes from.
	 *
	 * @return array{floor: float, floorSource: string, floorConfigurable: bool, certainFrom: float, certainFromSource: string}
	 *         `floorConfigurable` is false when OpenRegister has no such setting and the fallback is shown.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function read(): array {
		$floor = $this->floor();

		return [
			'floor' => ($floor ?? self::FALLBACK_FLOOR),
			'floorSource' => 'openregister',
			'floorConfigurable' => ($floor !== null),
			'certainFrom' => $this->certainFrom(),
			'certainFromSource' => 'filinq',
		];

	}//end read()

	/**
	 * Certain-from as stored, or the default when it is missing or not a number between 0 and 1.
	 *
	 * @return float The threshold.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function certainFrom(): float {
		$raw = trim($this->appConfig->getValueString('filinq', self::CERTAIN_FROM_KEY, (string) self::DEFAULT_CERTAIN_FROM));
		if (is_numeric($raw) === false || (float) $raw < 0.0 || (float) $raw > 1.0) {
			return self::DEFAULT_CERTAIN_FROM;
		}

		return (float) $raw;

	}//end certainFrom()

	/**
	 * Refuse a settings save whose certain-from is not a number, is outside 0..1 or is below the floor.
	 *
	 * A save without the key passes. Checked before anything is written, so a
	 * refused save changes nothing.
	 *
	 * @param array<string, mixed> $data The settings data to update.
	 *
	 * @return void
	 *
	 * @throws RuntimeException With code 400, naming the floor when that is the reason.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function assertValid(array $data): void {
		if (array_key_exists(self::CERTAIN_FROM_KEY, $data) === false) {
			return;
		}

		$value = $data[self::CERTAIN_FROM_KEY];
		if (is_numeric($value) === false || (float) $value < 0.0 || (float) $value > 1.0) {
			throw new RuntimeException('Certain-from must be a number between 0 and 1.', 400);
		}

		$floor = $this->read()['floor'];
		if ((float) $value < $floor) {
			throw new RuntimeException(
				sprintf('Certain-from cannot be below the detection floor of %s set in OpenRegister.', (string) $floor),
				400
			);
		}

	}//end assertValid()

	/**
	 * Whether a finding is uncertain: detected, not manual, with a confidence below certain-from.
	 *
	 * @param array<string, mixed> $relation A relation's fields (`detectionMethod`, `confidence`).
	 * @param float                $certainFrom The threshold.
	 *
	 * @return bool True when uncertain.
	 *
	 * @spec openspec/changes/anonymization-review-workbench/tasks.md#task-5.3
	 */
	public function isUncertain(array $relation, float $certainFrom): bool {
		if ((string) ($relation['detectionMethod'] ?? '') === self::METHOD_MANUAL) {
			return false;
		}

		return (float) ($relation['confidence'] ?? 0.0) < $certainFrom;

	}//end isUncertain()

	/**
	 * OpenRegister's floor, or null when OpenRegister does not expose it.
	 *
	 * @return float|null The floor.
	 */
	private function floor(): ?float {
		try {
			$handler = $this->locator->get(className: self::FLOOR_OWNER);
			if (is_object($handler) === false || method_exists($handler, 'confidenceThreshold') === false) {
				return null;
			}

			return (float) $handler->confidenceThreshold();
		} catch (Throwable $e) {
			$this->logger->info(
				'[CertaintyThresholds] OpenRegister exposes no detection floor; showing the fallback',
				['error' => $e->getMessage()]
			);
			return null;
		}

	}//end floor()
}//end class
