<?php

/**
 * Takes one person out of one document, and proves it.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\SubjectErasure
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-3.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\SubjectErasure;

use DateTimeInterface;
use OCA\Filinq\Service\DocumentVersionService;
use OCA\Filinq\Service\FinalDocumentRepository;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\File;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * The order is the control:
 *
 * 1. OpenRegister's replaceWords writes an erased COPY beside the file, with
 *    `strict` on, so a PDF whose text the person survives in fails closed.
 * 2. The copy is read back and must not contain any value (DOCX and text
 *    formats; strict covers PDF). Only then are the original's bytes replaced.
 * 3. Nextcloud keeps the previous bytes as a version, with the person in them,
 *    so every earlier version is deleted. Until that succeeds the document
 *    counts as failed, not erased.
 *
 * A final current version is not edited (design D3): a new version beside it
 * carries the erased content and names the one it supersedes, and then the
 * superseded file's own bytes are erased while its finalisation record stays,
 * stamped with the request and a new checksum.
 *
 * The replacement is a fixed mark, never a numbered placeholder: a stable token
 * keeps the linkage between documents that the request exists to break
 * (SubjectErasureRules::treatmentErases).
 */
class SubjectErasureEraser {

	/**
	 * What an erased occurrence reads as.
	 *
	 * @var string
	 */
	public const MARK = '[VERWIJDERD]';

	private const PROCESSOR = 'OCA\OpenRegister\Service\File\DocumentProcessingHandler';

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface      $container      Resolves OpenRegister's document processor.
	 * @param DocumentVersionService  $versions       Deletes the earlier Nextcloud versions.
	 * @param FinalDocumentRepository $finalDocuments The finalisation records.
	 * @param ITimeFactory            $time           The clock.
	 */
	public function __construct(
		private readonly ContainerInterface $container,
		private readonly DocumentVersionService $versions,
		private readonly FinalDocumentRepository $finalDocuments,
		private readonly ITimeFactory $time,
	) {

	}//end __construct()

	/**
	 * Erase the values from one file in place.
	 *
	 * @param File               $file   The file.
	 * @param array<int, string> $values The values to take out.
	 *
	 * @return array{versionsDeleted: int} What was done besides the rewrite.
	 *
	 * @throws RuntimeException When the file was not erased, or was erased but an
	 *                          earlier version with the person in it survives.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-3.1
	 */
	public function erase(File $file, array $values): array {
		$copy = $this->erasedCopy(file: $file, values: $values);
		try {
			$file->putContent($copy->getContent());
		} catch (Throwable $e) {
			throw new RuntimeException('The erased content could not be written into the document: ' . $e->getMessage(), 0, $e);
		} finally {
			$this->discard(copy: $copy);
		}

		return ['versionsDeleted' => $this->versions->purgeEarlierVersions(file: $file)];

	}//end erase()

	/**
	 * Erase a document whose current version is final: supersede, then erase the superseded bytes.
	 *
	 * @param File                 $file        The final document.
	 * @param array<int, string>   $values      The values to take out.
	 * @param array<string, mixed> $record      Its current finalisation record, with `uuid`.
	 * @param string               $requestUuid The erasure request.
	 *
	 * @return array{versionsDeleted: int, newVersionFileId: int} The new version and the purge count.
	 *
	 * @throws RuntimeException When any step fails; a new version already written stays, draft.
	 *
	 * @spec openspec/changes/erase-a-person-while-the-records-stay/tasks.md#task-3.2
	 */
	public function eraseFinal(File $file, array $values, array $record, string $requestUuid): array {
		$copy = $this->erasedCopy(file: $file, values: $values);
		try {
			$parent = $file->getParent();
			$name = $parent->getNonExistingName($this->supersedingName(name: $file->getName()));
			$successor = $parent->newFile($name, $copy->getContent());
		} catch (Throwable $e) {
			$this->discard(copy: $copy);
			throw new RuntimeException('The new version could not be written beside the final document: ' . $e->getMessage(), 0, $e);
		}

		$supersededUuid = (string) ($record['uuid'] ?? '');
		$successorRecord = $this->finalDocuments->save(
			record: [
				'fileId' => (int) $successor->getId(),
				'versionLabel' => '',
				'documentName' => $successor->getName(),
				'status' => FinalDocumentRepository::STATUS_DRAFT,
				'supersedes' => $supersededUuid,
				'finalReason' => 'Subject erasure ' . $requestUuid,
				'unfrozen' => false,
			]
		);

		try {
			$file->putContent($copy->getContent());
		} catch (Throwable $e) {
			throw new RuntimeException('The superseded version\'s content could not be erased: ' . $e->getMessage(), 0, $e);
		} finally {
			$this->discard(copy: $copy);
		}

		$deleted = $this->versions->purgeEarlierVersions(file: $file);

		unset($record['uuid'], $record['@self'], $record['id']);
		$record['supersededBy'] = (string) ($successorRecord['uuid'] ?? ($successorRecord['@self']['id'] ?? ''));
		$record['fileChecksum'] = hash('sha256', $file->getContent());
		$record['erasedAt'] = $this->time->getDateTime()->format(format: DateTimeInterface::ATOM);
		$record['erasureRequest'] = $requestUuid;
		$this->finalDocuments->save(record: $record, uuid: $supersededUuid);

		return ['versionsDeleted' => $deleted, 'newVersionFileId' => (int) $successor->getId()];

	}//end eraseFinal()

	/**
	 * The erased copy beside the file, checked to hold none of the values.
	 *
	 * @param File               $file   The file.
	 * @param array<int, string> $values The values.
	 *
	 * @return File The copy; the caller deletes it.
	 *
	 * @throws RuntimeException When the copy cannot be made, or a value survives in it.
	 */
	private function erasedCopy(File $file, array $values): File {
		if ($values === []) {
			throw new RuntimeException('Nothing to erase: every occurrence in this document was excluded.');
		}

		try {
			$parent = $file->getParent();
			$extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
			$outputName = $parent->getNonExistingName('.filinq-erasure-' . bin2hex(random_bytes(6)) . '.' . $extension);
			$copy = $this->container->get(self::PROCESSOR)->replaceWords(
				$file,
				array_fill_keys($values, self::MARK),
				$outputName,
				true
			);
		} catch (Throwable $e) {
			throw new RuntimeException('The document could not be rewritten without the person: ' . $e->getMessage(), 0, $e);
		}

		$survivor = $this->survivingValue(copy: $copy, extension: $extension, values: $values);
		if ($survivor !== null) {
			$this->discard(copy: $copy);
			throw new RuntimeException('After rewriting, the document still contains one of the identifiers, so it was not changed.');
		}

		return $copy;

	}//end erasedCopy()

	/**
	 * The first value still readable in the copy, or null. PDF is checked by
	 * OpenRegister's strict mode, which throws before a copy is returned.
	 *
	 * @param File               $copy      The copy.
	 * @param string             $extension Its extension.
	 * @param array<int, string> $values    The values.
	 *
	 * @return string|null The surviving value.
	 */
	private function survivingValue(File $copy, string $extension, array $values): ?string {
		if ($extension === 'pdf') {
			return null;
		}

		$text = $copy->getContent();
		if ($extension === 'docx') {
			$text = $this->docxText(bytes: $text);
		}

		foreach ($values as $value) {
			if (mb_stripos($text, $value) !== false || mb_stripos(html_entity_decode($text), $value) !== false) {
				return $value;
			}
		}

		return null;

	}//end survivingValue()

	/**
	 * The visible text of a DOCX, every part that can carry it.
	 *
	 * @param string $bytes The DOCX bytes.
	 *
	 * @return string The text; the raw bytes when it does not open, so the check stays strict.
	 */
	private function docxText(string $bytes): string {
		$path = tempnam(sys_get_temp_dir(), 'filinq-erasure-');
		file_put_contents($path, $bytes);
		$zip = new ZipArchive();
		$text = '';
		if ($zip->open($path) === true) {
			for ($i = 0; $i < $zip->numFiles; $i++) {
				$name = (string) $zip->getNameIndex($i);
				if (str_starts_with($name, 'word/') === true && str_ends_with($name, '.xml') === true) {
					// Run boundaries split a word across tags; joining the text
					// nodes is what a reader sees.
					$text .= ' ' . strip_tags((string) $zip->getFromName($name));
				}
			}

			$zip->close();
		}

		unlink($path);
		if ($text === '') {
			return $bytes;
		}

		return $text;

	}//end docxText()

	/**
	 * Remove the working copy; a leftover copy would hold the erased content only.
	 *
	 * @param File $copy The copy.
	 *
	 * @return void
	 */
	private function discard(File $copy): void {
		try {
			$copy->delete();
		} catch (Throwable) {
			// The copy holds the erased content, so a leftover is clutter, not a leak.
			return;
		}

	}//end discard()

	/**
	 * The new version's name, keeping the extension.
	 *
	 * @param string $name The final document's name.
	 *
	 * @return string The name.
	 */
	private function supersedingName(string $name): string {
		$extension = pathinfo($name, PATHINFO_EXTENSION);
		$base = pathinfo($name, PATHINFO_FILENAME);
		if ($extension === '') {
			return $base . ' erased';
		}

		return $base . ' erased.' . $extension;

	}//end supersedingName()
}//end class
