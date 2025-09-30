/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

/**
 * Type definition for Anonymization entity
 *
 * @see website/docs/api/anonymization-logs.md for documentation
 */
export type TAnonymization = {
    /** Unique identifier for the anonymization log */
    id?: string;

    /** Nextcloud node ID of the original document */
    nodeId?: number;

    /** Hash of the file content */
    fileHash?: string;

    /** Original name of the document */
    originalFileName?: string;

    /** Name of the anonymized document */
    anonymizedFileName?: string;

    /** Path of the anonymized document */
    anonymizedFilePath?: string;

    /** Status of the anonymization operation */
    status?: 'pending' | 'processing' | 'completed' | 'failed';

    /** Message about the anonymization process */
    message?: string;

    /** List of entities found during anonymization */
    entities?: Array<{
        entityType: string;
        text: string;
        score: number;
        startPosition?: number;
        endPosition?: number;
    }>;

    /** List of entity replacements made during anonymization */
    replacements?: Array<{
        entityType: string;
        originalText: string;
        replacementText: string;
        key?: string;
        start?: number;
        end?: number;
    }>;

    /** Start time of the anonymization process (timestamp) */
    startTime?: number;

    /** End time of the anonymization process (timestamp) */
    endTime?: number | null;

    /** Duration of the anonymization process in seconds */
    processingTime?: number | null;
}
