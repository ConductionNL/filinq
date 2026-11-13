<?php

/**
 * Filinq Merge Tag Classifier
 *
 * Sorts the merge tags of an office template into fields the bound schema
 * knows, text fragment references, and unknown tags.
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

/**
 * Builds the tag report stored on an office template.
 *
 * A tag is `known` when its first part is a property of the schema and every
 * further part is a declared sub-property, as far as the schema declares
 * sub-properties: a property without declared sub-properties (a reference,
 * a free object) cannot rule a deeper path out, so the path counts as known.
 * A `fragment:` tag is a text fragment. Everything else is `unknown`.
 *
 * Without a schema nothing is checked: every tag is `unvalidated` and the
 * report says so, so a template without a binding is never blocked.
 *
 * @category Service
 * @package  OCA\Filinq\Service\OfficeTemplate
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
 */
class MergeTagClassifier {

	/**
	 * The prefix of a text fragment reference.
	 */
	public const FRAGMENT_PREFIX = 'fragment:';

	/**
	 * Unknown tags are reported and the upload goes through.
	 */
	public const SEVERITY_WARNING = 'warning';

	/**
	 * Unknown tags refuse the upload.
	 */
	public const SEVERITY_BLOCKING = 'blocking';

	/**
	 * The report for a template's tags.
	 *
	 * @param array<int, string>              $tags       The merge tags.
	 * @param array<string, mixed>|null       $properties The bound schema's properties, or null without a binding.
	 * @param string                          $severity   How unknown tags are treated.
	 *
	 * @return array<string, mixed> `validated`, `severity`, `tags` (each `tag` and `status`), `unknown`, and `notice` when nothing was checked.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function report(array $tags, ?array $properties, string $severity): array {
		$severity = $this->severity(value: $severity);
		$entries = [];
		$unknown = [];
		foreach ($tags as $tag) {
			$status = $this->status(tag: $tag, properties: $properties);
			$entries[] = ['tag' => $tag, 'status' => $status];
			if ($status === 'unknown') {
				$unknown[] = $tag;
			}
		}

		$report = [
			'validated' => $properties !== null,
			'severity' => $severity,
			'tags' => $entries,
			'unknown' => $unknown,
		];
		if ($properties === null) {
			$report['notice'] = 'Not validated against a schema: the template is not bound to one.';
		}

		return $report;
	}//end report()

	/**
	 * Whether a report refuses the upload.
	 *
	 * @param array<string, mixed> $report A report from report().
	 *
	 * @return bool True for unknown tags on a checked template under blocking severity.
	 *
	 * @spec openspec/changes/office-template-authoring/specs/office-template-authoring/spec.md
	 */
	public function blocks(array $report): bool {
		return ($report['validated'] ?? false) === true
			&& ($report['severity'] ?? '') === self::SEVERITY_BLOCKING
			&& ($report['unknown'] ?? []) !== [];
	}//end blocks()

	/**
	 * A configured severity, read as warning unless it is exactly blocking.
	 *
	 * @param string $value The configured value.
	 *
	 * @return string The severity.
	 */
	private function severity(string $value): string {
		if (strtolower(trim($value)) === self::SEVERITY_BLOCKING) {
			return self::SEVERITY_BLOCKING;
		}

		return self::SEVERITY_WARNING;
	}//end severity()

	/**
	 * The status of one tag.
	 *
	 * @param string                    $tag        The tag.
	 * @param array<string, mixed>|null $properties The schema's properties, or null.
	 *
	 * @return string `fragment`, `known`, `unknown` or `unvalidated`.
	 */
	private function status(string $tag, ?array $properties): string {
		if (str_starts_with($tag, self::FRAGMENT_PREFIX) === true) {
			return 'fragment';
		}

		if ($properties === null) {
			return 'unvalidated';
		}

		if ($this->pathExists(parts: explode('.', $tag), properties: $properties) === true) {
			return 'known';
		}

		return 'unknown';
	}//end status()

	/**
	 * Whether a dotted path names a property, as far as the schema declares it.
	 *
	 * @param array<int, string>   $parts      The path's parts.
	 * @param array<string, mixed> $properties The properties at this level.
	 *
	 * @return bool
	 */
	private function pathExists(array $parts, array $properties): bool {
		$name = array_shift($parts);
		if ($name === null || array_key_exists($name, $properties) === false) {
			return false;
		}

		if ($parts === []) {
			return true;
		}

		$definition = $properties[$name];
		$nested = null;
		if (is_array($definition) === true) {
			$nested = ($definition['properties'] ?? ($definition['items']['properties'] ?? null));
		}

		if (is_array($nested) === false || $nested === []) {
			return true;
		}

		return $this->pathExists(parts: $parts, properties: $nested);
	}//end pathExists()
}//end class
