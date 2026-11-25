<?php

/**
 * Vendor Render Sources
 *
 * The document generation sources integriq holds: the active ones, and one by
 * slug. Read from integriq's `source` objects in OpenRegister, so filinq needs
 * no class of integriq to list them and keeps working without integriq.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Vendor
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Vendor;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\SlugSearch;
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Lists integriq's document generation sources.
 *
 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
 */
class VendorRenderSources {

	/**
	 * The app that holds the sources and renders through them.
	 *
	 * @var string
	 */
	public const APP = 'integriq';

	/**
	 * integriq's register.
	 *
	 * @var string
	 */
	public const REGISTER = 'integriq';

	/**
	 * integriq's source schema.
	 *
	 * @var string
	 */
	public const SCHEMA = 'source';

	/**
	 * The source type of a document generation source.
	 *
	 * @var string
	 */
	public const TYPE = 'documentGeneration';

	/**
	 * Constructor.
	 *
	 * @param IAppManager                   $appManager     Tells whether integriq is enabled.
	 * @param DocumentObjectServiceResolver $objectResolver Reads integriq's sources from OpenRegister.
	 * @param LoggerInterface               $logger         Records a source list that cannot be read.
	 */
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly DocumentObjectServiceResolver $objectResolver,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Whether integriq is enabled on this instance.
	 *
	 * @return bool True when integriq can take a render.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-vendor-render-goes-to-integriq-and-never-falls-back-req-tev-002
	 */
	public function isAvailable(): bool {
		return $this->appManager->isEnabledForAnyone(self::APP);

	}//end isAvailable()

	/**
	 * The active document generation sources; none without integriq.
	 *
	 * @return list<array{id: string, slug: string, name: string}> The sources.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-the-engines-an-instance-offers-are-listed-req-tev-004
	 */
	public function active(): array {
		if ($this->isAvailable() === false) {
			return [];
		}

		$sources = [];
		foreach ($this->rows() as $row) {
			$source = $this->asSource(row: $row);
			if ($source !== null) {
				$sources[] = $source;
			}
		}

		return $sources;

	}//end active()

	/**
	 * The active source of a slug, or null when no active source carries it.
	 *
	 * @param string $slug The source slug.
	 *
	 * @return array{id: string, slug: string, name: string}|null The source.
	 *
	 * @spec openspec/changes/template-engine-renders-through-a-vendor/specs/document-creatie-sjablonen/spec.md#requirement-a-vendor-render-goes-to-integriq-and-never-falls-back-req-tev-002
	 */
	public function bySlug(string $slug): ?array {
		foreach ($this->active() as $source) {
			if ($source['slug'] === $slug) {
				return $source;
			}
		}

		return null;

	}//end bySlug()

	/**
	 * integriq's document generation source rows; none when they cannot be read.
	 *
	 * @return array<int|string, mixed> The rows.
	 */
	private function rows(): array {
		try {
			$rows = (new SlugSearch(objects: $this->objectResolver->resolve(), _rbac: false))->search(
				register: self::REGISTER,
				schema: self::SCHEMA,
				filters: ['type' => self::TYPE]
			);
		} catch (Throwable $e) {
			$this->logger->warning(
				'Filinq: integriq\'s document generation sources cannot be read: ' . $e->getMessage(),
				['app' => 'filinq']
			);
			return [];
		}

		if (is_array($rows) === false) {
			return [];
		}

		return $rows;

	}//end rows()

	/**
	 * One row as an active document generation source, or null.
	 *
	 * The search filters by type; the match, the enabled flag and the
	 * status are checked here, so a source switched off is never offered.
	 *
	 * @param mixed $row A row as the register answered it.
	 *
	 * @return array{id: string, slug: string, name: string}|null The source.
	 */
	private function asSource(mixed $row): ?array {
		if (is_object($row) === true && method_exists($row, 'jsonSerialize') === true) {
			$row = $row->jsonSerialize();
		}

		if (is_array($row) === false) {
			return null;
		}

		$self = (array) ($row['@self'] ?? []);
		$id   = (string) ($self['id'] ?? ($row['id'] ?? ($row['uuid'] ?? '')));
		$slug = trim((string) ($row['slug'] ?? ''));
		if ($id === '' || $slug === '' || ($row['type'] ?? '') !== self::TYPE) {
			return null;
		}

		if (($row['isEnabled'] ?? true) === false || ($row['status'] ?? '') === 'disabled') {
			return null;
		}

		$name = trim((string) ($row['name'] ?? ''));
		if ($name === '') {
			$name = $slug;
		}

		return ['id' => $id, 'slug' => $slug, 'name' => $name];

	}//end asSource()
}//end class
