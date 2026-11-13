<?php

/**
 * Detection Provider Interface
 *
 * The seam an entity-detection engine plugs into.
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
 * An entity-detection engine.
 *
 * Implementations return entities in OpenRegister's persisted shape: `value`,
 * `type`, `category`, `positionStart`, `positionEnd` (null when the engine
 * gives none), `confidence` (0..1) and `detectionMethod`.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */
interface DetectionProviderInterface {

	/**
	 * Detect entities in a piece of a document's text.
	 *
	 * @param int                     $fileId      The Nextcloud file the text belongs to.
	 * @param string                  $text        The text to search.
	 * @param array<int, string>|null $entityTypes The enabled entity types, or null for all.
	 *
	 * @return DetectionResult The entities, positions relative to $text.
	 *
	 * @throws \RuntimeException When the engine cannot run or answers with something unusable.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function detect(int $fileId, string $text, ?array $entityTypes): DetectionResult;

	/**
	 * The provider's stable id: `default` or `llm`.
	 *
	 * @return string The id.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function id(): string;

	/**
	 * Whether the provider is configured well enough to be called.
	 *
	 * @return bool True when a call may be attempted.
	 *
 * @spec openspec/changes/llm-entity-detection-provider/tasks.md#task-1.1
	 */
	public function isAvailable(): bool;
}//end interface
