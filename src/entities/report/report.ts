/**
 * @copyright Copyright (c) 2024 Conduction B.V. <info@conduction.nl>
 * @license EUPL-1.2
 */

import { SafeParseReturnType, z } from 'zod'
import { TReport } from './report.types'

/**
 * Report entity class
 *
 * Represents a document report containing analysis results for anonymization,
 * WCAG compliance, and language level assessments.
 *
 * @see website/docs/api/document-reports.md for documentation
 *
 * @package docudesk
 * @author Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (c) 2024 Conduction B.V.
 * @license EUPL-1.2
 * @version 1.0.0
 */
export class Report implements TReport {

	/**
	 * Unique identifier for the report
	 * @readonly
	 */
	public readonly id: string

	/**
	 * Nextcloud node ID of the document
	 */
	public nodeId: string

	/**
	 * Name of the document
	 */
	public fileName: string

	/**
	 * Full path to the document in Nextcloud
	 */
	public filePath: string

	/**
	 * MIME type of the document
	 */
	public fileType: string

	/**
	 * File extension of the document
	 */
	public fileExtension: string

	/**
	 * Size of the file in bytes
	 */
	public fileSize: number

	/**
	 * Hash of the file content to determine if a new report is needed
	 */
	public fileHash: string

	/**
	 * Status of the report generation
	 */
	public status: 'pending' | 'processing' | 'completed' | 'failed'

	/**
	 * Risk level assessment based on detected entities
	 */
	public riskLevel: 'low' | 'medium' | 'high' | 'critical' | 'unknown'

	/**
	 * Results of anonymization analysis
	 */
	public anonymizationResults?: {
        /**
         * Whether the document contains personal data
         */
        containsPersonalData: boolean;
        /**
         * List of entities found in the document
         */
        entitiesFound: Array<{
            /**
             * Type of entity (e.g., PERSON, EMAIL_ADDRESS)
             */
            entityType: string;
            /**
             * The actual text that was identified
             */
            text: string;
            /**
             * Confidence score (0-1)
             */
            score: number;
            /**
             * Number of occurrences
             */
            count: number;
        }>;
        /**
         * Total number of entities found
         */
        totalEntitiesFound: number;
        /**
         * Categories of data found (e.g., name, email)
         */
        dataCategories: Array<string>;
        /**
         * Status of anonymization process
         */
        anonymizationStatus: 'not_required' | 'pending' | 'in_progress' | 'completed' | 'failed';
        /**
         * Reference to anonymization log if applicable
         */
        anonymizationLogId?: string;
    }

	/**
	 * Results of WCAG compliance analysis
	 */
	public wcagComplianceResults?: {
        /**
         * WCAG compliance level
         */
        complianceLevel: 'A' | 'AA' | 'AAA' | 'non-compliant';
        /**
         * Overall compliance score (0-100)
         */
        complianceScore: number;
        /**
         * List of compliance issues found
         */
        issues: Array<{
            /**
             * WCAG principle (e.g., Perceivable)
             */
            principle: string;
            /**
             * WCAG guideline (e.g., 1.1)
             */
            guideline: string;
            /**
             * WCAG criterion (e.g., 1.1.1)
             */
            criterion: string;
            /**
             * Issue severity
             */
            severity: 'error' | 'warning' | 'notice';
            /**
             * Description of the issue
             */
            message: string;
            /**
             * HTML element causing the issue
             */
            element?: string;
            /**
             * Suggested fix
             */
            recommendation?: string;
        }>;
        /**
         * Total number of issues found
         */
        totalIssues: number;
        /**
         * Breakdown of issues by severity
         */
        issuesBySeverity: {
            /**
             * Number of errors
             */
            error: number;
            /**
             * Number of warnings
             */
            warning: number;
            /**
             * Number of notices
             */
            notice: number;
        };
    }

	/**
	 * Results of language level analysis
	 */
	public languageLevelResults?: {
        /**
         * Primary language detected in the document
         */
        primaryLanguage: string;
        /**
         * Various readability scores
         */
        readabilityScores: {
            /**
             * Flesch-Kincaid Grade Level score
             */
            fleschKincaid?: number;
            /**
             * SMOG Index score
             */
            smogIndex?: number;
            /**
             * Coleman-Liau Index score
             */
            colemanLiau?: number;
            /**
             * Automated Readability Index score
             */
            automatedReadability?: number;
            /**
             * Dale-Chall Readability score
             */
            daleChall?: number;
        };
        /**
         * Required education level to understand the document
         */
        educationLevel: 'elementary' | 'middle_school' | 'high_school' | 'college' | 'graduate' | 'professional';
        /**
         * Overall text complexity assessment
         */
        textComplexity: 'very_easy' | 'easy' | 'moderate' | 'difficult' | 'very_difficult';
        /**
         * Suggestions for improving readability
         */
        suggestions: Array<string>;
    }

	/**
	 * Retention period in days (0 for indefinite)
	 */
	public retentionPeriod: number

	/**
	 * Date when the retention period expires
	 */
	public retentionExpiry: string

	/**
	 * Legal basis for processing the data under GDPR
	 */
	public legalBasis?: 'consent' | 'contract' | 'legal_obligation' | 'vital_interests' | 'public_interest' | 'legitimate_interests'

	/**
	 * Name of the data controller
	 */
	public dataController: string

	/**
	 * Start time of the report generation process
	 */
	public startTime: string

	/**
	 * End time of the report generation process
	 */
	public endTime: string

	/**
	 * Duration of the report generation process in milliseconds
	 */
	public duration: number

	/**
	 * Error message if the report generation failed
	 */
	public errorMessage: string

	/**
	 * ID of the user who initiated the report generation
	 */
	public userId: string

	/**
	 * List of detected entities
	 */
	public entities?: Array<{
        /**
         * Type of entity (e.g., PERSON, EMAIL_ADDRESS)
         */
        entity_type: string;
        /**
         * The actual text that was identified
         */
        text: string;
        /**
         * Confidence score (0-1)
         */
        score: number;
    }>

	/**
	 * Creation timestamp
	 */
	public created: string

	/**
	 * Last update timestamp
	 */
	public updated: string

	/**
	 * Creates a new Report instance
	 *
	 * @param {TReport} report - Report data
	 * @return {Report} A new Report instance
	 */
	constructor(report: TReport = {}) {
		this.id = report.id || ''
		this.nodeId = report.nodeId || ''
		this.fileName = report.fileName || ''
		this.filePath = report.filePath || ''
		this.fileType = report.fileType || ''
		this.fileExtension = report.fileExtension || ''
		this.fileSize = report.fileSize || 0
		this.fileHash = report.fileHash || ''
		this.status = report.status || 'pending'
		this.riskLevel = report.riskLevel || 'unknown'
		this.anonymizationResults = report.anonymizationResults
		this.wcagComplianceResults = report.wcagComplianceResults
		this.languageLevelResults = report.languageLevelResults
		this.retentionPeriod = report.retentionPeriod || 0
		this.retentionExpiry = report.retentionExpiry || ''
		this.legalBasis = report.legalBasis
		this.dataController = report.dataController || ''
		this.startTime = report.startTime || ''
		this.endTime = report.endTime || ''
		this.duration = report.duration || 0
		this.errorMessage = report.errorMessage || ''
		this.userId = report.userId || ''
		this.entities = report.entities || []
		this.created = report.created || ''
		this.updated = report.updated || ''
	}

	/**
	 * Validates the report data
	 *
	 * @return {SafeParseReturnType<TReport, unknown>} Validation result
	 * @throws {Error} If validation fails
	 */
	public validate(): SafeParseReturnType<TReport, unknown> {
		// Define schema for entity found in document
		const entityFoundSchema = z.object({
			entityType: z.string(),
			text: z.string(),
			score: z.number(),
			count: z.number(),
		})

		// Define schema for WCAG compliance issue
		const issueSchema = z.object({
			principle: z.string(),
			guideline: z.string(),
			criterion: z.string(),
			severity: z.enum(['error', 'warning', 'notice']),
			message: z.string(),
			element: z.string().optional(),
			recommendation: z.string().optional(),
		})

		// Define schema for anonymization results
		const anonymizationResultsSchema = z.object({
			containsPersonalData: z.boolean(),
			entitiesFound: z.array(entityFoundSchema),
			totalEntitiesFound: z.number(),
			dataCategories: z.array(z.string()),
			anonymizationStatus: z.enum(['not_required', 'pending', 'in_progress', 'completed', 'failed']),
			anonymizationLogId: z.string().optional(),
		}).optional()

		// Define schema for WCAG compliance results
		const wcagComplianceResultsSchema = z.object({
			complianceLevel: z.enum(['A', 'AA', 'AAA', 'non-compliant']),
			complianceScore: z.number(),
			issues: z.array(issueSchema),
			totalIssues: z.number(),
			issuesBySeverity: z.object({
				error: z.number(),
				warning: z.number(),
				notice: z.number(),
			}),
		}).optional()

		// Define schema for readability scores
		const readabilityScoresSchema = z.object({
			fleschKincaid: z.number().optional(),
			smogIndex: z.number().optional(),
			colemanLiau: z.number().optional(),
			automatedReadability: z.number().optional(),
			daleChall: z.number().optional(),
		})

		// Define schema for language level results
		const languageLevelResultsSchema = z.object({
			primaryLanguage: z.string(),
			readabilityScores: readabilityScoresSchema,
			educationLevel: z.enum(['elementary', 'middle_school', 'high_school', 'college', 'graduate', 'professional']),
			textComplexity: z.enum(['very_easy', 'easy', 'moderate', 'difficult', 'very_difficult']),
			suggestions: z.array(z.string()),
		}).optional()

		// Define schema for detected entities
		const entitySchema = z.object({
			entity_type: z.string(),
			text: z.string(),
			score: z.number(),
		})

		// Define main schema for report
		const schema = z.object({
			id: z.string().optional(),
			nodeId: z.string().optional(),
			fileName: z.string().optional(),
			filePath: z.string().optional(),
			fileType: z.string().optional(),
			fileExtension: z.string().optional(),
			fileSize: z.number().optional(),
			fileHash: z.string().optional(),
			status: z.enum(['pending', 'processing', 'completed', 'failed']).optional(),
			riskLevel: z.enum(['low', 'medium', 'high', 'critical', 'unknown']).optional(),
			anonymizationResults: anonymizationResultsSchema,
			wcagComplianceResults: wcagComplianceResultsSchema,
			languageLevelResults: languageLevelResultsSchema,
			retentionPeriod: z.number().optional(),
			retentionExpiry: z.string().optional(),
			legalBasis: z.enum(['consent', 'contract', 'legal_obligation', 'vital_interests', 'public_interest', 'legitimate_interests']).optional(),
			dataController: z.string().optional(),
			startTime: z.string().optional(),
			endTime: z.string().optional(),
			duration: z.number().optional(),
			errorMessage: z.string().optional(),
			userId: z.string().optional(),
			entities: z.array(entitySchema).optional(),
			created: z.string().optional(),
			updated: z.string().optional(),
		})

		// Validate the report against the schema
		return schema.safeParse(this)
	}

	/**
	 * Checks if the document contains personal data
	 *
	 * @return {boolean} True if the document contains personal data
	 */
	public containsPersonalData(): boolean {
		return this.anonymizationResults?.containsPersonalData || false
	}

	/**
	 * Gets the data categories found in the document
	 *
	 * @return {Array<string>} Array of data categories
	 */
	public getDataCategories(): Array<string> {
		return this.anonymizationResults?.dataCategories || []
	}

	/**
	 * Gets the WCAG compliance level
	 *
	 * @return {string} WCAG compliance level
	 */
	public getComplianceLevel(): string {
		return this.wcagComplianceResults?.complianceLevel || 'unknown'
	}

	/**
	 * Gets the education level required to understand the document
	 *
	 * @return {string} Education level
	 */
	public getEducationLevel(): string {
		return this.languageLevelResults?.educationLevel || 'unknown'
	}

	/**
	 * Checks if the retention period has expired
	 *
	 * @return {boolean} True if the retention period has expired
	 */
	public isRetentionExpired(): boolean {
		// If no retention expiry date is set, return false
		if (!this.retentionExpiry) {
			return false
		}

		// Compare the expiry date with the current date
		const expiryDate = new Date(this.retentionExpiry)
		const currentDate = new Date()

		return expiryDate < currentDate
	}

	/**
	 * Gets the risk level of the document
	 *
	 * @return {string} Risk level
	 */
	public getRiskLevel(): string {
		return this.riskLevel || 'unknown'
	}

	/**
	 * Calculates the total number of entities found
	 *
	 * @return {number} Total number of entities
	 */
	public getTotalEntitiesCount(): number {
		return this.entities?.length || 0
	}

	/**
	 * Gets the file metadata as an object
	 *
	 * @return {object} File metadata
	 */
	public getFileMetadata(): object {
		return {
			fileName: this.fileName,
			filePath: this.filePath,
			fileType: this.fileType,
			fileExtension: this.fileExtension,
			fileSize: this.fileSize,
			fileHash: this.fileHash,
		}
	}

}
