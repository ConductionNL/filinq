<?php

/**
 * Bridge Dossier Pick-up
 *
 * Attach staged external documents to a Filinq dossier (REQ-DDZGW-006).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/zgw-document-bridge
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use InvalidArgumentException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Copies each staged file into the dossier's Nextcloud folder, the unit the
 * folder-batch anonymisation works on, then records `dossierRef` and moves
 * the document to `in_processing`. The staged original is never moved or
 * changed, and the `dossier` schema is not touched.
 *
 * Everything is checked before anything is copied: every document must be
 * staged and its staged file readable by the operator, and the dossier must
 * have a folder the operator can write to. One bad document refuses the whole
 * pick-up, so a dossier never ends up with half of what was asked.
 *
 * @spec openspec/changes/zgw-document-bridge
 */
class BridgeDossierPickUp {

	/**
	 * Constructor.
	 *
	 * @param BridgeService           $bridge     Reads and moves the external documents.
	 * @param DossierObjectRepository $dossiers   Reads the dossier and resolves its folder.
	 * @param IRootFolder             $rootFolder The file system.
	 * @param IUserSession            $session    The operator.
	 * @param LoggerInterface         $logger     Logs a document saved after its copy failed.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function __construct(
		private readonly BridgeService $bridge,
		private readonly DossierObjectRepository $dossiers,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $session,
		private readonly LoggerInterface $logger,
	) {
	}//end __construct()

	/**
	 * Attach staged documents to a dossier.
	 *
	 * @param string             $dossierUuid The dossier object's uuid.
	 * @param array<int, string> $documentIds The external documents' uuids.
	 *
	 * @return array<int, array<string, mixed>> The saved documents, each with `copiedFileId`.
	 *
	 * @throws InvalidArgumentException When no document is named, one is not staged or its file cannot be read.
	 * @throws RuntimeException         When the dossier or its folder cannot be resolved (code 404).
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	public function attach(string $dossierUuid, array $documentIds): array {
		$documentIds = array_values(
			array_unique(array_filter(array_map('strval', $documentIds), static fn (string $id): bool => trim($id) !== ''))
		);
		if ($documentIds === []) {
			throw new InvalidArgumentException('Name at least one staged document to attach.');
		}

		$folder  = $this->dossierFolder(dossierUuid: $dossierUuid);
		$planned = [];
		foreach ($documentIds as $id) {
			$document = $this->bridge->find(id: $id);
			if ((string)($document['processingStatus'] ?? '') !== 'staged') {
				throw new InvalidArgumentException(sprintf('Document %s is not staged, so it cannot be attached.', $id));
			}

			$planned[] = ['document' => $document, 'file' => $this->stagedFile(document: $document)];
		}

		$attached = [];
		foreach ($planned as $plan) {
			$file   = $plan['file'];
			$name = trim((string)($plan['document']['filename'] ?? ''));
			if ($name === '') {
				$name = $file->getName();
			}

			$target = $folder->getNonExistingName($name);
			$copy   = $file->copy(rtrim($folder->getPath(), '/').'/'.$target);
			try {
				$saved = $this->bridge->pickedUp(document: $plan['document'], dossierRef: $dossierUuid);
			} catch (Throwable $e) {
				$this->logger->warning(
					'[BridgeDossierPickUp] copied but not recorded',
					['document' => (string)($plan['document']['uuid'] ?? ''), 'copy' => $copy->getId(), 'exception' => $e->getMessage()]
				);
				throw $e;
			}

			$attached[] = $saved + ['copiedFileId' => $copy->getId()];
		}

		return $attached;
	}//end attach()

	/**
	 * The dossier's folder, under the operator's view.
	 *
	 * @param string $dossierUuid The dossier object's uuid.
	 *
	 * @return Folder
	 *
	 * @throws RuntimeException When the dossier or its folder cannot be resolved (code 404).
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	private function dossierFolder(string $dossierUuid): Folder {
		try {
			$context = $this->dossiers->loadDossierContext(dossierUuid: $dossierUuid);

			return $this->dossiers->resolveDossierFolder(folderRef: ($context['folderRef'] ?? null));
		} catch (Throwable $e) {
			throw new RuntimeException('The dossier or its folder was not found.', 404, $e);
		}
	}//end dossierFolder()

	/**
	 * The staged file of a document, under the operator's view.
	 *
	 * @param array<string, mixed> $document The external document.
	 *
	 * @return File
	 *
	 * @throws InvalidArgumentException When it has no staged file the operator can read.
	 *
	 * @spec openspec/changes/zgw-document-bridge
	 */
	private function stagedFile(array $document): File {
		$user = $this->session->getUser();
		$ref  = ($document['stagedFileRef'] ?? null);
		$node = null;
		if ($user !== null && is_numeric($ref) === true) {
			try {
				$node = ($this->rootFolder->getUserFolder($user->getUID())->getById((int)$ref)[0] ?? null);
			} catch (Throwable $e) {
				$node = null;
			}
		}

		if (($node instanceof File) === false) {
			throw new InvalidArgumentException(sprintf('The staged file of document %s cannot be read.', (string)($document['uuid'] ?? '')));
		}

		return $node;
	}//end stagedFile()
}//end class
