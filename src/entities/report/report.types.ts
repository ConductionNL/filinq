/**
 * Type definitions for the Report entity
 *
 * @package docudesk
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */

/**
 * Type definition for Report entity
 *
 * Represents the structure of a document report containing analysis results
 * for anonymization, WCAG compliance, and language level assessments.
 *
 * @see website/docs/api/document-reports.md for documentation
 */
export type TReport = {
    /** Unique identifier for the report */
    id?: string;
    /** Nextcloud node ID of the document */
    nodeId?: string;
    /** Name of the document */
    fileName?: string;
    /** Full path to the document in Nextcloud */
    filePath?: string;
    /** MIME type of the document */
    fileType?: string;
    /** File extension of the document */
    fileExtension?: string;
    /** Size of the file in bytes */
    fileSize?: number;
    /** Hash of the file content to determine if a new report is needed */
    fileHash?: string;
    /** Status of the report generation */
    status?: 'pending' | 'processing' | 'completed' | 'failed';
    /** Risk level assessment based on detected entities */
    riskLevel?: 'low' | 'medium' | 'high' | 'critical' | 'unknown';
    /** Results of anonymization analysis */
    anonymizationResults?: {
        /** Whether the document contains personal data */
        containsPersonalData: boolean;
        /** List of entities found in the document */
        entitiesFound: Array<{
            /** Type of entity (e.g., PERSON, EMAIL_ADDRESS) */
            entityType: string;
            /** The actual text that was identified */
            text: string;
            /** Confidence score (0-1) */
            score: number;
            /** Number of occurrences */
            count: number;
        }>;
        /** Total number of entities found */
        totalEntitiesFound: number;
        /** Categories of data found (e.g., name, email) */
        dataCategories: Array<string>;
        /** Status of anonymization process */
        anonymizationStatus: 'not_required' | 'pending' | 'in_progress' | 'completed' | 'failed';
        /** Reference to anonymization log if applicable */
        anonymizationLogId?: string;
    };
    /** Results of WCAG compliance analysis */
    wcagComplianceResults?: {
        /** WCAG compliance level */
        complianceLevel: 'A' | 'AA' | 'AAA' | 'non-compliant';
        /** Overall compliance score (0-100) */
        complianceScore: number;
        /** List of compliance issues found */
        issues: Array<{
            /** WCAG principle (e.g., Perceivable) */
            principle: string;
            /** WCAG guideline (e.g., 1.1) */
            guideline: string;
            /** WCAG criterion (e.g., 1.1.1) */
            criterion: string;
            /** Issue severity */
            severity: 'error' | 'warning' | 'notice';
            /** Description of the issue */
            message: string;
            /** HTML element causing the issue */
            element?: string;
            /** Suggested fix */
            recommendation?: string;
        }>;
        /** Total number of issues found */
        totalIssues: number;
        /** Breakdown of issues by severity */
        issuesBySeverity: {
            /** Number of errors */
            error: number;
            /** Number of warnings */
            warning: number;
            /** Number of notices */
            notice: number;
        };
    };
    /** Results of language level analysis */
    languageLevelResults?: {
        /** Primary language detected in the document */
        primaryLanguage: string;
        /** Various readability scores */
        readabilityScores: {
            /** Flesch-Kincaid Grade Level score */
            fleschKincaid?: number;
            /** SMOG Index score */
            smogIndex?: number;
            /** Coleman-Liau Index score */
            colemanLiau?: number;
            /** Automated Readability Index score */
            automatedReadability?: number;
            /** Dale-Chall Readability score */
            daleChall?: number;
        };
        /** Required education level to understand the document */
        educationLevel: 'elementary' | 'middle_school' | 'high_school' | 'college' | 'graduate' | 'professional';
        /** Overall text complexity assessment */
        textComplexity: 'very_easy' | 'easy' | 'moderate' | 'difficult' | 'very_difficult';
        /** Suggestions for improving readability */
        suggestions: Array<string>;
    };
    /** Retention period in days (0 for indefinite) */
    retentionPeriod?: number;
    /** Date when the retention period expires */
    retentionExpiry?: string;
    /** Legal basis for processing the data under GDPR */
    legalBasis?: 'consent' | 'contract' | 'legal_obligation' | 'vital_interests' | 'public_interest' | 'legitimate_interests';
    /** Name of the data controller */
    dataController?: string;
    /** Start time of the report generation process */
    startTime?: string;
    /** End time of the report generation process */
    endTime?: string;
    /** Duration of the report generation process in milliseconds */
    duration?: number;
    /** Error message if the report generation failed */
    errorMessage?: string;
    /** ID of the user who initiated the report generation */
    userId?: string;
    /** List of detected entities */
    entities?: Array<{
        /** Type of entity (e.g., PERSON, EMAIL_ADDRESS) */
        entity_type: string;
        /** The actual text that was identified */
        text: string;
        /** Confidence score (0-1) */
        score: number;
    }>;
    /** Creation timestamp */
    created?: string;
    /** Last update timestamp */
    updated?: string;
}
