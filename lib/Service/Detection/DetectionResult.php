<?php

/**
 * Detection Result
 *
 * What one detection run produced, and which engine produced it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Detection
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Detection;

/**
 * The outcome of a detection run: the entities a provider produced, the
 * provider that actually ran, and whether that is a fallback.
 *
 * `providerUsed` always names the engine whose results the operator sees. A
 * fallback carries `fellBack = true` and a warning, so a default-provider run
 * is never presented as an LLM run.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
final class DetectionResult {

	/**
	 * Constructor.
	 *
	 * @param array<int, array<string, mixed>> $entities     The entities the provider produced, in OpenRegister's shape.
	 * @param string                           $providerUsed The id of the provider that produced them.
	 * @param bool                             $fellBack     Whether the chosen provider failed and the default ran instead.
	 * @param string|null                      $warning      The operator-facing warning for a fallback, or null.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function __construct(
		public readonly array $entities,
		public readonly string $providerUsed,
		public readonly bool $fellBack=false,
		public readonly ?string $warning=null,
	) {
	}//end __construct()

	/**
	 * The fields this result adds to the extraction payload and the report.
	 *
	 * @return array{detectionProvider: string, detectionFellBack: bool, detectionWarning: string|null}
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-3.2
	 */
	public function toPayload(): array {
		return [
			'detectionProvider' => $this->providerUsed,
			'detectionFellBack' => $this->fellBack,
			'detectionWarning'  => $this->warning,
		];
	}//end toPayload()
}//end class
