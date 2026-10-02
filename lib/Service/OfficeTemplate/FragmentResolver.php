<?php

/**
 * Fragment resolver
 *
 * Turns `${fragment:slug}` references into the fragment's text, before any
 * merge tag is filled. The `${field}` tags inside a fragment are filled from
 * the same data, one level deep: a fragment inside a fragment is not
 * expanded. A fragment that does not exist becomes a visible
 * `[ontbrekende bouwsteen: slug]` marker and a warning, never an empty spot.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\OfficeTemplate
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\OfficeTemplate;

/**
 * Resolves fragment references to filled text.
 *
 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
 */
class FragmentResolver {

	/**
	 * A fragment reference.
	 *
	 * @var string
	 */
	public const REFERENCE = '/\$\{fragment:([a-z0-9][a-z0-9-]*)\}/';

	/**
	 * A merge tag inside a fragment.
	 *
	 * @var string
	 */
	private const FIELD = '/\$\{([^}:]+)\}/';

	/**
	 * Constructor.
	 *
	 * @param TemplateObjectRepository $repository The textFragment objects.
	 * @param DataPath                 $dataPath   Reads dotted paths from the data.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly TemplateObjectRepository $repository,
		private readonly DataPath $dataPath,
	) {

	}//end __construct()

	/**
	 * The slugs a text references.
	 *
	 * @param string $text Template content or a list of tags joined.
	 *
	 * @return string[] The slugs, without duplicates.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function referencedSlugs(string $text): array {
		preg_match_all(self::REFERENCE, $text, $matches);

		return array_values(array_unique($matches[1]));

	}//end referencedSlugs()

	/**
	 * The filled text of each referenced fragment.
	 *
	 * @param string[]             $slugs     The slugs.
	 * @param string               $namespace The template's namespace.
	 * @param array<string, mixed> $data      The data context.
	 *
	 * @return array{texts: array<string, string>, warnings: string[], used: array<int, array{slug: string, id: string}>}
	 *               texts by slug; a missing one holds its marker.
	 *
	 * @spec openspec/changes/office-template-authoring/tasks.md#2-4
	 */
	public function resolve(array $slugs, string $namespace, array $data): array {
		$texts = [];
		$warnings = [];
		$used = [];
		$system = $this->repository->forSchema(schema: 'textFragment')->asSystem();
		foreach ($slugs as $slug) {
			$rows = $system->search(filters: ['slug' => $slug, 'namespace' => $namespace]);
			if ($rows === []) {
				$texts[$slug] = '[ontbrekende bouwsteen: ' . $slug . ']';
				$warnings[] = 'Text fragment "' . $slug . '" does not exist in namespace ' . $namespace . '; the document shows a marker at its place.';
				continue;
			}

			$filled = $this->fill(text: (string) ($rows[0]['content'] ?? ''), data: $data);
			$texts[$slug] = $filled['text'];
			$warnings = array_merge($warnings, $filled['warnings']);
			$used[] = ['slug' => $slug, 'id' => (string) $rows[0]['uuid']];
		}

		return ['texts' => $texts, 'warnings' => $warnings, 'used' => $used];

	}//end resolve()

	/**
	 * Fill the `${field}` tags of a fragment from the data. A nested
	 * `${fragment:...}` stays as it is (one level deep).
	 *
	 * @param string               $text The fragment content.
	 * @param array<string, mixed> $data The data context.
	 *
	 * @return array{text: string, warnings: string[]}
	 */
	private function fill(string $text, array $data): array {
		$warnings = [];
		$filled = preg_replace_callback(
			self::FIELD,
			function (array $match) use ($data, &$warnings): string {
				$value = $this->dataPath->scalar(data: $data, path: trim($match[1]));
				if ($value === null) {
					$warnings[] = 'No value for tag ' . trim($match[1]) . ' in a text fragment; it is left empty.';
					return '';
				}

				return $value;
			},
			$text
		);

		return ['text' => (string) $filled, 'warnings' => $warnings];

	}//end fill()
}//end class
