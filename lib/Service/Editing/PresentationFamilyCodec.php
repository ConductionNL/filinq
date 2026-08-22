<?php

/**
 * Filinq PresentationFamilyCodec
 *
 * One presentation family's answer to "read the shapes" and "write this one".
 *
 * @category Service
 * @package  OCA\Filinq\Service\Editing
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @link https://filinq.app
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#2-presentation
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Editing;

/**
 * Reads and writes presentation shapes for one family.
 */
interface PresentationFamilyCodec {

	/**
	 * Whether this codec handles an extension.
	 *
	 * @param string $extension The lower-case file extension.
	 *
	 * @return bool True when handled.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function supports(string $extension): bool;

	/**
	 * Read every text-bearing shape.
	 *
	 * @param string $packageBytes The package.
	 *
	 * @return array<int, array{slide: string, shape: string, region: string, text: string}> The shapes.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function readShapes(string $packageBytes): array;

	/**
	 * Replace one shape's text.
	 *
	 * @param string $packageBytes The package.
	 * @param string $slide        The slide id.
	 * @param string $shape        The shape id.
	 * @param string $region       Either `slide` or `notes`.
	 * @param string $text         The replacement text.
	 *
	 * @return string The rewritten package.
	 *
	 * @spec openspec/changes/multi-format-editing-tools/tasks.md#task-2.1
	 */
	public function writeShape(string $packageBytes, string $slide, string $shape, string $region, string $text): string;
}//end interface
