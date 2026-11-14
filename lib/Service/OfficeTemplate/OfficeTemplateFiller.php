<?php

/**
 * Filinq Office Template Filler
 *
 * Fills the merge tags of a DOCX with data: text fragments first, then
 * repeating blocks and table rows, then every remaining field.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use Exception;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

/**
 * Fills a DOCX's merge tags.
 *
 * Filling only substitutes values: no logic written in the template runs.
 * Every value is XML-escaped before it is written, so data cannot add markup
 * to the document. A tag without a value is left empty and named in the
 * warnings; a missing fragment is written as a visible marker.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class OfficeTemplateFiller {

	/**
	 * The prefix of a fragment reference.
	 */
	public const FRAGMENT_PREFIX = 'fragment:';

	/**
	 * What a missing fragment renders as.
	 */
	public const MISSING_FRAGMENT = '[ontbrekende bouwsteen: %s]';

	/**
	 * Fill a DOCX.
	 *
	 * @param string                $docx      The DOCX to fill.
	 * @param array<string, mixed>  $data      The data context.
	 * @param array<string, string> $fragments The fragment texts by slug.
	 * @param array<string, mixed>  $fieldMap  Tag => data path aliases.
	 *
	 * @return array{docx: string, warnings: array<int, string>, fragments: array<int, string>}
	 *         The filled DOCX, the warnings and the fragment slugs used.
	 *
	 * @throws Exception When the DOCX cannot be read (422).
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
	 */
	public function fill(string $docx, array $data, array $fragments=[], array $fieldMap=[]): array {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-fill-');
		file_put_contents($path, $docx);
		try {
			$processor = new TemplateProcessor($path);
		} catch (Throwable) {
			unlink($path);
			throw new Exception(message: 'The template source could not be read as a DOCX document.', code: 422);
		}

		unlink($path);
		$run = ['warnings' => [], 'fragments' => []];
		$run = $this->fillFragments(processor: $processor, data: $data, fragments: $fragments, fieldMap: $fieldMap, run: $run);
		$this->fillBlocks(processor: $processor, data: $data, fieldMap: $fieldMap);
		$this->fillRows(processor: $processor, data: $data, fieldMap: $fieldMap);
		foreach ($this->variables(processor: $processor) as $tag) {
			$value = $this->lookup(data: $data, path: $this->aliased(tag: $tag, fieldMap: $fieldMap));
			if ($value === null) {
				$run['warnings'][] = 'No value for merge tag "'.$tag.'"; it is left empty.';
				$value = '';
			}

			$processor->setValue($tag, $this->escape(value: $value));
		}

		$out = (string) tempnam(sys_get_temp_dir(), 'filinq-filled-');
		$processor->saveAs($out);
		$bytes = (string) file_get_contents($out);
		unlink($out);

		return ['docx' => $bytes, 'warnings' => array_values(array_unique($run['warnings'])), 'fragments' => $run['fragments']];
	}//end fill()

	/**
	 * The fragment slugs a set of tags references.
	 *
	 * @param array<int, string> $tags The tags.
	 *
	 * @return array<int, string> The slugs, once each.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function fragmentSlugs(array $tags): array {
		$slugs = [];
		foreach ($tags as $tag) {
			if (str_starts_with($tag, self::FRAGMENT_PREFIX) === true) {
				$slugs[] = trim(substr($tag, strlen(self::FRAGMENT_PREFIX)));
			}
		}

		return array_values(array_unique(array_filter($slugs, static fn (string $slug): bool => $slug !== '')));
	}//end fragmentSlugs()

	/**
	 * Fill a fragment's own merge tags from the data, one level deep.
	 *
	 * A fragment reference inside a fragment is not expanded: it is removed
	 * and named in the warnings, so fragments cannot loop.
	 *
	 * @param string               $text     The fragment text.
	 * @param array<string, mixed> $data     The data context.
	 * @param array<string, mixed> $fieldMap Tag => data path aliases.
	 *
	 * @return array{text: string, warnings: array<int, string>}
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	public function fillFragmentText(string $text, array $data, array $fieldMap=[]): array {
		$warnings = [];
		$filled = (string) preg_replace_callback(
			'/\$\{([^{}]+)\}/',
			function (array $match) use ($data, $fieldMap, &$warnings): string {
				$tag = trim($match[1]);
				if (str_starts_with($tag, self::FRAGMENT_PREFIX) === true) {
					$warnings[] = 'A text fragment cannot contain another fragment ("'.$tag.'"); it is left out.';
					return '';
				}

				$value = $this->lookup(data: $data, path: $this->aliased(tag: $tag, fieldMap: $fieldMap));
				if ($value === null) {
					$warnings[] = 'No value for merge tag "'.$tag.'"; it is left empty.';
					return '';
				}

				return $value;
			},
			$text
		);

		return ['text' => $filled, 'warnings' => $warnings];
	}//end fillFragmentText()

	/**
	 * The value at a dotted path as text, or null when there is none.
	 *
	 * A list of scalars reads as a comma-separated list; a yes/no reads as
	 * ja or nee.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string               $path The dotted path.
	 *
	 * @return string|null
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-office-templates-render-through-the-conversion-cascade-req-ddota-003
	 */
	public function lookup(array $data, string $path): ?string {
		$value = $this->raw(data: $data, path: $path);
		if ($value === null) {
			return null;
		}

		if (is_bool($value) === true) {
			return ['nee', 'ja'][(int) $value];
		}

		if (is_scalar($value) === true) {
			return (string) $value;
		}

		if (is_array($value) === true && array_is_list($value) === true) {
			$items = array_filter($value, static fn (mixed $item): bool => is_scalar($item));
			if (count($items) === count($value)) {
				return implode(', ', array_map('strval', $items));
			}
		}

		return null;
	}//end lookup()

	/**
	 * Replace every fragment reference by its filled text or a marker.
	 *
	 * @param TemplateProcessor     $processor The document.
	 * @param array<string, mixed>  $data      The data context.
	 * @param array<string, string> $fragments The fragment texts by slug.
	 * @param array<string, mixed>  $fieldMap  Tag => data path aliases.
	 * @param array<string, mixed>  $run       The warnings and fragments so far.
	 *
	 * @return array<string, mixed> The warnings and fragments.
	 */
	private function fillFragments(TemplateProcessor $processor, array $data, array $fragments, array $fieldMap, array $run): array {
		foreach ($this->variables(processor: $processor) as $tag) {
			if (str_starts_with($tag, self::FRAGMENT_PREFIX) === false) {
				continue;
			}

			$slug = trim(substr($tag, strlen(self::FRAGMENT_PREFIX)));
			if (isset($fragments[$slug]) === false) {
				$run['warnings'][] = 'Text fragment "'.$slug.'" does not exist; a marker is shown in its place.';
				$processor->setValue($tag, $this->escape(value: sprintf(self::MISSING_FRAGMENT, $slug)));
				continue;
			}

			$filled = $this->fillFragmentText(text: $fragments[$slug], data: $data, fieldMap: $fieldMap);
			$run['warnings'] = array_merge($run['warnings'], $filled['warnings']);
			$run['fragments'][] = $slug;
			$processor->setValue($tag, $this->escape(value: $filled['text']));
		}

		return $run;
	}//end fillFragments()

	/**
	 * Repeat every `${name}`…`${/name}` block once per item of its list.
	 *
	 * An empty or absent list removes the block; a yes/no keeps it once or
	 * removes it. Inside a block a tag reads from the item first, with or
	 * without the block's or item's first name segment, then from the data.
	 *
	 * @param TemplateProcessor    $processor The document.
	 * @param array<string, mixed> $data      The data context.
	 * @param array<string, mixed> $fieldMap  Tag => data path aliases.
	 *
	 * @return void
	 */
	private function fillBlocks(TemplateProcessor $processor, array $data, array $fieldMap): void {
		$raw = $processor->getVariables();
		foreach (array_unique($raw) as $name) {
			if (str_starts_with($name, '/') === true || in_array('/'.$name, $raw, true) === false) {
				continue;
			}

			$value = $this->raw(data: $data, path: $this->aliased(tag: $name, fieldMap: $fieldMap));
			if (is_array($value) === false || $value === [] || array_is_list($value) === false) {
				$processor->cloneBlock($name, (int) ($value !== null && $value !== false && $value !== [] && $value !== ''), true);
				continue;
			}

			$replacements = [];
			foreach ($value as $item) {
				$replacements[] = $this->itemValues(tags: $raw, name: $name, item: $item, data: $data);
			}

			$processor->cloneBlock($name, count($replacements), true, false, $replacements);
		}
	}//end fillBlocks()

	/**
	 * Repeat a table row once per item of the list its tags read from.
	 *
	 * A tag `${regels.omschrijving}` in a table row, where `regels` is a list,
	 * repeats that row for each item. A row outside a table is filled once,
	 * with the values joined, and named in no warning.
	 *
	 * @param TemplateProcessor    $processor The document.
	 * @param array<string, mixed> $data      The data context.
	 * @param array<string, mixed> $fieldMap  Tag => data path aliases.
	 *
	 * @return void
	 */
	private function fillRows(TemplateProcessor $processor, array $data, array $fieldMap): void {
		$groups = [];
		foreach ($this->variables(processor: $processor) as $tag) {
			$path = $this->aliased(tag: $tag, fieldMap: $fieldMap);
			$dot = strpos($path, '.');
			if ($dot === false) {
				continue;
			}

			$list = $this->raw(data: $data, path: substr($path, 0, $dot));
			if (is_array($list) === true && array_is_list($list) === true && $this->isListOfRecords(list: $list) === true) {
				$groups[substr($path, 0, $dot)][$tag] = substr($path, $dot + 1);
			}
		}

		foreach ($groups as $prefix => $tags) {
			$items = (array) $this->raw(data: $data, path: $prefix);
			$first = (string) array_key_first($tags);
			try {
				if ($items === []) {
					$processor->deleteRow($first);
					continue;
				}

				$processor->cloneRow($first, count($items));
			} catch (Throwable) {
				// Not in a table: the scalar pass fills it as a joined list.
				continue;
			}

			foreach (array_values($items) as $index => $item) {
				foreach ($tags as $tag => $rest) {
					$value = $this->lookup(data: (array) $item, path: $rest) ?? '';
					$processor->setValue($tag.'#'.($index + 1), $this->escape(value: $value));
				}
			}
		}//end foreach
	}//end fillRows()

	/**
	 * The values of one block item, for every tag the document has.
	 *
	 * @param array<int, string>   $tags The document's tags.
	 * @param string               $name The block's name.
	 * @param mixed                $item The item.
	 * @param array<string, mixed> $data The data context.
	 *
	 * @return array<string, string>
	 */
	private function itemValues(array $tags, string $name, mixed $item, array $data): array {
		$values = [];
		foreach (array_unique($tags) as $tag) {
			if ($tag === $name || str_starts_with($tag, '/') === true || str_starts_with($tag, self::FRAGMENT_PREFIX) === true) {
				continue;
			}

			$value = null;
			if (is_array($item) === true) {
				$value = $this->lookup(data: $item, path: $tag);
				if ($value === null && str_starts_with($tag, $name.'.') === true) {
					$value = $this->lookup(data: $item, path: substr($tag, strlen($name) + 1));
				}

				if ($value === null && str_contains($tag, '.') === true) {
					$value = $this->lookup(data: $item, path: substr($tag, (int) strpos($tag, '.') + 1));
				}
			}

			$value ??= $this->lookup(data: $data, path: $tag);
			if ($value !== null) {
				// Cloned blocks are written without PhpWord's own escaping.
				$values[$tag] = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
			}
		}//end foreach

		return $values;
	}//end itemValues()

	/**
	 * Whether a list holds records (associative arrays).
	 *
	 * @param array<int, mixed> $list The list.
	 *
	 * @return bool
	 */
	private function isListOfRecords(array $list): bool {
		foreach ($list as $item) {
			if (is_array($item) === false || ($item !== [] && array_is_list($item) === true)) {
				return false;
			}
		}

		return true;
	}//end isListOfRecords()

	/**
	 * The document's field tags still to fill: no block closings, no fragments.
	 *
	 * @param TemplateProcessor $processor The document.
	 *
	 * @return array<int, string>
	 */
	private function variables(TemplateProcessor $processor): array {
		$tags = [];
		foreach ($processor->getVariables() as $variable) {
			$tag = (string) $variable;
			if ($tag === '' || str_starts_with($tag, '/') === true) {
				continue;
			}

			$tags[] = $tag;
		}

		$tags = array_values(array_unique($tags));
		$fragments = array_values(array_filter($tags, static fn (string $tag): bool => str_starts_with($tag, self::FRAGMENT_PREFIX) === true));
		$fields = array_values(array_filter($tags, static fn (string $tag): bool => str_starts_with($tag, self::FRAGMENT_PREFIX) === false));

		// Fragments are asked for first by fillFragments(); fields by the rest.
		return array_merge($fragments, $fields);
	}//end variables()

	/**
	 * The raw value at a dotted path, or null.
	 *
	 * @param array<string, mixed> $data The data.
	 * @param string               $path The dotted path.
	 *
	 * @return mixed
	 */
	private function raw(array $data, string $path): mixed {
		if (array_key_exists($path, $data) === true) {
			return $data[$path];
		}

		$current = $data;
		foreach (explode('.', $path) as $segment) {
			if (is_array($current) === false || array_key_exists($segment, $current) === false) {
				return null;
			}

			$current = $current[$segment];
		}

		return $current;
	}//end raw()

	/**
	 * The data path a tag reads, after the template's field mapping.
	 *
	 * @param string               $tag      The tag.
	 * @param array<string, mixed> $fieldMap Tag => data path aliases.
	 *
	 * @return string
	 */
	private function aliased(string $tag, array $fieldMap): string {
		$alias = $fieldMap[$tag] ?? null;
		if (is_string($alias) === true && trim($alias) !== '') {
			return trim($alias);
		}

		return $tag;
	}//end aliased()

	/**
	 * A value made safe for the document XML.
	 *
	 * PhpWord escapes only when its global setting says so; this escapes
	 * whenever it does not, so a value is escaped exactly once.
	 *
	 * @param string $value The value.
	 *
	 * @return string
	 */
	private function escape(string $value): string {
		if (Settings::isOutputEscapingEnabled() === true) {
			return $value;
		}

		return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
	}//end escape()
}//end class
