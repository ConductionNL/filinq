<?php
/**
 * Custom Dictionary Detection Runner
 *
 * The custom-dictionary detection pass extracted from AnonymizationService:
 * runs every active organisation dictionary's matcher across a file's
 * OpenRegister chunks, de-duplicates matches by absolute document position
 * (so an occurrence straddling two overlapping chunks is written once), and
 * writes the resulting `CUSTOM_DICTIONARY` entity relations.
 *
 * Best-effort by contract: any failure is logged and returned as a
 * human-readable warning string. It never throws, so OpenRegister's own
 * detections are always returned by the caller regardless.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

use DateTime;
use OCP\App\IAppManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * Runs the custom-dictionary detection pass over a file's chunks.
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
 */
class CustomDictionaryDetectionRunner
{

    /**
     * The entity type this pass produces.
     *
     * @var string
     */
    private const ENTITY_TYPE = 'CUSTOM_DICTIONARY';

    /**
     * The detection method stamped on every relation this pass writes.
     *
     * @var string
     */
    private const DETECTION_METHOD = 'custom_dictionary';

    /**
     * Constructor.
     *
     * @param LoggerInterface              $logger                Logger for error reporting.
     * @param ContainerInterface           $container             Container for lazy OpenRegister resolution.
     * @param IAppManager                  $appManager            App manager (OpenRegister availability).
     * @param CustomDictionaryService      $customDictionary      Detection-scoped dictionary listing.
     * @param CustomDictionaryMatchService $matcher Pure matcher run per active dictionary.
     *
     * @return void
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly IAppManager $appManager,
        private readonly CustomDictionaryService $customDictionary,
        private readonly CustomDictionaryMatchService $matcher
    ) {

    }//end __construct()

    /**
     * Run the custom-dictionary detection pass for one file.
     *
     * @param int                     $fileId              The Nextcloud file ID.
     * @param array<int, string>|null $entityTypeWhitelist The operator's enabled-type
     *                                                     selection (null = all types).
     *                                                     When non-null and it does not
     *                                                     contain `CUSTOM_DICTIONARY`, the
     *                                                     pass is skipped entirely.
     *
     * @return string|null Null on success (or when skipped), a warning message on failure.
     *
     * @spec openspec/changes/custom-dictionary-recognition/specs/custom-dictionary-recognition/spec.md
     */
    public function run(int $fileId, ?array $entityTypeWhitelist): ?string
    {
        if ($entityTypeWhitelist !== null && in_array(self::ENTITY_TYPE, $entityTypeWhitelist, true) === false) {
            // Operator has disabled automatic custom-dictionary detection.
            return null;
        }

        try {
            $dictionaries = $this->customDictionary->listActiveDictionariesForDetection();
            if (empty($dictionaries) === true) {
                return null;
            }

            $chunkMapper = $this->getOpenRegisterService(className: 'OCA\OpenRegister\Db\ChunkMapper');
            $chunks      = $chunkMapper->findBySource('file', $fileId);
            if (empty($chunks) === true) {
                return null;
            }

            $entityRelationMapper = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\EntityRelationMapper'
            );
            $gdprEntityMapper     = $this->getOpenRegisterService(
                className: 'OCA\OpenRegister\Db\GdprEntityMapper'
            );

            $this->clearRelations(fileId: $fileId, entityRelationMapper: $entityRelationMapper);

            $rowsToInsert = $this->matchDictionariesAgainstChunks(
                chunks: $chunks,
                dictionaries: $dictionaries,
                fileId: $fileId,
                gdprEntityMapper: $gdprEntityMapper
            );

            if (empty($rowsToInsert) === false) {
                $entityRelationMapper->insertBatch(rows: $rowsToInsert);
            }

            return null;
        } catch (Throwable $e) {
            $this->logger->error(
                '[CustomDictionaryDetectionRunner] Custom dictionary matching failed; continuing with OpenRegister detection only.',
                ['file' => __FILE__, 'line' => __LINE__, 'fileId' => $fileId, 'error' => $e->getMessage()]
            );
            return 'Custom dictionary matching did not run: '.$e->getMessage();
        }//end try

    }//end run()

    /**
     * Run every active dictionary's matcher against every chunk and build
     * the `EntityRelationMapper::insertBatch()` row set.
     *
     * @param array<int, mixed> $chunks           OpenRegister `Chunk` entities, chunk-index ascending.
     * @param array<int, mixed> $dictionaries     Active dictionaries + terms (see
     *                                            {@see CustomDictionaryService::listActiveDictionariesForDetection()}
     *                                            for the exact row shape).
     * @param int               $fileId           The Nextcloud file ID.
     * @param mixed             $gdprEntityMapper OpenRegister `GdprEntityMapper` instance.
     *
     * @return array<int, array<string, mixed>> Rows ready for `EntityRelationMapper::insertBatch()`.
     */
    private function matchDictionariesAgainstChunks(
        array $chunks,
        array $dictionaries,
        int $fileId,
        mixed $gdprEntityMapper
    ): array {
        $globalClaimed = [];
        $entityCache   = [];
        $rowsToInsert  = [];

        foreach ($chunks as $chunk) {
            $chunkText = (string) $chunk->getTextContent();
            if ($chunkText === '') {
                continue;
            }

            $chunkOccurrences = $this->collectChunkOccurrences(
                chunkText: $chunkText,
                dictionaries: $dictionaries
            );
            if (empty($chunkOccurrences) === true) {
                continue;
            }

            $startOffset = (int) $chunk->getStartOffset();
            foreach ($chunkOccurrences as $occurrence) {
                $absoluteStart = ($startOffset + $occurrence['positionStart']);
                $absoluteEnd   = ($startOffset + $occurrence['positionEnd']);

                if ($this->rangeOverlapsAny(start: $absoluteStart, end: $absoluteEnd, claimed: $globalClaimed) === true) {
                    // Already matched at this absolute document position —
                    // typically the same occurrence seen again in an
                    // overlap region shared by two adjacent chunks.
                    continue;
                }

                $globalClaimed[] = [$absoluteStart, $absoluteEnd];

                $entityKey = (self::ENTITY_TYPE.'|'.$occurrence['value']);
                if (isset($entityCache[$entityKey]) === false) {
                    $entityCache[$entityKey] = $this->lookupOrCreateEntity(
                        value: $occurrence['value'],
                        gdprEntityMapper: $gdprEntityMapper
                    );
                }

                $rowsToInsert[] = [
                    'entityId'          => $entityCache[$entityKey],
                    'fileId'            => $fileId,
                    'chunkId'           => (int) $chunk->getId(),
                    'positionStart'     => $occurrence['positionStart'],
                    'positionEnd'       => $occurrence['positionEnd'],
                    'confidence'        => 1.0,
                    'detectionMethod'   => self::DETECTION_METHOD,
                    // The only free-text field EntityRelation exposes; carries
                    // the matching term/dictionary label for the review UI
                    // (design.md §D3 — "per-list label carried on the relation").
                    'context'           => $occurrence['label'],
                    'anonymized'        => false,
                    'skipAnonymization' => false,
                    'createdAt'         => new DateTime(),
                ];
            }//end foreach
        }//end foreach

        return $rowsToInsert;

    }//end matchDictionariesAgainstChunks()

    /**
     * Collect one chunk's occurrences across every active dictionary,
     * longest-match-first.
     *
     * Sorting longest-first means a shorter cross-dictionary match cannot
     * pre-empt a longer one at an overlapping position — mirrors
     * CustomDictionaryMatchService's own per-dictionary overlap rule.
     *
     * @param string            $chunkText    The chunk's text content.
     * @param array<int, mixed> $dictionaries Active dictionaries + terms.
     *
     * @return array<int, array<string, mixed>> Occurrences, longest match first.
     */
    private function collectChunkOccurrences(string $chunkText, array $dictionaries): array
    {
        $chunkOccurrences = [];
        foreach ($dictionaries as $dictionary) {
            foreach ($this->matcher->match(
                    text: $chunkText,
                    terms: $dictionary['terms'],
                    mode: $dictionary['matchMode']
                ) as $occurrence
            ) {
                $chunkOccurrences[] = $occurrence;
            }
        }

        usort(
            $chunkOccurrences,
            static fn (array $a, array $b): int => (
                ($b['positionEnd'] - $b['positionStart']) <=> ($a['positionEnd'] - $a['positionStart'])
            )
        );

        return $chunkOccurrences;

    }//end collectChunkOccurrences()

    /**
     * Look up an existing `CUSTOM_DICTIONARY` catalogue entry for `$value`,
     * or create one. Mirrors OpenRegister's own manual-entity lookup-or-
     * create convention (`ManualEntityService::lookupOrCreateEntity`) so
     * repeated matches of the same literal value share one catalogue row.
     *
     * @param string $value            The matched term text.
     * @param mixed  $gdprEntityMapper OpenRegister `GdprEntityMapper` instance.
     *
     * @return int The catalogue entity id.
     */
    private function lookupOrCreateEntity(string $value, mixed $gdprEntityMapper): int
    {
        $existing = $gdprEntityMapper->findOneByValueAndType(value: $value, type: self::ENTITY_TYPE);
        if ($existing !== null) {
            return (int) $existing->getId();
        }

        // Instantiated by FQCN string (not a hard `use` import) so this class
        // stays loadable without OpenRegister installed, mirroring
        // `getOpenRegisterService()`'s own lazy-resolution convention. A
        // fresh instance is required per call — the container's `get()`
        // would return a shared, mutable singleton, which is wrong for an
        // Entity that is set up differently on every insert.
        $entityClass = 'OCA\OpenRegister\Db\GdprEntity';
        $now         = new DateTime();
        $entity      = new $entityClass();
        $entity->setUuid(Uuid::v4()->toRfc4122());
        $entity->setValue($value);
        $entity->setType(self::ENTITY_TYPE);
        $entity->setCategory('contextual_data');
        $entity->setDetectedAt($now);
        $entity->setUpdatedAt($now);

        $inserted = $gdprEntityMapper->insert($entity);
        return (int) $inserted->getId();

    }//end lookupOrCreateEntity()

    /**
     * Clear this file's prior `custom_dictionary` relations so a re-run
     * never appends duplicates (design.md §D3 idempotency rule).
     *
     * @param int   $fileId               The Nextcloud file ID.
     * @param mixed $entityRelationMapper OpenRegister `EntityRelationMapper` instance.
     *
     * @return void
     */
    private function clearRelations(int $fileId, mixed $entityRelationMapper): void
    {
        foreach ($entityRelationMapper->findByFileId($fileId) as $relation) {
            if ($relation->getDetectionMethod() === self::DETECTION_METHOD) {
                $entityRelationMapper->delete($relation);
            }
        }

    }//end clearRelations()

    /**
     * Whether `[start, end)` overlaps any already-claimed absolute-position range.
     *
     * @param int                            $start   Candidate match start.
     * @param int                            $end     Candidate match end.
     * @param array<int, array{0:int,1:int}> $claimed Already-claimed `[start, end]` pairs.
     *
     * @return bool True when the candidate overlaps a claimed range.
     */
    private function rangeOverlapsAny(int $start, int $end, array $claimed): bool
    {
        foreach ($claimed as [$claimedStart, $claimedEnd]) {
            if ($start < $claimedEnd && $end > $claimedStart) {
                return true;
            }
        }

        return false;

    }//end rangeOverlapsAny()

    /**
     * Get an OpenRegister service or mapper by class name.
     *
     * @param string $className The fully qualified class name.
     *
     * @return mixed The service instance.
     *
     * @throws RuntimeException If OpenRegister is not available.
     */
    private function getOpenRegisterService(string $className): mixed
    {
        if (in_array('openregister', $this->appManager->getInstalledApps(), true) === true) {
            return $this->container->get($className);
        }

        throw new RuntimeException($className.' is not available.');

    }//end getOpenRegisterService()
}//end class
