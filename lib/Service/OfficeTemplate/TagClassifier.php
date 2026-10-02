<?php

/**
 * Tag classifier
 *
 * Sorts the merge tags of an office source into known (a property, or a
 * dotted path into one, of the bound schema as OpenRegister holds it),
 * fragment (`fragment:slug`) and unknown, and says whether the unknown ones
 * block the upload. The schema's properties are read from OpenRegister on
 * every call: filinq keeps no copy of them.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

use OCA\OpenRegister\Db\SchemaMapper;
use OCP\IAppConfig;
use Throwable;

/**
 * Classifies merge tags against a bound schema.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-2
 */
class TagClassifier {

	/**
	 * The app-config key of the unknown-tag severity.
	 *
	 * @var string
	 */
	public const SEVERITY_KEY = 'templates_unknown_tag_severity';

	/**
	 * The notice of a template without a bound schema.
	 *
	 * @var string
	 */
	public const NOT_VALIDATED = 'Not validated against a schema: the template has no bound schema.';

	/**
	 * Constructor.
	 *
	 * @param SchemaMapper $schemas Reads the bound schema's properties.
	 * @param IAppConfig   $config  Reads the severity.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly SchemaMapper $schemas,
		private readonly IAppConfig $config,
	) {

	}//end __construct()

	/**
	 * The tag report of a set of tags.
	 *
	 * @param string[]              $tags        The tags.
	 * @param string|null           $boundSchema The bound schema slug, or null.
	 * @param array<string, string> $fieldMap    Tag to property path aliases.
	 *
	 * @return array{validated: bool, notice?: string, severity: string, known: string[], fragment: string[], unknown: string[]}
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-2
	 */
	public function classify(array $tags, ?string $boundSchema, array $fieldMap = []): array {
		$report = ['validated' => false, 'severity' => $this->severity(), 'known' => [], 'fragment' => [], 'unknown' => []];
		$properties = $this->properties(slug: trim((string) $boundSchema));
		if ($properties === null) {
			$report['notice'] = self::NOT_VALIDATED;
		}

		if ($properties !== null) {
			$report['validated'] = true;
		}

		foreach ($tags as $tag) {
			$report[$this->bucket(tag: $tag, properties: $properties, fieldMap: $fieldMap)][] = $tag;
		}

		return $report;

	}//end classify()

	/**
	 * Whether a report refuses the upload: unknown tags under blocking severity.
	 *
	 * @param array $report A report from {@see classify()}.
	 *
	 * @return bool True when it blocks.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-2
	 */
	public function blocks(array $report): bool {
		return $report['validated'] === true && $report['severity'] === 'blocking' && $report['unknown'] !== [];

	}//end blocks()

	/**
	 * Which bucket a tag goes in.
	 *
	 * @param string                    $tag        The tag.
	 * @param array<string, mixed>|null $properties The schema properties, or null.
	 * @param array<string, string>     $fieldMap   Aliases.
	 *
	 * @return string known, fragment or unknown.
	 */
	private function bucket(string $tag, ?array $properties, array $fieldMap): string {
		if (str_starts_with($tag, 'fragment:') === true) {
			return 'fragment';
		}

		if ($properties === null) {
			return 'unknown';
		}

		$path = $fieldMap[$tag] ?? $tag;
		if ($this->pathExists(segments: explode('.', $path), properties: $properties) === true) {
			return 'known';
		}

		return 'unknown';

	}//end bucket()

	/**
	 * Whether a dotted path exists in a property tree. An object property
	 * that declares no sub-properties accepts any path below it.
	 *
	 * @param string[]             $segments   The path segments.
	 * @param array<string, mixed> $properties The properties at this level.
	 *
	 * @return bool True when it exists.
	 */
	private function pathExists(array $segments, array $properties): bool {
		$head = array_shift($segments);
		if (isset($properties[$head]) === false) {
			return false;
		}

		if ($segments === []) {
			return true;
		}

		$definition = (array) $properties[$head];
		$children = ($definition['properties'] ?? ($definition['items']['properties'] ?? null));
		if (is_array($children) === false || $children === []) {
			return true;
		}

		return $this->pathExists(segments: $segments, properties: $children);

	}//end pathExists()

	/**
	 * The properties of a schema by slug, or null when there is none.
	 *
	 * @param string $slug The schema slug.
	 *
	 * @return array<string, mixed>|null The properties.
	 */
	private function properties(string $slug): ?array {
		if ($slug === '') {
			return null;
		}

		try {
			$schema = $this->schemas->find(id: $slug);
		} catch (Throwable) {
			return null;
		}

		if (is_object($schema) === false || method_exists($schema, 'getProperties') === false) {
			return null;
		}

		return json_decode((string) json_encode($schema->getProperties()), true) ?? [];

	}//end properties()

	/**
	 * The configured severity: warning (default) or blocking.
	 *
	 * @return string The severity.
	 */
	private function severity(): string {
		if ($this->config->getValueString('filinq', self::SEVERITY_KEY, 'warning') === 'blocking') {
			return 'blocking';
		}

		return 'warning';

	}//end severity()
}//end class
