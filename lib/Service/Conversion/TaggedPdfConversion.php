<?php

/**
 * Tagged PDF Conversion
 *
 * One accessible (PDF/UA) run of {@see \OCA\Filinq\Service\PdfConversionService::convertToPdfReporting()}:
 * only LibreOffice can tag, so every other backend is recorded as unable and
 * never asked. Without LibreOffice the conversion fails; it never falls back
 * to an untagged PDF. Built per call by the service and never handed outside it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Conversion
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

namespace OCA\Filinq\Service\Conversion;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\StripsFileExtension;
use OCP\Files\File;

/**
 * The tagged export of one source file, written beside it as `<stem>.pdf`.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Conversion
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.1
 */
final class TaggedPdfConversion {
	use StripsFileExtension;

	/**
	 * The attempt records of the backends that could not tag.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $attempts = [];

	/**
	 * Constructor.
	 *
	 * @param array<int, mixed> $backends Ordered list of backends.
	 * @param File              $source   The file to convert.
	 * @param bool              $pdfa     Whether to keep PDF/A as well.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly array $backends,
		private readonly File $source,
		private readonly bool $pdfa,
	) {

	}//end __construct()

	/**
	 * Run the tagged conversion.
	 *
	 * @return array{file: File, backend: string} The PDF beside the source.
	 *
	 * @throws ConversionFailedException When no tagged PDF can be made.
	 *
	 * @spec openspec/changes/archive/2026-09-29-pdfua-accessible-output/tasks.md#task-1.1
	 */
	public function run(): array {
		foreach ($this->backends as $backend) {
			$result = $this->tryBackend(backend: $backend);
			if ($result !== null) {
				return $result;
			}
		}

		throw new ConversionFailedException(
			message: 'Accessible PDF output needs LibreOffice, which is not available; no untagged PDF is made instead.',
			attempts: $this->attempts,
			code: 503
		);

	}//end run()

	/**
	 * Tag with one backend, or record why it could not.
	 *
	 * @param mixed $backend The backend.
	 *
	 * @return array{file: File, backend: string}|null The PDF, or null to try the next backend.
	 */
	private function tryBackend(mixed $backend): ?array {
		if ($backend instanceof LibreOfficeHeadlessBackend === false) {
			$this->recordUntaggable(backend: $backend);
			return null;
		}

		$bytes = $this->taggedBytes(backend: $backend);
		if ($bytes === null) {
			return null;
		}

		return ['file' => $this->writeBesideSource(bytes: $bytes), 'backend' => $backend->name()];

	}//end tryBackend()

	/**
	 * Record a backend that cannot write tagged PDF; anything that is not a backend is skipped.
	 *
	 * @param mixed $backend The backend.
	 *
	 * @return void
	 */
	private function recordUntaggable(mixed $backend): void {
		if ($backend instanceof ConversionBackendInterface === true) {
			$this->attempts[] = [
				'name' => $backend->name(),
				'available' => $backend->isAvailable(),
				'supports' => false,
				'reason' => 'cannot write tagged PDF',
			];
		}

	}//end recordUntaggable()

	/**
	 * The tagged export of the source, or null (its attempts recorded) when it fails.
	 *
	 * @param LibreOfficeHeadlessBackend $backend The LibreOffice backend.
	 *
	 * @return string|null The PDF bytes.
	 */
	private function taggedBytes(LibreOfficeHeadlessBackend $backend): ?string {
		try {
			return $backend->convertTagged(
				bytes: (string) $this->source->getContent(),
				extension: $this->extractExtension(name: $this->source->getName()),
				pdfa: $this->pdfa
			);
		} catch (ConversionFailedException $e) {
			$this->attempts = array_merge($this->attempts, $e->getAttempts());
			return null;
		}

	}//end taggedBytes()

	/**
	 * Write the PDF beside the source as `<stem>.pdf`, replacing one that is there.
	 *
	 * @param string $bytes The PDF bytes.
	 *
	 * @return File The written PDF.
	 */
	private function writeBesideSource(string $bytes): File {
		$stem = $this->stripExtension(name: basename($this->source->getName()));
		$parent = $this->source->getParent();
		if ($parent->nodeExists($stem . '.pdf') === true) {
			$parent->get($stem . '.pdf')->delete();
		}

		return $parent->newFile($stem . '.pdf', $bytes);

	}//end writeBesideSource()
}//end class
