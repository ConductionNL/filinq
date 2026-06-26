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
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/document-comparison/specs/document-comparison/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use OCA\DocuDesk\Exception\ComparisonException;
use OCP\App\IAppManager;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\IAppConfig;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service computing structured, redaction-aware document comparisons.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class DocumentComparisonService
{
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
     * Constructor.
     *
     * @param LoggerInterface    $logger      Logger for diagnostics.
     * @param IRootFolder        $rootFolder  Root folder for user-scoped file access.
     * @param IUserSession       $userSession Current user session.
     * @param IAppConfig         $appConfig   App configuration.
     * @param IAppManager        $appManager  App manager (OpenRegister availability check).
     * @param ContainerInterface $container   DI container for lazy OR resolution.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IRootFolder $rootFolder,
        private readonly IUserSession $userSession,
        private readonly IAppConfig $appConfig,
        private readonly IAppManager $appManager,
        private readonly ContainerInterface $container
    ) {

    }//end __construct()

    /**
     * Compare two document subjects.
     *
     * @param array{fileId:int, versionTimestamp?:int} $left  Left subject.
     * @param array{fileId:int, versionTimestamp?:int} $right Right subject.
     *
     * @return array<string, mixed> The structured comparison response.
     *
     * @throws ComparisonException On any resolvable failure (404/413/415/422).
     *
     * @spec openspec/changes/document-comparison/specs/document-comparison/spec.md
     */
    public function compare(array $left, array $right): array
    {
        $this->logSubjects(left: $left, right: $right);

        $leftFile  = $this->resolveFile(fileId: $left['fileId']);
        $rightFile = $this->resolveFile(fileId: $right['fileId']);

        $leftText  = $this->extractText(file: $leftFile, side: 'left', versionTimestamp: ($left['versionTimestamp'] ?? null));
        $rightText = $this->extractText(file: $rightFile, side: 'right', versionTimestamp: ($right['versionTimestamp'] ?? null));

        $leftMime  = $leftFile->getMimeType();
        $rightMime = $rightFile->getMimeType();

        $hunks   = $this->diff(leftText: $leftText, rightText: $rightText);
        $changed = 0;
        foreach ($hunks as $hunk) {
            if ($hunk['type'] !== 'unchanged') {
                $changed++;
            }
        }

        $response = [
            'crossFormat' => ($leftMime !== $rightMime),
            'hunks'       => $hunks,
            'summary'     => [
                'changedHunks' => $changed,
                'totalHunks'   => count($hunks),
            ],
        ];

        // Redaction annotation: only when right is the anonymised output of left.
        $sourceFileId      = $left['fileId'];
        $annotation        = $this->annotateRedactions(hunks: $response['hunks'], sourceFileId: $sourceFileId);
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
    private function resolveFile(int $fileId): File
    {
        $user = $this->userSession->getUser();
        if ($user === null) {
            throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Subject not found.');
        }

        if ($fileId <= 0) {
            throw new ComparisonException(statusCode: 404, reason: 'not-found', message: 'Subject not found.');
        }

        $userFolder = $this->rootFolder->getUserFolder($user->getUID());
        $nodes      = $userFolder->getById($fileId);
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
     * @param File     $file             The resolved file.
     * @param string   $side             'left' or 'right' (for error attribution).
     * @param int|null $versionTimestamp Optional version timestamp.
     *
     * @return string The normalised text.
     *
     * @throws ComparisonException 404/413/415/422.
     */
    private function extractText(File $file, string $side, ?int $versionTimestamp): string
    {
        $mime = $file->getMimeType();
        if ($this->isTextExtractable(mime: $mime) === false) {
            throw new ComparisonException(statusCode: 415, reason: 'unsupported-format', message: $side);
        }

        if ($versionTimestamp !== null) {
            $raw = $this->readVersionContent(file: $file, versionTimestamp: (int) $versionTimestamp);
        } else {
            try {
                $raw = $file->getContent();
            } catch (Throwable $e) {
                throw new ComparisonException(statusCode: 404, reason: 'not-found', message: $side);
            }
        }

        $maxBytes = $this->getMaxTextBytes();
        if (strlen($raw) > $maxBytes) {
            throw new ComparisonException(statusCode: 413, reason: 'too-large', message: $side);
        }

        return $this->normaliseWhitespace(text: $raw);

    }//end extractText()

    /**
     * Read a prior version's content via the files_versions integration.
     *
     * Resolved lazily so the app degrades gracefully when files_versions is
     * disabled (422 versions-unavailable) per the spec.
     *
     * @param File $file             The file.
     * @param int  $versionTimestamp The version timestamp.
     *
     * @return string The version content.
     *
     * @throws ComparisonException 422 (disabled) or 404 (unknown version).
     */
    private function readVersionContent(File $file, int $versionTimestamp): string
    {
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
                if ((int) $version->getTimestamp() === $versionTimestamp) {
                    $content = $versionManager->read($version);
                    if (is_resource($content) === true) {
                        $content = (string) stream_get_contents($content);
                    }

                    return (string) $content;
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
    private function isTextExtractable(string $mime): bool
    {
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
    private function normaliseWhitespace(string $text): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', $text);
        if ($collapsed === null) {
            $collapsed = $text;
        }

        return trim($collapsed);

    }//end normaliseWhitespace()

    /**
     * Compute a word-level structured diff coalesced into hunks.
     *
     * Uses a longest-common-subsequence over word tokens, then groups runs
     * of equal/insert/delete operations into hunks with left/right offsets.
     *
     * @param string $leftText  Left (normalised) text.
     * @param string $rightText Right (normalised) text.
     *
     * @return array<int, array<string, mixed>> Ordered hunks.
     */
    private function diff(string $leftText, string $rightText): array
    {
        if ($leftText === '') {
            $leftWords = [];
        } else {
            $leftWords = explode(' ', $leftText);
        }

        if ($rightText === '') {
            $rightWords = [];
        } else {
            $rightWords = explode(' ', $rightText);
        }

        $ops = $this->lcsDiff(a: $leftWords, b: $rightWords);

        $hunks       = [];
        $leftOffset  = 0;
        $rightOffset = 0;
        $i           = 0;
        $count       = count($ops);

        while ($i < $count) {
            $opType = $ops[$i]['op'];

            // Coalesce consecutive ops of the same logical hunk type.
            if ($opType === 'equal') {
                $words = [];
                while ($i < $count && $ops[$i]['op'] === 'equal') {
                    $words[] = $ops[$i]['left'];
                    $i++;
                }

                $text         = implode(' ', $words);
                $leftLen      = strlen($text);
                $hunks[]      = [
                    'type'      => 'unchanged',
                    'left'      => ['offset' => $leftOffset, 'length' => $leftLen],
                    'right'     => ['offset' => $rightOffset, 'length' => $leftLen],
                    'leftText'  => $text,
                    'rightText' => $text,
                ];
                $leftOffset  += ($leftLen + 1);
                $rightOffset += ($leftLen + 1);
                continue;
            }

            // A change block = a run of deletes and/or inserts.
            $removed = [];
            $added   = [];
            while ($i < $count && $ops[$i]['op'] !== 'equal') {
                if ($ops[$i]['op'] === 'delete') {
                    $removed[] = $ops[$i]['left'];
                } else if ($ops[$i]['op'] === 'insert') {
                    $added[] = $ops[$i]['right'];
                }

                $i++;
            }

            $removedText = implode(' ', $removed);
            $addedText   = implode(' ', $added);

            if ($removed !== [] && $added !== []) {
                $type = 'changed';
            } else if ($added !== []) {
                $type = 'added';
            } else {
                $type = 'removed';
            }

            $hunk = ['type' => $type];

            if ($removed !== []) {
                $hunk['left']     = ['offset' => $leftOffset, 'length' => strlen($removedText)];
                $hunk['leftText'] = $removedText;
                $leftOffset      += (strlen($removedText) + 1);
            } else {
                $hunk['left'] = null;
            }

            if ($added !== []) {
                $hunk['right']     = ['offset' => $rightOffset, 'length' => strlen($addedText)];
                $hunk['rightText'] = $addedText;
                $rightOffset      += (strlen($addedText) + 1);
            } else {
                $hunk['right'] = null;
            }

            $hunks[] = $hunk;
        }//end while

        return $hunks;

    }//end diff()

    /**
     * Compute an LCS-based op list over two word arrays.
     *
     * @param array<int, string> $a Left words.
     * @param array<int, string> $b Right words.
     *
     * @return array<int, array{op:string, left?:string, right?:string}> Ops.
     */
    private function lcsDiff(array $a, array $b): array
    {
        $n = count($a);
        $m = count($b);

        // Build the LCS length table.
        $lcs = [];
        for ($i = 0; $i <= $n; $i++) {
            $lcs[$i] = array_fill(0, ($m + 1), 0);
        }

        for ($i = ($n - 1); $i >= 0; $i--) {
            for ($j = ($m - 1); $j >= 0; $j--) {
                if ($a[$i] === $b[$j]) {
                    $lcs[$i][$j] = ($lcs[($i + 1)][($j + 1)] + 1);
                } else {
                    $lcs[$i][$j] = max($lcs[($i + 1)][$j], $lcs[$i][($j + 1)]);
                }
            }
        }

        // Backtrack into an op list.
        $ops = [];
        $i   = 0;
        $j   = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $ops[] = ['op' => 'equal', 'left' => $a[$i], 'right' => $b[$j]];
                $i++;
                $j++;
            } else if ($lcs[($i + 1)][$j] >= $lcs[$i][($j + 1)]) {
                $ops[] = ['op' => 'delete', 'left' => $a[$i]];
                $i++;
            } else {
                $ops[] = ['op' => 'insert', 'right' => $b[$j]];
                $j++;
            }
        }

        while ($i < $n) {
            $ops[] = ['op' => 'delete', 'left' => $a[$i]];
            $i++;
        }

        while ($j < $m) {
            $ops[] = ['op' => 'insert', 'right' => $b[$j]];
            $j++;
        }

        return $ops;

    }//end lcsDiff()

    /**
     * Annotate change hunks with redaction metadata + completeness signal.
     *
     * Resolves the OR EntityRelationMapper lazily. Falls back to a plain diff
     * (status 'unavailable') when OR is absent, and 'none' when no relations
     * exist for the source file (unrelated pair).
     *
     * @param array<int, array<string, mixed>> $hunks        The diff hunks.
     * @param int                              $sourceFileId The source (left) file id.
     *
     * @return array{hunks: array<int, array<string, mixed>>, status: string, unredactedEntities: array<int, array<string, mixed>>}
     */
    private function annotateRedactions(array $hunks, int $sourceFileId): array
    {
        $result = [
            'hunks'              => $hunks,
            'status'             => 'none',
            'unredactedEntities' => [],
        ];

        if ($this->isOpenRegisterAvailable() === false) {
            $result['status'] = 'unavailable';
            return $result;
        }

        $mapper = $this->tryGetEntityRelationMapper();
        if ($mapper === null) {
            $result['status'] = 'unavailable';
            return $result;
        }

        try {
            $relations = $mapper->findByFileId(fileId: $sourceFileId);
            $joined    = $mapper->findEntitiesForFile(fileId: $sourceFileId);
        } catch (Throwable $e) {
            $this->logger->debug('Entity relation lookup failed', ['fileId' => $sourceFileId]);
            $result['status'] = 'unavailable';
            return $result;
        }

        if (empty($relations) === true) {
            // No anonymisation link for this file: plain diff, no annotation.
            return $result;
        }

        // Index entity metadata (type + canonical value/name) by entity id.
        $entityMeta = [];
        foreach ($joined as $row) {
            $eid = (int) ($row['entity_id'] ?? 0);
            if ($eid === 0) {
                continue;
            }

            $entityMeta[$eid] = [
                'entityType' => (string) ($row['entity_type'] ?? ''),
                'entityName' => (string) ($row['entity_name'] ?? ($row['entity_value'] ?? '')),
                'value'      => (string) ($row['entity_value'] ?? ''),
            ];
        }

        // Build the anonymise set: non-skip relations with their replacement keys.
        $anonymiseSet = [];
        foreach ($relations as $relation) {
            if ($this->isSkipFlagged(relation: $relation) === true) {
                continue;
            }

            $eid = (int) $relation->getEntityId();
            $anonymiseSet[$eid] = [
                'replacement' => (string) ($relation->getAnonymizedValue() ?? ''),
                'matched'     => false,
            ];
        }

        // Annotate hunks: match inserted (replacement key) or removed (value) spans.
        $annotatedHunks = [];
        foreach ($result['hunks'] as $hunk) {
            $match = $this->matchHunkToEntity(hunk: $hunk, anonymiseSet: $anonymiseSet, entityMeta: $entityMeta);
            if ($match !== null) {
                $hunk['redaction'] = [
                    'entityId'   => $match['entityId'],
                    'entityType' => $match['entityType'],
                    'matchedBy'  => $match['matchedBy'],
                ];
                $anonymiseSet[$match['entityId']]['matched'] = true;
            }

            $annotatedHunks[] = $hunk;
        }

        $result['hunks']  = $annotatedHunks;
        $result['status'] = 'annotated';

        // Completeness signal: anonymise-set entities that matched zero hunks.
        $unredacted = [];
        foreach ($anonymiseSet as $eid => $info) {
            if ($info['matched'] === false) {
                $unredacted[] = [
                    'entityId'   => $eid,
                    'entityName' => ($entityMeta[$eid]['entityName'] ?? ''),
                ];
            }
        }

        $result['unredactedEntities'] = $unredacted;

        return $result;

    }//end annotateRedactions()

    /**
     * Match a hunk to an entity by replacement-key (insert) or value (delete).
     *
     * @param array<string, mixed>                                                  $hunk         A diff hunk.
     * @param array<int, array{replacement:string, matched:bool}>                   $anonymiseSet The anonymise set.
     * @param array<int, array{entityType:string, entityName:string, value:string}> $entityMeta   Entity metadata.
     *
     * @return array{entityId:int, entityType:string, matchedBy:string}|null The match or null.
     */
    private function matchHunkToEntity(array $hunk, array $anonymiseSet, array $entityMeta): ?array
    {
        if ($hunk['type'] === 'unchanged') {
            return null;
        }

        $insertedText = (string) ($hunk['rightText'] ?? '');
        $removedText  = (string) ($hunk['leftText'] ?? '');

        // Key-based: inserted span equals an entity's replacement key.
        if ($insertedText !== '') {
            foreach ($anonymiseSet as $eid => $info) {
                if ($info['replacement'] !== '' && str_contains($insertedText, $info['replacement']) === true) {
                    return [
                        'entityId'   => $eid,
                        'entityType' => ($entityMeta[$eid]['entityType'] ?? ''),
                        'matchedBy'  => 'key',
                    ];
                }
            }
        }

        // Value-based fallback: removed span equals an entity canonical value.
        if ($removedText !== '') {
            foreach ($anonymiseSet as $eid => $info) {
                $value = (string) ($entityMeta[$eid]['value'] ?? '');
                if ($value !== '' && str_contains($removedText, $value) === true) {
                    return [
                        'entityId'   => $eid,
                        'entityType' => ($entityMeta[$eid]['entityType'] ?? ''),
                        'matchedBy'  => 'value',
                    ];
                }
            }
        }

        return null;

    }//end matchHunkToEntity()

    /**
     * Determine whether a relation is skip-flagged (operator-released override).
     *
     * @param mixed $relation The EntityRelation object.
     *
     * @return bool True when skip-flagged.
     */
    private function isSkipFlagged(mixed $relation): bool
    {
        if (method_exists($relation, 'getSkipAnonymization') === true) {
            return ($relation->getSkipAnonymization() === true);
        }

        return false;

    }//end isSkipFlagged()

    /**
     * Whether OpenRegister is installed/available.
     *
     * @return bool True when available.
     */
    private function isOpenRegisterAvailable(): bool
    {
        return in_array('openregister', $this->appManager->getInstalledApps(), true);

    }//end isOpenRegisterAvailable()

    /**
     * Try to resolve the OR EntityRelationMapper, returning null on failure.
     *
     * @return mixed The mapper or null.
     */
    private function tryGetEntityRelationMapper(): mixed
    {
        try {
            return $this->container->get('OCA\OpenRegister\Db\EntityRelationMapper');
        } catch (Throwable $e) {
            $this->logger->debug('EntityRelationMapper unavailable: '.$e->getMessage());
            return null;
        }

    }//end tryGetEntityRelationMapper()

    /**
     * Read the configured maximum text size in bytes.
     *
     * @return int Maximum bytes.
     */
    private function getMaxTextBytes(): int
    {
        $value = $this->appConfig->getValueInt('docudesk', self::CONFIG_MAX_TEXT_BYTES, self::DEFAULT_MAX_TEXT_BYTES);
        if ($value <= 0) {
            return self::DEFAULT_MAX_TEXT_BYTES;
        }

        return $value;

    }//end getMaxTextBytes()

    /**
     * Log a comparison request with identifiers only (no content).
     *
     * @param array<string, mixed> $left  Left subject.
     * @param array<string, mixed> $right Right subject.
     *
     * @return void
     */
    private function logSubjects(array $left, array $right): void
    {
        $this->logger->info(
            'Document comparison requested',
            [
                'leftFileId'   => (int) ($left['fileId'] ?? 0),
                'leftVersion'  => ($left['versionTimestamp'] ?? null),
                'rightFileId'  => (int) ($right['fileId'] ?? 0),
                'rightVersion' => ($right['versionTimestamp'] ?? null),
            ]
        );

    }//end logSubjects()
}//end class
