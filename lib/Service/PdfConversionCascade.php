<?php

/**
 * PDF Conversion Cascade
 *
 * One untagged run of {@see PdfConversionService::convertToPdfReporting()}:
 * walks the backends in order, records why each one that cannot take the
 * file was skipped or failed, and returns the first PDF. Built per call by
 * the service and never handed outside it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
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

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\ConversionBackendInterface;
use OCP\Files\File;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The backend cascade for one source file; first success wins.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
 */
final class PdfConversionCascade {
	use StripsFileExtension;

	/**
	 * The attempt records of the backends tried so far.
	 *
	 * @var array<int, array{name: string, available: bool, supports: bool, reason: string}>
	 */
	private array $attempts = [];

	/**
	 * The source MIME type.
	 *
	 * @var string
	 */
	private readonly string $mimeType;

	/**
	 * The source extension, or an empty string.
	 *
	 * @var string
	 */
	private readonly string $ext;

	/**
	 * Constructor.
	 *
	 * @param array<int, mixed> $backends Ordered list of backends; first success wins.
	 * @param LoggerInterface   $logger   Logger for per-attempt diagnostics.
	 * @param File              $source   The file to convert.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly array $backends,
		private readonly LoggerInterface $logger,
		private readonly File $source,
	) {
		$this->mimeType = (string)$source->getMimeType();
		$this->ext = $this->extractExtension(name: $source->getName());

	}//end __construct()

	/**
	 * Walk the cascade.
	 *
	 * @return array{file: File, backend: string} The PDF and the backend that produced it.
	 *
	 * @throws ConversionFailedException When no backend in the cascade succeeded.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	public function run(): array {
		foreach ($this->backends as $backend) {
			if ($backend instanceof ConversionBackendInterface === false) {
				// Defensive — DI should only register interface
				// implementations; skip anything else without crashing
				// the cascade.
				continue;
			}

			$result = $this->tryBackend(backend: $backend);
			if ($result !== null) {
				return $result;
			}
		}

		// No backend succeeded — emit a structured failure.
		throw new ConversionFailedException(
			message: 'Conversion to PDF failed; no backend in the cascade succeeded.',
			attempts: $this->attempts
		);

	}//end run()

	/**
	 * Convert with one backend, or record why it was skipped or failed.
	 *
	 * @param ConversionBackendInterface $backend The backend.
	 *
	 * @return array{file: File, backend: string}|null The PDF, or null to try the next backend.
	 */
	private function tryBackend(ConversionBackendInterface $backend): ?array {
		$skipped = $this->unusableBackendAttempt(backend: $backend);
		if ($skipped !== null) {
			$this->attempts[] = $skipped;
			return null;
		}

		try {
			return $this->convertWith(backend: $backend);
		} catch (Throwable $e) {
			$this->attempts[] = $this->failedBackendAttempt(backendName: $backend->name(), exception: $e);
			return null;
		}

	}//end tryBackend()

	/**
	 * The attempt record of a backend that cannot take this file, or null
	 * when it is available and supports the type.
	 *
	 * @param ConversionBackendInterface $backend The backend.
	 *
	 * @return array{name: string, available: bool, supports: bool, reason: string}|null The skip record.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	private function unusableBackendAttempt(ConversionBackendInterface $backend): ?array {
		if ($backend->isAvailable() === false) {
			return [
				'name' => $backend->name(),
				'available' => false,
				'supports' => false,
				'reason' => 'backend disabled or prerequisites not present',
			];
		}

		if ($backend->canHandle($this->mimeType, $this->ext) === true) {
			return null;
		}

		return $this->unsupportedAttempt(backend: $backend);

	}//end unusableBackendAttempt()

	/**
	 * The attempt record of an available backend that does not take this type.
	 *
	 * @param ConversionBackendInterface $backend The backend.
	 *
	 * @return array{name: string, available: bool, supports: bool, reason: string} The skip record.
	 */
	private function unsupportedAttempt(ConversionBackendInterface $backend): array {
		$extLabel = $this->ext;
		if ($this->ext === '') {
			$extLabel = '(none)';
		}

		return [
			'name' => $backend->name(),
			'available' => true,
			'supports' => false,
			'reason' => sprintf('backend does not support MIME %s / extension %s', $this->mimeType, $extLabel),
		];

	}//end unsupportedAttempt()

	/**
	 * Convert with one backend and log which one produced the PDF.
	 *
	 * @param ConversionBackendInterface $backend The backend.
	 *
	 * @return array{file: File, backend: string} The PDF and the backend that produced it.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	private function convertWith(ConversionBackendInterface $backend): array {
		$result = $backend->convert($this->source);
		$this->logger->info(
			'[PdfConversionService] Conversion succeeded',
			[
				'backend' => $backend->name(),
				'source' => $this->source->getPath(),
				'output' => $result->getPath(),
			]
		);

		return [
			'file' => $result,
			'backend' => $backend->name(),
		];

	}//end convertWith()

	/**
	 * Log a failed backend and return its attempt record; the cascade falls
	 * through to the next backend.
	 *
	 * @param string    $backendName The backend name.
	 * @param Throwable $exception   The failure.
	 *
	 * @return array{name: string, available: bool, supports: bool, reason: string} The attempt record.
	 *
	 * @spec openspec/specs/document-editing/spec.md#requirement-conversion-routes-through-the-nextcloud-conversion-broker
	 */
	private function failedBackendAttempt(string $backendName, Throwable $exception): array {
		$this->logFailure(backendName: $backendName, exception: $exception);

		return [
			'name' => $backendName,
			'available' => true,
			'supports' => true,
			'reason' => $exception->getMessage(),
		];

	}//end failedBackendAttempt()

	/**
	 * Log that a backend failed and the cascade falls through.
	 *
	 * @param string    $backendName The backend name.
	 * @param Throwable $exception   The failure.
	 *
	 * @return void
	 */
	private function logFailure(string $backendName, Throwable $exception): void {
		$this->logger->warning(
			'[PdfConversionService] Backend failed; falling through',
			[
				'backend' => $backendName,
				'source' => $this->source->getPath(),
				'exception' => get_class($exception),
				'message' => $exception->getMessage(),
			]
		);

	}//end logFailure()
}//end class
