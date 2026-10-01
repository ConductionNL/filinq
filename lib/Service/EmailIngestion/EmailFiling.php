<?php

/**
 * Email filing
 *
 * Moves an .eml from its inbox into the folder bound to its dossier
 * (`@self.folder`), and has the existing conversion cascade write the PDF/A
 * copy beside it. Filing never waits on conversion: when the cascade fails
 * the email is filed all the same and can be converted later.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\EmailIngestion
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\EmailIngestion;

use OCA\Filinq\Service\DossierObjectRepository;
use OCA\Filinq\Service\PdfConversionService;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Files an email into its dossier and converts it.
 */
class EmailFiling {

	/**
	 * Constructor.
	 *
	 * @param DossierObjectRepository $dossiers   Reads the dossier's bound folder.
	 * @param IRootFolder             $rootFolder Resolves nodes without a user session.
	 * @param PdfConversionService    $conversion The existing conversion cascade.
	 * @param LoggerInterface         $logger     The logger.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly DossierObjectRepository $dossiers,
		private readonly IRootFolder $rootFolder,
		private readonly PdfConversionService $conversion,
		private readonly LoggerInterface $logger,
	) {

	}//end __construct()

	/**
	 * Move the email into the dossier's folder, under a free name.
	 *
	 * @param File   $email      The email in its inbox.
	 * @param string $dossierRef The dossier.
	 *
	 * @return File The email in the dossier folder.
	 *
	 * @throws EmailNotFiled When the dossier has no reachable folder, or the move fails.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-1
	 */
	public function file(File $email, string $dossierRef): File {
		$folder = $this->dossierFolder(dossierRef: $dossierRef);

		try {
			$moved = $email->move(rtrim($folder->getPath(), '/') . '/' . $folder->getNonExistingName($email->getName()));
		} catch (Throwable $e) {
			$this->logger->warning('[EmailFiling] could not move an email into its dossier', ['fileId' => $email->getId(), 'dossier' => $dossierRef, 'exception' => $e->getMessage()]);
			throw new EmailNotFiled(EmailIngestionService::REASON_FILING_FAILED);
		}

		if (($moved instanceof File) === false) {
			throw new EmailNotFiled(EmailIngestionService::REASON_FILING_FAILED);
		}

		return $moved;

	}//end file()

	/**
	 * Convert the filed email through the cascade; the PDF lands beside it.
	 *
	 * @param File $email The filed email.
	 *
	 * @return string The PDF's file id, or '' when the cascade could not convert it.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
	 */
	public function convert(File $email): string {
		try {
			return (string) $this->conversion->convertToPdfReporting(source: $email)['file']->getId();
		} catch (Throwable $e) {
			$this->logger->info('[EmailFiling] email filed but not converted', ['fileId' => $email->getId(), 'exception' => $e->getMessage()]);

			return '';
		}

	}//end convert()

	/**
	 * A file by id, without a user session.
	 *
	 * @param string $fileId The id.
	 *
	 * @return File|null The file, or null when it is gone.
	 *
	 * @spec openspec/changes/archive/2026-10-01-email-ingestion/tasks.md#2-3
	 */
	public function fileById(string $fileId): ?File {
		if (ctype_digit($fileId) === false) {
			return null;
		}

		$node = $this->rootFolder->getFirstNodeById((int) $fileId);
		if (($node instanceof File) === false) {
			return null;
		}

		return $node;

	}//end fileById()

	/**
	 * The folder bound to the dossier.
	 *
	 * @param string $dossierRef The dossier.
	 *
	 * @return Folder The folder.
	 *
	 * @throws EmailNotFiled With reason dossier-folder-unavailable.
	 */
	private function dossierFolder(string $dossierRef): Folder {
		try {
			$folderRef = $this->dossiers->loadDossierContext(dossierUuid: $dossierRef)['folderRef'];
			$node = $this->rootFolder->getFirstNodeById((int) $folderRef);
		} catch (Throwable $e) {
			$this->logger->warning('[EmailFiling] the dossier of an inbox cannot be read', ['dossier' => $dossierRef, 'exception' => $e->getMessage()]);
			$node = null;
		}

		if (($node instanceof Folder) === false) {
			throw new EmailNotFiled(EmailIngestionService::REASON_NO_DOSSIER_FOLDER);
		}

		return $node;

	}//end dossierFolder()
}//end class
