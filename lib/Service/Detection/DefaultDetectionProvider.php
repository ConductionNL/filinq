<?php

/**
 * Default Detection Provider
 *
 * OpenRegister's own detection, unchanged: the default and the fallback target.
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
 * The default provider: OpenRegister's existing detection.
 *
 * OpenRegister detects entities while it extracts a file's text, with its own
 * globally configured method, and writes them to its catalogue itself. This
 * provider therefore adds nothing: its result says the default engine ran and
 * the caller reads OpenRegister's entities back exactly as before. It is always
 * available, which is what makes it a safe fallback.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
class DefaultDetectionProvider implements DetectionProviderInterface {

	/**
	 * The provider id.
	 *
	 * @var string
	 */
	public const ID = 'default';

	/**
	 * Report the default engine; OpenRegister already wrote its entities during extraction.
	 *
	 * @param DetectionRequest $request The file and enabled types (unused: OpenRegister owns this run).
	 * @param string           $text    The text (unused).
	 *
	 * @return DetectionResult An empty entity list from the default provider.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter) The interface passes them; OpenRegister has already run.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function detect(DetectionRequest $request, string $text): DetectionResult {
		return new DetectionResult(entities: [], providerUsed: self::ID);
	}//end detect()

	/**
	 * The provider id.
	 *
	 * @return string `default`.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function id(): string {
		return self::ID;
	}//end id()

	/**
	 * Always available.
	 *
	 * @return bool True.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function isAvailable(): bool {
		return true;
	}//end isAvailable()
}//end class
