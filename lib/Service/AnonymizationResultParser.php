<?php
/**
 * Anonymization Result Parser
 *
 * Service for parsing anonymization results from OpenRegister into
 * a structured format. Extracted from EntityDetectionService to
 * reduce class complexity.
 *
 * @category  Service
 * @package   OCA\DocuDesk\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.DocuDesk.app
 *
 * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Service;

/**
 * Service for parsing anonymization results into structured arrays
 *
 * @category Service
 * @package  OCA\DocuDesk\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */
class AnonymizationResultParser
{
    /**
     * Parse anonymization result into a structured array
     *
     * @param mixed $result The raw anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     *
     * @spec openspec/changes/retrofit-2026-05-24-annotate-docudesk/tasks.md#task-4
     */
    public function parseResult(mixed $result): array
    {
        if (is_object($result) === true && method_exists($result, 'getId') === true) {
            return $this->extractFromObject(result: $result);
        }

        if (is_array($result) === true) {
            return $this->extractFromArray(result: $result);
        }

        return [
            'anonymizedFileId'   => null,
            'anonymizedFileName' => null,
            'anonymizedFilePath' => null,
        ];

    }//end parseResult()

    /**
     * Extract file info from anonymization result object
     *
     * @param mixed $result The anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     */
    private function extractFromObject(mixed $result): array
    {
        $fileName = null;
        if (method_exists($result, 'getName') === true) {
            $fileName = $result->getName();
        }

        $filePath = null;
        if (method_exists($result, 'getPath') === true) {
            $filePath = $result->getPath();
        }

        return [
            'anonymizedFileId'   => $result->getId(),
            'anonymizedFileName' => $fileName,
            'anonymizedFilePath' => $filePath,
        ];

    }//end extractFromObject()

    /**
     * Extract file info from anonymization result array
     *
     * @param array<string, mixed> $result The anonymization result
     *
     * @return array{anonymizedFileId: mixed, anonymizedFileName: mixed, anonymizedFilePath: mixed}
     */
    private function extractFromArray(array $result): array
    {
        return [
            'anonymizedFileId'   => $result['fileId'] ?? $result['id'] ?? null,
            'anonymizedFileName' => $result['fileName'] ?? $result['name'] ?? null,
            'anonymizedFilePath' => $result['filePath'] ?? $result['path'] ?? null,
        ];

    }//end extractFromArray()
}//end class
