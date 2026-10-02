<?php

/**
 * Office template filler
 *
 * Fills the `${...}` tags of a DOCX with PhpWord's TemplateProcessor: the
 * text fragments first, then the repeating blocks (`${regel}` ...
 * `${/regel}`) and table rows, then every remaining tag from the data,
 * through the template's field mapping. Every value is XML-escaped before it
 * goes in, so data can never become WordprocessingML. Nothing the template
 * says is executed: the office path only substitutes.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

/**
 * Fills a DOCX source with data.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
 */
class OfficeTemplateFiller {

	/**
	 * Constructor.
	 *
	 * @param DataPath $dataPath Reads dotted paths from the data.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DataPath $dataPath,
	) {

	}//end __construct()

	/**
	 * Fill a DOCX.
	 *
	 * @param string                $docxBytes The source.
	 * @param array<string, mixed>  $data      The data context.
	 * @param array<string, string> $fieldMap  Tag to property path aliases.
	 * @param array<string, string> $fragments Filled fragment text by slug.
	 *
	 * @return array{bytes: string, warnings: string[]} The filled DOCX.
	 *
	 * @throws OfficeTemplateRefused 422 when the source cannot be opened.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-3
	 */
	public function fill(string $docxBytes, array $data, array $fieldMap, array $fragments): array {
		$path = tempnam(sys_get_temp_dir(), 'filinq_fill_');
		file_put_contents($path, $docxBytes);
		try {
			$processor = new TemplateProcessor($path);
		} catch (Throwable $e) {
			unlink($path);
			throw new OfficeTemplateRefused(message: 'The office source could not be opened: ' . $e->getMessage(), reason: 'corrupt');
		}

		foreach ($fragments as $slug => $text) {
			$processor->setValue('fragment:' . $slug, $this->escape(value: $text));
		}

		$warnings = $this->fillBlocks(processor: $processor, data: $data, fieldMap: $fieldMap);
		$warnings = array_merge($warnings, $this->fillRows(processor: $processor, data: $data, fieldMap: $fieldMap));
		$warnings = array_merge($warnings, $this->fillScalars(processor: $processor, data: $data, fieldMap: $fieldMap));

		$processor->saveAs($path);
		$bytes = (string) file_get_contents($path);
		unlink($path);

		return ['bytes' => $bytes, 'warnings' => $warnings];

	}//end fill()

	/**
	 * Clone each `${name}` ... `${/name}` block once per row of its data,
	 * or remove it when there are none.
	 *
	 * @param TemplateProcessor     $processor The open source.
	 * @param array<string, mixed>  $data      The data.
	 * @param array<string, string> $fieldMap  Aliases.
	 *
	 * @return string[] Warnings.
	 */
	private function fillBlocks(TemplateProcessor $processor, array $data, array $fieldMap): array {
		$variables = $processor->getVariables();
		$warnings = [];
		foreach ($variables as $variable) {
			if (str_starts_with($variable, '/') === false || in_array(substr($variable, 1), $variables, true) === false) {
				continue;
			}

			$block = substr($variable, 1);
			$rows = $this->dataPath->rows(data: $data, path: ($fieldMap[$block] ?? $block));
			if ($rows === null || $rows === []) {
				$processor->deleteBlock($block);
				$warnings[] = 'No rows for block ' . $block . '; the block is left out.';
				continue;
			}

			$replacements = array_map(fn (array $row): array => $this->rowReplacements(prefix: $block, row: $row), $rows);
			$processor->cloneBlock($block, 0, true, false, $replacements);
		}

		return $warnings;

	}//end fillBlocks()

	/**
	 * Clone a table row once per item when its tags point into a list
	 * (`${regels.omschrijving}` with `regels` a list of rows).
	 *
	 * @param TemplateProcessor     $processor The open source.
	 * @param array<string, mixed>  $data      The data.
	 * @param array<string, string> $fieldMap  Aliases.
	 *
	 * @return string[] Warnings.
	 */
	private function fillRows(TemplateProcessor $processor, array $data, array $fieldMap): array {
		$groups = $this->rowGroups(variables: $processor->getVariables(), data: $data, fieldMap: $fieldMap);

		$warnings = [];
		foreach ($groups as $prefix => $group) {
			$anchor = array_key_first($group['tags']);
			try {
				$processor->cloneRow($anchor, count($group['rows']));
			} catch (Throwable) {
				$warnings[] = 'The tags of ' . $prefix . ' point at a list but are not in a table row; they are left empty.';
				continue;
			}

			foreach ($group['rows'] as $index => $row) {
				foreach ($group['tags'] as $tag => $field) {
					$processor->setValue($tag . '#' . ($index + 1), $this->escape(value: (string) ($this->dataPath->scalar(data: $row, path: $field) ?? '')));
				}
			}
		}

		return $warnings;

	}//end fillRows()

	/**
	 * The tags that point into a list, grouped by the list.
	 *
	 * @param string[]              $variables The tags.
	 * @param array<string, mixed>  $data      The data.
	 * @param array<string, string> $fieldMap  Aliases.
	 *
	 * @return array<string, array{rows: array, tags: array<string, string>}> By list path.
	 */
	private function rowGroups(array $variables, array $data, array $fieldMap): array {
		$groups = [];
		foreach ($variables as $variable) {
			$path = $fieldMap[$variable] ?? $variable;
			if (str_contains($path, '.') === false || $this->dataPath->scalar(data: $data, path: $path) !== null) {
				continue;
			}

			$prefix = strstr($path, '.', true);
			$rows = $this->dataPath->rows(data: $data, path: $prefix);
			if ($rows !== null && $rows !== []) {
				$groups[$prefix]['rows'] = $rows;
				$groups[$prefix]['tags'][$variable] = substr($path, strlen($prefix) + 1);
			}
		}

		return $groups;

	}//end rowGroups()

	/**
	 * Fill every remaining tag from the data; a tag without a value is left
	 * empty and named in a warning.
	 *
	 * @param TemplateProcessor     $processor The open source.
	 * @param array<string, mixed>  $data      The data.
	 * @param array<string, string> $fieldMap  Aliases.
	 *
	 * @return string[] Warnings.
	 */
	private function fillScalars(TemplateProcessor $processor, array $data, array $fieldMap): array {
		$warnings = [];
		foreach (array_unique($processor->getVariables()) as $variable) {
			if (str_starts_with($variable, 'fragment:') === true || str_starts_with($variable, '/') === true) {
				continue;
			}

			$value = $this->dataPath->scalar(data: $data, path: ($fieldMap[$variable] ?? $variable));
			if ($value === null) {
				$warnings[] = 'No value for tag ' . $variable . '; it is left empty.';
				$value = '';
			}

			$processor->setValue($variable, $this->escape(value: $value));
		}

		return $warnings;

	}//end fillScalars()

	/**
	 * The replacements of one block clone: `block.key` and `key` for every field.
	 *
	 * @param string               $prefix The block name.
	 * @param array<string, mixed> $row    The row.
	 *
	 * @return array<string, string> Escaped values by tag.
	 */
	private function rowReplacements(string $prefix, array $row): array {
		$replacements = [];
		foreach (array_keys($row) as $key) {
			$value = $this->escape(value: (string) ($this->dataPath->scalar(data: $row, path: (string) $key) ?? ''));
			$replacements[$prefix . '.' . $key] = $value;
			$replacements[(string) $key] = $value;
		}

		return $replacements;

	}//end rowReplacements()

	/**
	 * XML-escape a value (PhpWord's own escaping is off by default).
	 *
	 * @param string $value The value.
	 *
	 * @return string The escaped value.
	 */
	private function escape(string $value): string {
		return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

	}//end escape()
}//end class
