<?php

/**
 * Page Layout Service
 *
 * The paper a generated document leaves the building on: size, margins, header,
 * footer, logo, and whether the first page differs the way briefpapier usually
 * does.
 *
 * A layout is VERSIONED, and editing one makes a new version rather than
 * changing the old one. A besluit sent in March was sent on March's paper; a
 * layout edited in April that rewrote it would change what the organisation
 * says it sent, and nothing on the document would say it had changed.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Resolves, versions and applies a page layout.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
 */
class PageLayoutService {

	/**
	 * The schema holding the layouts.
	 *
	 * @var string
	 */
	public const SCHEMA = 'pageLayout';

	/**
	 * Constructor.
	 *
	 * @param DocumentObjectServiceResolver $objectResolver Resolver for OpenRegister's ObjectService.
	 * @param LoggerInterface $logger Logger for diagnostics.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * The layout a template names, at the version it names.
	 *
	 * A template that names no version gets the ACTIVE one, which is the
	 * current paper. A template that names version 2 gets version 2, even
	 * after version 3 exists: that is the whole reason versions exist.
	 *
	 * @param string $name The layout name.
	 * @param int|null $version The version it names, or null for the active one.
	 *
	 * @return array<string, mixed>|null The layout, or null when there is none.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function resolve(string $name, ?int $version = null): ?array {
		if ($name === '') {
			return null;
		}

		$versions = $this->versionsOf(name: $name);
		if ($versions === []) {
			return null;
		}

		if ($version !== null) {
			foreach ($versions as $candidate) {
				if ((int)($candidate['layoutVersion'] ?? 0) === $version) {
					return $candidate;
				}
			}

			return null;
		}

		foreach ($versions as $candidate) {
			if (($candidate['active'] ?? false) === true) {
				return $candidate;
			}
		}

		return $versions[0];

	}//end resolve()

	/**
	 * Edit a layout by writing the next version of it.
	 *
	 * The earlier version is left on disk and only marked inactive. Documents
	 * and templates naming it keep resolving to exactly what they were made
	 * with.
	 *
	 * @param string $name The layout name.
	 * @param array<string, mixed> $changes The fields to change.
	 *
	 * @return array<string, mixed> The new version.
	 *
	 * @throws RuntimeException When there is no such layout, or the write fails.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function edit(string $name, array $changes): array {
		$current = $this->resolve(name: $name);
		if ($current === null) {
			throw new RuntimeException(message: 'There is no page layout called ' . $name . '.');
		}

		unset($changes['layoutVersion'], $changes['supersedes'], $changes['name']);

		$next = array_merge($current, $changes);
		$next['name'] = $name;
		$next['layoutVersion'] = ((int)($current['layoutVersion'] ?? 1) + 1);
		$next['supersedes'] = (string)($current['uuid'] ?? '');
		$next['active'] = true;
		unset($next['uuid']);

		$stored = $this->save(layout: $next);

		$retired = $current;
		$retired['active'] = false;
		$this->save(layout: $retired, uuid: (string)($current['uuid'] ?? ''));

		return $stored;

	}//end edit()

	/**
	 * The PDF options one layout asks for.
	 *
	 * @param array<string, mixed> $layout The layout.
	 *
	 * @return array<string, mixed> The options, in the shape the render pipeline takes.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function pdfOptions(array $layout): array {
		$orientation = 'P';
		if (((string)($layout['orientation'] ?? 'portrait')) === 'landscape') {
			$orientation = 'L';
		}

		$options = [
			'format' => (string)($layout['paperSize'] ?? 'A4'),
			'orientation' => $orientation,
			'header' => (string)($layout['header'] ?? ''),
			'footer' => (string)($layout['footer'] ?? ''),
		];

		if (isset($layout['margins']) === true && is_array($layout['margins']) === true) {
			$options['margin'] = $layout['margins'];
		}

		if (($layout['firstPageDiffers'] ?? false) === true) {
			$options['firstPage'] = [
				'header' => (string)($layout['firstPageHeader'] ?? ''),
				'footer' => (string)($layout['firstPageFooter'] ?? ''),
			];
		}

		if ((int)($layout['logo'] ?? 0) > 0) {
			$options['logoFileId'] = (int)$layout['logo'];
		}

		return $options;

	}//end pdfOptions()

	/**
	 * Record on a generated document which layout version made it.
	 *
	 * @param array<string, mixed> $document The generated-document entry.
	 * @param array<string, mixed>|null $layout The layout it rendered through.
	 *
	 * @return array<string, mixed> The entry, naming the layout version.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function stamp(array $document, ?array $layout): array {
		if ($layout === null) {
			return $document;
		}

		$document['layoutId'] = (string)($layout['uuid'] ?? '');
		$document['layoutVersion'] = (int)($layout['layoutVersion'] ?? 0);

		return $document;

	}//end stamp()

	/**
	 * Every version of one layout, newest version first.
	 *
	 * @param string $name The layout name.
	 *
	 * @return array<int, array<string, mixed>> The versions.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	public function versionsOf(string $name): array {
		try {
			// 🔴 SLUGS GO THROUGH `searchObjectsBySlug`, NEVER `searchObjects`.
			// `searchObjects` answers a slug with zero rows and no error, so
			// resolve() found no version, stamp() stamped no layout, and the
			// layout list rendered empty.
			$results = $this->objectResolver->resolve()->searchObjectsBySlug(
				registerSlug: IntakeRepository::REGISTER,
				schemaSlug: self::SCHEMA,
				filters: ['name' => $name]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				message: '[PageLayoutService] could not read the page layouts',
				context: ['file' => __FILE__, 'line' => __LINE__, 'name' => $name, 'error' => $e->getMessage()]
			);

			return [];
		}

		if (is_array($results) === false) {
			return [];
		}

		$versions = [];
		foreach ($results as $result) {
			$data = $result;
			if (is_object($result) === true && method_exists($result, 'jsonSerialize') === true) {
				$data = $result->jsonSerialize();
			}

			if (is_array($data) === false) {
				continue;
			}

			$fields = $data;
			if (isset($data['object']) === true && is_array($data['object']) === true) {
				$fields = $data['object'];
			}

			$fields['uuid'] = (string)($fields['uuid'] ?? ($data['uuid'] ?? ($data['@self']['id'] ?? ($data['id'] ?? ''))));
			$versions[] = $fields;
		}

		usort(
			$versions,
			static fn (array $left, array $right): int => ((int)($right['layoutVersion'] ?? 0) <=> (int)($left['layoutVersion'] ?? 0))
		);

		return $versions;

	}//end versionsOf()

	/**
	 * Write one layout.
	 *
	 * @param array<string, mixed> $layout The layout.
	 * @param string $uuid The uuid to write under, or an empty string to create one.
	 *
	 * @return array<string, mixed> The stored layout.
	 *
	 * @throws RuntimeException When the write fails.
	 *
	 * @spec openspec/changes/documents-from-a-template/specs/document-creatie-sjablonen/spec.md
	 */
	private function save(array $layout, string $uuid = ''): array {
		unset($layout['uuid']);

		try {
			$arguments = [
				'object' => $layout,
				'register' => IntakeRepository::REGISTER,
				'schema' => self::SCHEMA,
			];
			if ($uuid !== '') {
				$arguments['uuid'] = $uuid;
			}

			$stored = $this->objectResolver->resolve()->saveObject(...$arguments);
		} catch (Throwable $e) {
			throw new RuntimeException(
				message: 'Could not store the page layout: ' . $e->getMessage(),
				code: 0,
				previous: $e
			);
		}

		if (is_array($stored) === false) {
			return $layout;
		}

		return $stored;

	}//end save()
}//end class
