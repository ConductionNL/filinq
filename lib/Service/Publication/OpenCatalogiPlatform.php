<?php

/**
 * OpenCatalogi platform
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Publication
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Publication;

use OCA\Filinq\Kernel\Contracts\OpenCatalogiPlatformInterface;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

/**
 * What Filinq asks of OpenCatalogi besides the publication object: whether
 * it is there, its TOOI categories, and the attachment of a file. Its
 * classes and OpenRegister's file service are resolved by name, so Filinq
 * loads without them.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Publication
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/woo-publicatie-pipeline/spec.md
 */
class OpenCatalogiPlatform implements OpenCatalogiPlatformInterface {

	/**
	 * OpenRegister's file service.
	 */
	private const FILE_SERVICE = 'OCA\\OpenRegister\\Service\\FileService';

	/**
	 * OpenCatalogi's TOOI value lists.
	 */
	private const TOOI = 'OCA\\OpenCatalogi\\Service\\TooiVocabularyService';

	/**
	 * Constructor
	 *
	 * @param IAppManager        $appManager Whether OpenCatalogi is there
	 * @param ContainerInterface $container  The services resolved by name
	 * @param IRootFolder        $rootFolder The redacted copy, from the actor's files
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
		private readonly IRootFolder $rootFolder,
	) {

	}//end __construct()

	/**
	 * Whether the publication platform (OpenCatalogi) is there.
	 *
	 * @return bool True when OpenCatalogi is enabled.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function available(): bool {
		return $this->appManager->isEnabledForAnyone('opencatalogi');

	}//end available()

	/**
	 * The Woo information categories, from OpenCatalogi's own TOOI value list.
	 *
	 * The code is the list's key, OpenCatalogi's own code, which its
	 * publication schema accepts and its category sitemaps match on.
	 *
	 * @return list<array{code: string, label: string}> The categories, empty when OpenCatalogi is not there.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-3.1
	 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1.1
	 */
	public function categories(): array {
		$categories = [];
		foreach ($this->categoryList() as $code => $entry) {
			$categories[] = ['code' => (string) $code, 'label' => (string) ($entry['label'] ?? '')];
		}

		return $categories;

	}//end categories()

	/**
	 * OpenCatalogi's code for a stored category: the code itself, or the code
	 * whose TOOI URI ends in the stored basename (records from before the codes).
	 *
	 * @param string $value The stored category.
	 *
	 * @return string|null The code, or null when the value is neither or OpenCatalogi is not there.
	 *
	 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1.1
	 */
	public function toPlatformCode(string $value): ?string {
		$value = trim($value);
		if ($value === '') {
			return null;
		}

		$list = $this->categoryList();
		if (array_key_exists($value, $list) === true) {
			return (string) $value;
		}

		foreach ($list as $code => $entry) {
			if (basename((string) ($entry['uri'] ?? '')) === $value) {
				return (string) $code;
			}
		}

		return null;

	}//end toPlatformCode()

	/**
	 * OpenCatalogi's category list, keyed by its code.
	 *
	 * @return array<string, array<string, mixed>> Per code, its TOOI URI and label; empty without OpenCatalogi.
	 *
	 * @spec openspec/changes/woo-hand-off-files-its-category/tasks.md#task-1.1
	 */
	private function categoryList(): array {
		if ($this->available() === false) {
			return [];
		}

		try {
			$list = $this->container->get(self::TOOI)->informatiecategorieList();
		} catch (Throwable) {
			return [];
		}

		$byCode = [];
		foreach ((array) $list as $code => $entry) {
			if (is_array($entry) === true && (string) $code !== '') {
				$byCode[(string) $code] = $entry;
			}
		}

		return $byCode;

	}//end categoryList()

	/**
	 * Attach the redacted copy to the platform's publication, shared so the public sees it.
	 *
	 * @param string $publicationUuid The platform's publication
	 * @param string $fileName        The file name
	 * @param string $content         The bytes
	 *
	 * @return void
	 *
	 * @throws RuntimeException When OpenRegister cannot attach it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function attach(string $publicationUuid, string $fileName, string $content): void {
		try {
			$this->container->get(self::FILE_SERVICE)->addFile(
				objectEntity: $publicationUuid,
				fileName: $fileName,
				content: $content,
				share: true
			);
		} catch (Throwable $e) {
			throw new RuntimeException('The redacted copy could not be attached to the publication: ' . $e->getMessage(), 0, $e);
		}

	}//end attach()

	/**
	 * The redacted copy, read from the actor's files.
	 *
	 * @param string $fileId The copy's file id
	 * @param string $actor  Whose files
	 *
	 * @return File The copy.
	 *
	 * @throws RuntimeException When the actor cannot read it.
	 *
	 * @spec openspec/changes/archive/2026-09-29-woo-publicatie-pipeline/tasks.md#task-2.3
	 */
	public function readCopy(string $fileId, string $actor): File {
		foreach ($this->rootFolder->getUserFolder($actor)->getById((int) $fileId) as $node) {
			if ($node instanceof File) {
				return $node;
			}
		}

		throw new RuntimeException('The redacted copy ' . $fileId . ' cannot be read', 404);

	}//end readCopy()
}//end class
