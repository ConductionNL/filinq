<?php

/**
 * Receipt Capture Service
 *
 * Takes a receipt or invoice photo straight from a phone camera
 * (extraction-receipt-photo-and-invoice-agent D2, REQ-ERP-001, REQ-ERP-002):
 * checks the bytes against the upload policy, accepts JPEG, PNG, HEIC and
 * PDF up to 20 MB, turns a photo upright, converts HEIC to JPEG, stores the
 * file in the user's own Files under `Receipts/<yyyy>/<mm>/`, and then reads
 * it through the financial extraction pipeline. The receipt is the evidence
 * for the booking, so it is stored before it is read and stays findable.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Extraction
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Extraction;

use OCA\Filinq\Exception\ReceiptCaptureRefusedException;
use OCA\Filinq\Exception\UploadRefusedException;
use OCA\Filinq\Kernel\Contracts\FinancialExtractionServiceInterface;
use OCA\Filinq\Kernel\Contracts\UploadPolicyServiceInterface;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;

/**
 * Stores a captured receipt in the user's Files and extracts it.
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
 */
class ReceiptCaptureService {

	/**
	 * The largest photo taken, in bytes.
	 *
	 * @var int
	 */
	public const MAX_BYTES = 20971520;

	/**
	 * The folder under the user's Files that receipts are kept in.
	 *
	 * @var string
	 */
	public const FOLDER = 'Receipts';

	/**
	 * The media types taken, with the extension they are stored under.
	 *
	 * @var array<string, string>
	 */
	public const ACCEPTED = [
		'image/jpeg' => 'jpg',
		'image/png' => 'png',
		'image/heic' => 'heic',
		'image/heif' => 'heic',
		'application/pdf' => 'pdf',
	];

	/**
	 * Constructor.
	 *
	 * @param UploadPolicyServiceInterface        $uploadPolicy The upload policy that checks the bytes.
	 * @param PhotoNormaliser                     $photos       Uprights photos and converts HEIC.
	 * @param IRootFolder                         $rootFolder   The users' Files.
	 * @param FinancialExtractionServiceInterface $extraction   Reads the stored receipt.
	 * @param ITimeFactory                        $time         The capture moment.
	 */
	public function __construct(
		private readonly UploadPolicyServiceInterface $uploadPolicy,
		private readonly PhotoNormaliser $photos,
		private readonly IRootFolder $rootFolder,
		private readonly FinancialExtractionServiceInterface $extraction,
		private readonly ITimeFactory $time,
	) {
	}//end __construct()

	/**
	 * Store a captured receipt in the user's Files and extract it.
	 *
	 * @param string               $userId   The signed-in user, whose Files hold the receipt.
	 * @param string               $fileName The name the photo arrived under.
	 * @param string               $contents The bytes.
	 * @param array<string, mixed> $request  `docType`, `sourceApp` and `callbackEvent` for the extraction.
	 *
	 * @return array<string, mixed> The stored `financialExtraction`, with `capturedFileId`.
	 *
	 * @throws ReceiptCaptureRefusedException (413) When the photo is above 20 MB, (415) when its type or format is not taken.
	 * @throws UploadRefusedException When the upload policy refuses the file.
	 *
	 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#task-2.1
	 */
	public function capture(string $userId, string $fileName, string $contents, array $request): array {
		if (strlen($contents) > self::MAX_BYTES) {
			throw new ReceiptCaptureRefusedException(message: 'This photo is larger than 20 MB. Take it again at a lower resolution.', code: 413);
		}

		$detected = (string)($this->uploadPolicy->check(fileName: $fileName, contents: $contents)['detectedType'] ?? '');
		$extension = (self::ACCEPTED[$detected] ?? null);
		if ($extension === null) {
			throw new ReceiptCaptureRefusedException(message: 'Only a JPEG, PNG or HEIC photo or a PDF can be captured.', code: 415);
		}

		[$contents, $extension] = $this->normalised(contents: $contents, extension: $extension);

		$fileId = $this->store(userId: $userId, contents: $contents, extension: $extension);

		return $this->extraction->extractFinancial(
			data: [
				'fileId' => $fileId,
				'capturedFileId' => $fileId,
				'docType' => (string)($request['docType'] ?? 'receipt'),
				'sourceApp' => (string)($request['sourceApp'] ?? ''),
				'callbackEvent' => filter_var(($request['callbackEvent'] ?? false), FILTER_VALIDATE_BOOLEAN),
			],
			requestedBy: $userId
		);
	}//end capture()

	/**
	 * The photo upright, as JPEG when it arrived as HEIC.
	 *
	 * @param string $contents  The bytes.
	 * @param string $extension The extension of the detected type.
	 *
	 * @return array{0: string, 1: string} The bytes and extension to store.
	 *
	 * @throws ReceiptCaptureRefusedException (415) When a HEIC photo cannot be read on this server.
	 */
	private function normalised(string $contents, string $extension): array {
		if ($extension === 'heic') {
			return [$this->photos->heicToJpeg(heic: $contents), 'jpg'];
		}

		if ($extension === 'jpg') {
			return [$this->photos->upright(jpeg: $contents), 'jpg'];
		}

		return [$contents, $extension];
	}//end normalised()

	/**
	 * Write the receipt as `Receipts/<yyyy>/<mm>/<date>-<time>.<ext>`, never over another.
	 *
	 * @param string $userId    The user.
	 * @param string $contents  The bytes.
	 * @param string $extension The extension.
	 *
	 * @return int The new file's id.
	 */
	private function store(string $userId, string $contents, string $extension): int {
		$now = $this->time->getDateTime();
		$folder = $this->folder(
			parent: $this->rootFolder->getUserFolder($userId),
			path: [self::FOLDER, $now->format('Y'), $now->format('m')]
		);

		$base = $now->format('Y-m-d-His');
		$name = $base . '.' . $extension;
		for ($suffix = 2; $folder->nodeExists($name) === true; $suffix++) {
			$name = $base . '-' . $suffix . '.' . $extension;
		}

		return (int)$folder->newFile($name, $contents)->getId();
	}//end store()

	/**
	 * The folder at a path under a parent, made where it is missing.
	 *
	 * @param Folder             $parent The parent.
	 * @param array<int, string> $path   The folder names, outermost first.
	 *
	 * @return Folder The innermost folder.
	 */
	private function folder(Folder $parent, array $path): Folder {
		$folder = $parent;
		foreach ($path as $name) {
			if ($folder->nodeExists($name) === false) {
				$folder = $folder->newFolder($name);
				continue;
			}

			$node = $folder->get($name);
			if (($node instanceof Folder) === false) {
				throw new ReceiptCaptureRefusedException(message: 'The receipts folder cannot be written: ' . $name . ' is a file.', code: 409);
			}

			$folder = $node;
		}

		return $folder;
	}//end folder()
}//end class
