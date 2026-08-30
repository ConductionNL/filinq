<?php

/**
 * Document Comparison Service
 *
 * Post-hoc, read-only comparison of two document subjects (two versions of one
 * Nextcloud file, or two distinct files). Produces a server-computed word-level
 * structured diff. When the pair is an original document and its anonymised
 * output, diff hunks are annotated with redaction metadata from the OpenRegister
 * NER pipeline (EntityRelation rows for the source file) and the response carries
 * a redaction-completeness signal.
 *
 * The service is ephemeral: it never copies, persists, or indexes subject content
 * (search stays with OpenRegister per ADR-022). Subjects are resolved through the
 * requesting user's folder, so a file the user cannot read is indistinguishable
 * from a non-existent one (IDOR-safe per ADR-005).
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Exception\ComparisonException;
use OCA\Filinq\Service\Comparison\RedactionAnnotator;
use OCA\Filinq\Service\Comparison\WordDiffer;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service computing structured, redaction-aware document comparisons.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/document-comparison/spec.md
 */
class DocumentComparisonService {
	/**
	 * App config key for the maximum extracted text size (bytes).
	 *
	 * @var string
	 */
	private const CONFIG_MAX_TEXT_BYTES = 'comparison.max_text_bytes';

	/**
	 * Default maximum extracted text size (5 MB).
	 *
	 * @var integer
	 */
	private const DEFAULT_MAX_TEXT_BYTES = 5242880;

	/**
	 * Mime types whose bytes are directly readable as UTF-8 text.
	 *
	 * @var array<int, string>
	 */
	private const TEXT_MIME_PREFIXES = ['text/'];

	/**
	 * Discrete mime types the extractor can read as text.
	 *
	 * @var array<int, string>
	 */
	private const TEXT_MIME_TYPES = [
		'text/plain',
		'text/markdown',
		'text/html',
		'text/csv',
		'application/json',
		'application/xml',
		'text/xml',
	];

	/**
	 * Word-level structured diff computation.
	 *
	 * @var WordDiffer
	 */
	private readonly WordDiffer $differ;

	/**
	 * Redaction metadata annotation and completeness signal.
	 *
	 * @var RedactionAnnotator
	 */
	private readonly RedactionAnnotator $annotator;

	/**
	 * Constructor.
	 *
	 * The two collaborators are composed here rather than injected so the
	 * constructor signature (and therefore the DI wiring) stays unchanged.
	 *
	 * @param LoggerInterface $logger Logger for diagnostics.
	 * @param IRootFolder $rootFolder Root folder for user-scoped file access.
	 * @param IUserSession $userSession Current user session.
	 * @param IAppConfig $appConfig App configuration.
	 * @param IAppManager $appManager App manager (OpenRegister availability check).
	 * @param ContainerInterface $container DI container for lazy OR resolution.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IRootFolder $rootFolder,
		private readonly IUserSession $userSession,
		private readonly IAppConfig $appConfig,
		private readonly IAppManager $appManager,
		private readonly ContainerInterface $container,
	) {
		$this->differ = new WordDiffer();
		$this->annotator = new RedactionAnnotator(logger: $logger, appManager: $appManager, container: $container);

	}//end __construct()

	/**
	 * Compare two document subjects.
	 *
	 * @param array{fileId:int, versionTimestamp?:int} $left Left subject.
	 * @param array{fileId:int, versionTimestamp?:int} $right Right subject.
	 *
	 * @return array<string, mixed> The structured comparison response.
	 *
	 * @throws ComparisonException On any resolvable failure (404/413/415/422).
	 *
	 * @spec openspec/specs/document-comparison/spec.md
	 */
	public function compare(array $left, array $right): array {
		$this->logSubjects(left: $left, right: $right);

		$leftFile = $this->resolveFile(fileId: $left['fileId']);
		$rightFile = $this->resolveFile(fileId: $right['fileId']);

		$leftText = $this->extractText(file: $leftFile, side: 'left', versionTimestamp: ($left['versionTimestamp'] ?? null));
		$rightText = $this->extractText(file: $rightFile, side: 'right', versionTimestamp: ($right['versionTimestamp'] ?? null));

		$leftMime = $leftFile->getMimeType();
		$rightMime = $rightFile->getMimeType();

		$hunks = $this->differ->diff(leftText: $leftText, rightText: $rightText);
		$changed = 0;
		foreach ($hunks as $hunk) {
			if ($hunk['type'] !== 'unchanged') {
				$changed++;
			}
		}

		$response = [
			'crossFormat' => ($leftMime !== $rightMime),
			'hunks' => $hunks,
			'summary' => [
				'changedHunks' => $changed,
				'totalHunks' => count($hunks),
			],
		];

		// Redaction annotation: only when right is the anonymised output of left.
		$sourceFileId = $left['fileId'];
		$annotation = $this->annotator->annotate(hunks: $response['hunks'], sourceFileId: $sourceFileId);
		$response['hunks'] = $annotation['hunks'];
		$response['redactionAnnotation'] = $annotation['status'];
		if ($annotation['status'] === 'annotated') {
			$response['unredactedEntities'] = $annotation['unredactedEntities'];
		}

		return $response;
	}//end compare()

	/**
	 * Resolve a file through the requesting user's folder.
	 *
	 * Returns the File node or throws 404 — without distinguishing "does not
	 * exist" from "no access", per the spec.
	 *
	 * @param int $fileId The Nextcloud file id.
	 *
	 * @return File The resolved file.
	 *
	 * @throws ComparisonException 404 when not resolvable.
	 */
	private function resolveFile(int $fileId): File {
		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Subject not found.');
		}

		if ($fileId <= 0) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Subject not found.');
		}

		$userFolder = $this->rootFolder->getUserFolder($user->getUID());
		$nodes = $userFolder->getById($fileId);
		if (empty($nodes) === true) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Subject not found.');
		}

		$node = $nodes[0];
		if (($node instanceof File) === false) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Subject not found.');
		}

		return $node;
	}//end resolveFile()

	/**
	 * Extract normalised text from a subject (current content or a version).
	 *
	 * @param File $file The resolved file.
	 * @param string $side 'left' or 'right' (for error attribution).
	 * @param int|null $versionTimestamp Optional version timestamp.
	 *
	 * @return string The normalised text.
	 *
	 * @throws ComparisonException 404/413/415/422.
	 */
	private function extractText(File $file, string $side, ?int $versionTimestamp): string {
		$mime = $file->getMimeType();
		if ($this->isTextExtractable(mime: $mime) === false) {
			throw new ComparisonException(statusCode: 415, reason: 'unsupported-format', message: $side);
		}

		$raw = $this->readContent(file: $file, side: $side, versionTimestamp: $versionTimestamp);

		$maxBytes = $this->getMaxTextBytes();
		if (strlen($raw) > $maxBytes) {
			throw new ComparisonException(statusCode: 413, reason: 'too-large', message: $side);
		}

		return $this->normaliseWhitespace(text: $raw);
	}//end extractText()

	/**
	 * Read a subject's raw bytes: a prior version when a timestamp is given,
	 * otherwise the file's current content.
	 *
	 * @param File $file The resolved file.
	 * @param string $side 'left' or 'right' (for error attribution).
	 * @param int|null $versionTimestamp Optional version timestamp.
	 *
	 * @return string The raw content.
	 *
	 * @throws ComparisonException 404/422.
	 */
	private function readContent(File $file, string $side, ?int $versionTimestamp): string {
		if ($versionTimestamp !== null) {
			return $this->readVersionContent(file: $file, versionTimestamp: $versionTimestamp);
		}

		try {
			return $file->getContent();
		} catch (Throwable $e) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: $side);
		}

	}//end readContent()

	/**
	 * Read a prior version's content via the files_versions integration.
	 *
	 * Resolved lazily so the app degrades gracefully when files_versions is
	 * disabled (422 versions-unavailable) per the spec.
	 *
	 * @param File $file The file.
	 * @param int $versionTimestamp The version timestamp.
	 *
	 * @return string The version content.
	 *
	 * @throws ComparisonException 422 (disabled) or 404 (unknown version).
	 */
	private function readVersionContent(File $file, int $versionTimestamp): string {
		if ($this->appManager->isEnabledForUser('files_versions') === false) {
			throw new ComparisonException(statusCode: 422, reason: 'versions-unavailable', message: 'files_versions disabled');
		}

		try {
			$versionManager = $this->container->get('OCA\Files_Versions\Versions\IVersionManager');
		} catch (Throwable $e) {
			throw new ComparisonException(statusCode: 422, reason: 'versions-unavailable', message: 'version manager unavailable');
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'version');
		}

		try {
			$versions = $versionManager->getVersionsForFile($user, $file);
			foreach ($versions as $version) {
				if ((int)$version->getTimestamp() === $versionTimestamp) {
					$content = $versionManager->read($version);
					if (is_resource($content) === true) {
						$content = (string)stream_get_contents($content);
					}

					return (string)$content;
				}
			}
		} catch (Throwable $e) {
			$this->logger->debug('Version read failed', ['exception' => $e->getMessage()]);
		}

		throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'version');
	}//end readVersionContent()

	/**
	 * Determine whether the extractor can read a mime type as text.
	 *
	 * @param string $mime The mime type.
	 *
	 * @return bool True when extractable.
	 */
	private function isTextExtractable(string $mime): bool {
		if (in_array($mime, self::TEXT_MIME_TYPES, true) === true) {
			return true;
		}

		foreach (self::TEXT_MIME_PREFIXES as $prefix) {
			if (str_starts_with($mime, $prefix) === true) {
				return true;
			}
		}

		return false;
	}//end isTextExtractable()

	/**
	 * Normalise whitespace: collapse runs to single spaces, trim.
	 *
	 * @param string $text The raw text.
	 *
	 * @return string Normalised text.
	 */
	private function normaliseWhitespace(string $text): string {
		$collapsed = preg_replace('/\s+/u', ' ', $text);
		if ($collapsed === null) {
			$collapsed = $text;
		}

		return trim($collapsed);
	}//end normaliseWhitespace()

	/**
	 * Read the configured maximum text size in bytes.
	 *
	 * @return int Maximum bytes.
	 */
	private function getMaxTextBytes(): int {
		$value = $this->appConfig->getValueInt('filinq', self::CONFIG_MAX_TEXT_BYTES, self::DEFAULT_MAX_TEXT_BYTES);
		if ($value <= 0) {
			return self::DEFAULT_MAX_TEXT_BYTES;
		}

		return $value;
	}//end getMaxTextBytes()

	/**
	 * Log a comparison request with identifiers only (no content).
	 *
	 * @param array<string, mixed> $left Left subject.
	 * @param array<string, mixed> $right Right subject.
	 *
	 * @return void
	 */
	private function logSubjects(array $left, array $right): void {
		$this->logger->info(
			'Document comparison requested',
			[
				'leftFileId' => (int)($left['fileId'] ?? 0),
				'leftVersion' => ($left['versionTimestamp'] ?? null),
				'rightFileId' => (int)($right['fileId'] ?? 0),
				'rightVersion' => ($right['versionTimestamp'] ?? null),
			]
		);

	}//end logSubjects()
}//end class
