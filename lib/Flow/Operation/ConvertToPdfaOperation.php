<?php

/**
 * Filinq Convert to PDF/A Flow Operation
 *
 * Writes a PDF/A-3 copy of a file through the existing conversion services: a
 * PDF is converted directly, an office or text file is first converted to PDF.
 * The copy is a new, uniquely named file beside the source or in the rule's
 * target folder inside the owner's own tree. The source is never modified,
 * replaced or deleted.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow\Operation;

use OCA\Filinq\Kernel\Contracts\Pdfa3ConversionServiceInterface;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\FlowOperationTrigger;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCA\Filinq\Service\FileUploadService;
use OCA\Filinq\Service\PdfConversionService;
use Throwable;
use UnexpectedValueException;

/**
 * The Convert to PDF/A operation.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
 */
class ConvertToPdfaOperation extends AbstractFileOperation {

	/**
	 * The suffix of the PDF/A copy: `besluit.docx` becomes `besluit.pdfa.pdf`.
	 */
	public const SUFFIX = '.pdfa.pdf';

	/**
	 * Reason: a source that could not be turned into PDF/A.
	 */
	public const REASON_CONVERSION = 'conversion_error';

	/**
	 * Reason: the target folder could not be used.
	 */
	public const REASON_TARGET = 'target_folder_unavailable';

	/**
	 * The longest target folder path accepted.
	 */
	private const MAX_TARGET = 255;

	/**
	 * Constructor.
	 *
	 * @param IL10N                $l10n         Translates the name and description.
	 * @param IURLGenerator        $urlGenerator Builds the icon URL.
	 * @param FlowOperationTrigger $trigger      Queues the run.
	 * @param Pdfa3ConversionServiceInterface $pdfa    Converts a PDF to PDF/A-3.
	 * @param PdfConversionService            $pdf     Converts other formats to PDF first.
	 * @param FileUploadService               $uploads Picks a free file name.
	 *
	 * @return void
	 */
	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		FlowOperationTrigger $trigger,
		private readonly Pdfa3ConversionServiceInterface $pdfa,
		private readonly PdfConversionService $pdf,
		private readonly FileUploadService $uploads,
	) {
		parent::__construct(l10n: $l10n, urlGenerator: $urlGenerator, trigger: $trigger);

	}//end __construct()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The key.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function key(): string {
		return 'pdfa';

	}//end key()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated name.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Convert to PDF/A');

	}//end getDisplayName()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated description.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDescription(): string {
		return $this->l10n->t('Save an archival PDF/A copy of a document next to it or in a folder you choose; the original stays as it is');

	}//end getDescription()

	/**
	 * Only `targetFolder` is accepted: a relative path without `..` segments.
	 *
	 * @param array<string, mixed> $config The decoded options.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException For an unknown option or an unsafe path.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	protected function validateConfig(array $config): void {
		if (array_diff(array_keys($config), ['targetFolder']) !== []) {
			throw new UnexpectedValueException($this->l10n->t('Only a target folder can be set for this operation'));
		}

		$target = $config['targetFolder'] ?? '';
		if (is_string($target) === false || $this->safeSegments(path: $target) === null) {
			throw new UnexpectedValueException(
				$this->l10n->t('The target folder must be a folder path inside your own files, without ".."')
			);
		}

	}//end validateConfig()

	/**
	 * {@inheritDoc}
	 *
	 * @param File                 $file      The source.
	 * @param array<string, mixed> $config    `targetFolder`, optional.
	 * @param Folder               $ownerHome The owner's home, the root of the target folder.
	 *
	 * @return FlowOperationOutcome The outcome.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
	 */
	public function execute(File $file, array $config, Folder $ownerHome): FlowOperationOutcome {
		try {
			$folder = $this->targetFolder(source: $file, target: (string) ($config['targetFolder'] ?? ''), ownerHome: $ownerHome);
		} catch (Throwable $e) {
			return FlowOperationOutcome::failed(
				reason: self::REASON_TARGET,
				message: $this->l10n->t('The target folder could not be used: %s', [$e->getMessage()])
			);
		}

		$intermediate = null;
		try {
			$pdfSource = $file;
			if ($file->getMimeType() !== 'application/pdf') {
				$this->refuseToReplaceSibling(source: $file);
				$intermediate = $this->pdf->convertToPdf(source: $file);
				$pdfSource = $intermediate;
			}

			$converted = $this->pdfa->convertExistingPdf(
				source: $pdfSource,
				metadata: ['title' => pathinfo($file->getName(), PATHINFO_FILENAME)]
			);
			$name = $this->uploads->resolveUniqueFileName(folder: $folder, fileName: $this->outputName(source: $file));
			$output = $folder->newFile($name, $converted['content']);
		} catch (Throwable $e) {
			return FlowOperationOutcome::failed(reason: self::REASON_CONVERSION, message: $e->getMessage());
		} finally {
			$this->discard(intermediate: $intermediate);
		}

		$producedFileId = (int) $output->getId();

		return FlowOperationOutcome::succeeded(
			summary: [
				'producedFileId' => $producedFileId,
				'conformance' => (string) ($converted['conformance'] ?? ''),
				'pages' => (int) ($converted['pages'] ?? 0),
				'verified' => (string) ($converted['verified'] ?? ''),
			],
			producedFileId: $producedFileId
		);

	}//end execute()

	/**
	 * The name of the PDF/A copy.
	 *
	 * @param File $source The source.
	 *
	 * @return string The name.
	 */
	private function outputName(File $source): string {
		return pathinfo($source->getName(), PATHINFO_FILENAME) . self::SUFFIX;

	}//end outputName()

	/**
	 * The folder the copy goes in: beside the source, or the target folder
	 * under the owner's home, created when absent.
	 *
	 * @param File   $source    The source.
	 * @param string $target    The configured target folder.
	 * @param Folder $ownerHome The owner's home.
	 *
	 * @return Folder The folder.
	 *
	 * @throws UnexpectedValueException When the path is unsafe or names a file.
	 */
	private function targetFolder(File $source, string $target, Folder $ownerHome): Folder {
		$segments = $this->safeSegments(path: $target);
		if ($segments === null) {
			throw new UnexpectedValueException('unsafe path');
		}

		if ($segments === []) {
			return $source->getParent();
		}

		$folder = $ownerHome;
		foreach ($segments as $segment) {
			if ($folder->nodeExists($segment) === false) {
				$folder = $folder->newFolder($segment);
				continue;
			}

			$next = $folder->get($segment);
			if ($next instanceof Folder === false) {
				throw new UnexpectedValueException($this->l10n->t('%s is a file, not a folder', [$segment]));
			}

			$folder = $next;
		}

		return $folder;

	}//end targetFolder()

	/**
	 * The segments of a relative folder path, or null for an unsafe one.
	 *
	 * @param string $path The path.
	 *
	 * @return array<int, string>|null The segments; [] for none.
	 */
	private function safeSegments(string $path): ?array {
		if (strlen($path) > self::MAX_TARGET || str_contains($path, "\0") === true || str_contains($path, '\\') === true) {
			return null;
		}

		$segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $s): bool => $s !== '' && $s !== '.'));
		if (in_array('..', $segments, true) === true) {
			return null;
		}

		return $segments;

	}//end safeSegments()

	/**
	 * The PDF conversion writes `<name>.pdf` beside the source and deletes a
	 * file of that name first. A Flow run must never remove a file a person
	 * made, so it refuses when one is there.
	 *
	 * @param File $source The non-PDF source.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException When a PDF of that name already exists.
	 */
	private function refuseToReplaceSibling(File $source): void {
		$sibling = pathinfo($source->getName(), PATHINFO_FILENAME) . '.pdf';
		if ($source->getParent()->nodeExists($sibling) === true) {
			throw new UnexpectedValueException(
				$this->l10n->t('A file named %s already exists beside the document; it is left alone and no PDF/A copy was made', [$sibling])
			);
		}

	}//end refuseToReplaceSibling()

	/**
	 * Remove the intermediate PDF this run wrote; the source is never touched.
	 *
	 * @param File|null $intermediate The intermediate PDF.
	 *
	 * @return void
	 */
	private function discard(?File $intermediate): void {
		if ($intermediate === null) {
			return;
		}

		try {
			$intermediate->delete();
		} catch (Throwable) {
			// An intermediate left behind is untidy, not wrong: it is a copy.
		}

	}//end discard()
}//end class
