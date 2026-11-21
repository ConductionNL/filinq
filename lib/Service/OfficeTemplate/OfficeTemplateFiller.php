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
use OCA\Filinq\Service\Office\OfficeChartFiller;
use OCA\Filinq\Service\Office\OfficeImageFiller;
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
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity) The complexity is the sum of small independent
 * steps of one office template (tags, fragments, conditions, tables).
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
	 * Constructor.
	 *
	 * Without the chart or image filler a `${chart:key}` or `${image:key}`
	 * tag is an ordinary merge tag: left empty and named in the warnings.
	 *
	 * @param OfficeChartFiller|null $charts Fills `${chart:key}` with a native chart from the `charts` data entry.
	 * @param OfficeImageFiller|null $images Fills `${image:key}` with an image from the `images` data entry.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
	 */
	public function __construct(
		private readonly ?OfficeChartFiller $charts = null,
		private readonly ?OfficeImageFiller $images = null,
	) {
	}//end __construct()

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
	 * @spec openspec/changes/office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
	 */
	public function fill(string $docx, array $data, array $fragments=[], array $fieldMap=[]): array {
		$run = new OfficeFillRun(processor: $this->open(docx: $docx), data: $data, fragments: $fragments, fieldMap: $fieldMap);
		$this->fillChartsAndImages(run: $run);
		$this->fillFragments(run: $run);
		$this->fillBlocks(run: $run);
		$this->fillRows(run: $run);
		foreach ($this->variables(processor: $run->processor) as $tag) {
			$value = $this->lookup(data: $data, path: $run->aliased(tag: $tag));
			if ($value === null) {
				$run->warnings[] = 'No value for merge tag "'.$tag.'"; it is left empty.';
				$value = '';
			}

			$run->processor->setValue($tag, $this->escape(value: $value));
		}

		return [
			'docx'      => $this->bytes(processor: $run->processor),
			'warnings'  => array_values(array_unique($run->warnings)),
			'fragments' => $run->fragmentsUsed,
		];
	}//end fill()

	/**
	 * Place the native charts and the images before the plain tags are filled.
	 *
	 * They go first because they replace their own `${chart:key}` and
	 * `${image:key}` tags; the plain pass would otherwise empty them.
	 *
	 * @param OfficeFillRun $run The fill in progress.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/office-charts-and-images/specs/template-charts/spec.md#REQ-DDTCH-003
	 */
	private function fillChartsAndImages(OfficeFillRun $run): void {
		if ($this->charts !== null) {
			array_push($run->warnings, ...$this->charts->fill(processor: $run->processor, charts: self::entry(data: $run->data, key: 'charts')));
		}

		if ($this->images !== null) {
			array_push($run->warnings, ...$this->images->fill(processor: $run->processor, images: self::entry(data: $run->data, key: 'images')));
		}
	}//end fillChartsAndImages()

	/**
	 * One keyed entry of the data context, or none.
	 *
	 * @param array<string, mixed> $data The data context.
	 * @param string               $key  The entry.
	 *
	 * @return array<string, mixed> The entry when it is a map, else empty.
	 */
	private static function entry(array $data, string $key): array {
		$entry = ($data[$key] ?? []);
		if (is_array($entry) === false) {
			return [];
		}

		return $entry;
	}//end entry()

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
		if (is_bool($value) === true) {
			return ['nee', 'ja'][(int) $value];
		}

		if (is_scalar($value) === true) {
			return (string) $value;
		}

		return $this->scalarList(value: $value);
	}//end lookup()

	/**
	 * A list of scalars as comma-separated text, else null.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string|null
	 */
	private function scalarList(mixed $value): ?string {
		if (is_array($value) === false || array_is_list($value) === false) {
			return null;
		}

		$items = array_filter($value, static fn (mixed $item): bool => is_scalar($item));
		if (count($items) !== count($value)) {
			return null;
		}

		return implode(', ', array_map('strval', $items));
	}//end scalarList()

	/**
	 * Read a DOCX into a template processor.
	 *
	 * @param string $docx The DOCX.
	 *
	 * @return TemplateProcessor
	 *
	 * @throws Exception When the DOCX cannot be read (422).
	 */
	private function open(string $docx): TemplateProcessor {
		$path = (string) tempnam(sys_get_temp_dir(), 'filinq-fill-');
		file_put_contents($path, $docx);
		try {
			return new TemplateProcessor($path);
		} catch (Throwable) {
			throw new Exception(message: 'The template source could not be read as a DOCX document.', code: 422);
		} finally {
			unlink($path);
		}
	}//end open()

	/**
	 * The filled document's bytes.
	 *
	 * @param TemplateProcessor $processor The document.
	 *
	 * @return string
	 */
	private function bytes(TemplateProcessor $processor): string {
		$out = (string) tempnam(sys_get_temp_dir(), 'filinq-filled-');
		$processor->saveAs($out);
		$bytes = (string) file_get_contents($out);
		unlink($out);

		return $bytes;
	}//end bytes()

	/**
	 * Fill a fragment's own merge tags from the data, one level deep.
	 *
	 * A fragment reference inside a fragment is not expanded: it is removed
	 * and named in the warnings, so fragments cannot loop.
	 *
	 * @param string        $text The fragment text.
	 * @param OfficeFillRun $run  The fill.
	 *
	 * @return string The filled text; its warnings go to the run.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md#requirement-reusable-text-fragments-bouwstenen-resolve-in-templates-req-ddota-004
	 */
	private function fillFragmentText(string $text, OfficeFillRun $run): string {
		return (string) preg_replace_callback(
			'/\$\{([^{}]+)\}/',
			function (array $match) use ($run): string {
				$tag = trim($match[1]);
				if (str_starts_with($tag, self::FRAGMENT_PREFIX) === true) {
					$run->warnings[] = 'A text fragment cannot contain another fragment ("'.$tag.'"); it is left out.';
					return '';
				}

				$value = $this->lookup(data: $run->data, path: $run->aliased(tag: $tag));
				if ($value === null) {
					$run->warnings[] = 'No value for merge tag "'.$tag.'"; it is left empty.';
					return '';
				}

				return $value;
			},
			$text
		);
	}//end fillFragmentText()

	/**
	 * Replace every fragment reference by its filled text or a marker.
	 *
	 * @param OfficeFillRun $run The fill.
	 *
	 * @return void
	 */
	private function fillFragments(OfficeFillRun $run): void {
		foreach ($this->variables(processor: $run->processor) as $tag) {
			if (str_starts_with($tag, self::FRAGMENT_PREFIX) === false) {
				continue;
			}

			$slug = trim(substr($tag, strlen(self::FRAGMENT_PREFIX)));
			$text = sprintf(self::MISSING_FRAGMENT, $slug);
			if (isset($run->fragments[$slug]) === false) {
				$run->warnings[] = 'Text fragment "'.$slug.'" does not exist; a marker is shown in its place.';
			}

			if (isset($run->fragments[$slug]) === true) {
				$text = $this->fillFragmentText(text: $run->fragments[$slug], run: $run);
				$run->fragmentsUsed[] = $slug;
			}

			$run->processor->setValue($tag, $this->escape(value: $text));
		}
	}//end fillFragments()

	/**
	 * Repeat every `${name}`…`${/name}` block once per item of its list.
	 *
	 * An empty or absent list removes the block; a yes/no keeps it once or
	 * removes it. Inside a block a tag reads from the item first, with or
	 * without the block's or item's first name segment, then from the data.
	 *
	 * @param OfficeFillRun $run The fill.
	 *
	 * @return void
	 */
	private function fillBlocks(OfficeFillRun $run): void {
		$raw = $run->processor->getVariables();
		// The tags as they stand before any block is cloned.
		$run->documentTags = array_values(array_unique($raw));
		foreach (array_unique($raw) as $name) {
			if (str_starts_with($name, '/') === false && in_array('/'.$name, $raw, true) === true) {
				$this->fillBlock(run: $run, name: $name);
			}
		}
	}//end fillBlocks()

	/**
	 * Repeat one block once per item of its list, or keep it once or not.
	 *
	 * @param OfficeFillRun $run  The fill.
	 * @param string        $name The block's name.
	 *
	 * @return void
	 */
	private function fillBlock(OfficeFillRun $run, string $name): void {
		$value = $this->raw(data: $run->data, path: $run->aliased(tag: $name));
		if (is_array($value) === false || $value === [] || array_is_list($value) === false) {
			// Not a list: the block is kept once when the value is present.
			$run->processor->cloneBlock($name, (int) (in_array($value, [null, false, [], ''], true) === false), true);
			return;
		}

		$tags = $this->blockTags(tags: $run->documentTags, name: $name);
		$replacements = [];
		foreach ($value as $item) {
			$values = [];
			foreach ($tags as $tag) {
				$values[$tag] = $this->itemValue(item: $item, paths: $this->itemPaths(tag: $tag, name: $name)) ?? $this->lookup(data: $run->data, path: $tag);
			}

			$replacements[] = $this->blockValues(values: $values);
		}

		$run->processor->cloneBlock($name, count($replacements), true, false, $replacements);
	}//end fillBlock()

	/**
	 * The tags a block item can fill: no block markers, no fragments.
	 *
	 * @param array<int, string> $tags The document's tags.
	 * @param string             $name The block's name.
	 *
	 * @return array<int, string>
	 */
	private function blockTags(array $tags, string $name): array {
		return array_values(
			array_filter(
				$tags,
				static fn (string $tag): bool => $tag !== $name
					&& str_starts_with($tag, '/') === false
					&& str_starts_with($tag, self::FRAGMENT_PREFIX) === false
			)
		);
	}//end blockTags()

	/**
	 * The values of one block item, escaped, without the tags that have none.
	 *
	 * Cloned blocks are written without PhpWord's own escaping.
	 *
	 * @param array<string, string|null> $values Tag => value.
	 *
	 * @return array<string, string>
	 */
	private function blockValues(array $values): array {
		return array_map(
			static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8'),
			array_filter($values, static fn (?string $value): bool => $value !== null)
		);
	}//end blockValues()

	/**
	 * The paths a tag is tried at inside a block item: as is, without the
	 * block's name, and without its own first segment.
	 *
	 * @param string $tag  The tag.
	 * @param string $name The block's name.
	 *
	 * @return array<int, string>
	 */
	private function itemPaths(string $tag, string $name): array {
		$paths = [$tag];
		if (str_starts_with($tag, $name.'.') === true) {
			$paths[] = substr($tag, strlen($name) + 1);
		}

		if (str_contains($tag, '.') === true) {
			$paths[] = substr($tag, (int) strpos($tag, '.') + 1);
		}

		return $paths;
	}//end itemPaths()

	/**
	 * A value read from one block item at the first path that has one, or null.
	 *
	 * @param mixed              $item  The item.
	 * @param array<int, string> $paths The paths to try, in order.
	 *
	 * @return string|null
	 */
	private function itemValue(mixed $item, array $paths): ?string {
		if (is_array($item) === false) {
			return null;
		}

		foreach ($paths as $path) {
			$value = $this->lookup(data: $item, path: $path);
			if ($value !== null) {
				return $value;
			}
		}

		return null;
	}//end itemValue()

	/**
	 * Repeat a table row once per item of the list its tags read from.
	 *
	 * A tag `${regels.omschrijving}` in a table row, where `regels` is a list,
	 * repeats that row for each item. A row outside a table is filled once,
	 * with the values joined, and named in no warning.
	 *
	 * @param OfficeFillRun $run The fill.
	 *
	 * @return void
	 */
	private function fillRows(OfficeFillRun $run): void {
		foreach ($this->rowGroups(run: $run) as $prefix => $tags) {
			$items = array_values((array) $this->raw(data: $run->data, path: $prefix));
			if ($this->cloneRows(processor: $run->processor, row: ['first' => (string) array_key_first($tags), 'count' => count($items)]) === false) {
				continue;
			}

			foreach ($items as $index => $item) {
				foreach ($tags as $tag => $rest) {
					$value = $this->lookup(data: (array) $item, path: $rest) ?? '';
					$run->processor->setValue($tag.'#'.($index + 1), $this->escape(value: $value));
				}
			}
		}
	}//end fillRows()

	/**
	 * The row tags grouped by the list of records they read from.
	 *
	 * @param OfficeFillRun $run The fill.
	 *
	 * @return array<string, array<string, string>> List path => tag => path inside an item.
	 */
	private function rowGroups(OfficeFillRun $run): array {
		$groups = [];
		foreach ($this->variables(processor: $run->processor) as $tag) {
			$path = $run->aliased(tag: $tag);
			$dot = strpos($path, '.');
			if ($dot === false) {
				continue;
			}

			if ($this->isListOfRecords(list: $this->raw(data: $run->data, path: substr($path, 0, $dot))) === true) {
				$groups[substr($path, 0, $dot)][$tag] = substr($path, $dot + 1);
			}
		}

		return $groups;
	}//end rowGroups()

	/**
	 * Clone a table row to a count, or delete it for none.
	 *
	 * @param TemplateProcessor              $processor The document.
	 * @param array{first: string, count: int} $row      A tag in the row and the number of rows.
	 *
	 * @return bool True when rows are to be filled; false when the row is gone
	 *              or not in a table (the scalar pass then fills it joined).
	 */
	private function cloneRows(TemplateProcessor $processor, array $row): bool {
		try {
			if ($row['count'] === 0) {
				$processor->deleteRow($row['first']);
				return false;
			}

			$processor->cloneRow($row['first'], $row['count']);
		} catch (Throwable) {
			return false;
		}

		return true;
	}//end cloneRows()

	/**
	 * Whether a value is a list that holds records (associative arrays).
	 *
	 * @param mixed $list The value.
	 *
	 * @return bool
	 */
	private function isListOfRecords(mixed $list): bool {
		return is_array($list) === true && array_is_list($list) === true && $this->allRecords(list: $list) === true;
	}//end isListOfRecords()

	/**
	 * Whether every item of a list is a record (an associative array).
	 *
	 * @param array<int, mixed> $list The list.
	 *
	 * @return bool
	 */
	private function allRecords(array $list): bool {
		foreach ($list as $item) {
			if (is_array($item) === false || ($item !== [] && array_is_list($item) === true)) {
				return false;
			}
		}

		return true;
	}//end allRecords()

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
	 * A value made safe for the document XML.
	 *
	 * PhpWord escapes only when its global setting says so; this escapes
	 * whenever it does not, so a value is escaped exactly once.
	 *
	 * @param string $value The value.
	 *
	 * @return string
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) PhpWord exposes its output-escaping setting only as the
	 * static Settings::isOutputEscapingEnabled().
	 */
	private function escape(string $value): string {
		if (Settings::isOutputEscapingEnabled() === true) {
			return $value;
		}

		return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
	}//end escape()
}//end class
