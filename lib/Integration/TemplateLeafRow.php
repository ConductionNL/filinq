<?php

/**
 * Template Leaf Row: one template as the template leaves show it.
 *
 * @category Integration
 * @package  OCA\Filinq\Integration
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Integration;

/**
 * The row shape shared by the `filinq-templates` data provider and the
 * `filinq-generate-document` widget: id, name, format and description.
 * Nothing else of a template leaves filinq through a leaf.
 *
 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
 */
final class TemplateLeafRow {

	/**
	 * One template as a leaf row.
	 *
	 * @param array<string, mixed>|object $template The template as the template service lists it.
	 *
	 * @return array{id: string, name: string, format: string, description: string}
	 *
	 * @spec openspec/changes/template-library-leaf/tasks.md#task-2.2
	 */
	public static function fromTemplate(array|object $template): array {
		$template = (array) $template;

		return [
			'id' => (string) ($template['id'] ?? $template['uuid'] ?? ''),
			'name' => (string) ($template['name'] ?? ''),
			'format' => (string) ($template['format'] ?? ''),
			'description' => (string) ($template['description'] ?? ''),
		];

	}//end fromTemplate()
}//end class
