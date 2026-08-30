<?php

/**
 * Confidentiality Label Service
 *
 * Reads a file's existing confidentiality classification — the TSCP/BAILS
 * labels assigned by Nextcloud's optional `files_confidential` app,
 * surfaced as Nextcloud system tags — as a read-only sensitivity signal for
 * anonymisation appraisal. Availability-guarded: returns null (never
 * throws) whenever `files_confidential` is not installed, the file carries
 * no tag matching the configured vocabulary, or the system-tag API fails.
 * Mirrors Filinq's existing optional-dependency idiom
 * (MetadataService::getObjectService()) but binds only to Nextcloud's
 * public system-tag API (ISystemTagManager / ISystemTagObjectMapper) —
 * never to files_confidential internals.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service for reading a file's confidentiality label as a read-only signal.
 *
 * Read-only, no policy/enforcement of its own — see design.md (Non-Goals).
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 */
class ConfidentialityLabelService {

	/**
	 * App id of the optional files_confidential app this service integrates with.
	 *
	 * @var string
	 */
	private const FILES_CONFIDENTIAL_APP_ID = 'files_confidential';

	/**
	 * Nextcloud object type used by ISystemTagObjectMapper for file nodes.
	 *
	 * @var string
	 */
	private const TAG_OBJECT_TYPE = 'files';

	/**
	 * App config key for the admin-configurable label vocabulary
	 * (tag/label name => normalised level, JSON-encoded).
	 *
	 * @var string
	 */
	public const VOCABULARY_KEY = 'filinq.confidentiality.label_vocabulary';

	/**
	 * Default TSCP/BAILS-style confidentiality vocabulary, seeded when the
	 * admin has not configured `filinq.confidentiality.label_vocabulary`
	 * (design.md Open Questions — adjustable without code change).
	 *
	 * @var array<string, int>
	 */
	public const DEFAULT_VOCABULARY = [
		'Public' => 0,
		'Internal' => 1,
		'Confidential' => 2,
		'Secret' => 3,
	];

	/**
	 * Constructor for ConfidentialityLabelService
	 *
	 * @param LoggerInterface $logger Logger for diagnostic (non-fatal) reporting
	 * @param IAppManager $appManager App manager, used to guard on files_confidential presence
	 * @param IAppConfig $appConfig App configuration for the label vocabulary
	 * @param ISystemTagManager $tagManager Nextcloud's public system-tag manager
	 * @param ISystemTagObjectMapper $tagObjectMapper Nextcloud's public system-tag/object mapper
	 *
	 * @return void
	 */
	public function __construct(
		private readonly LoggerInterface $logger,
		private readonly IAppManager $appManager,
		private readonly IAppConfig $appConfig,
		private readonly ISystemTagManager $tagManager,
		private readonly ISystemTagObjectMapper $tagObjectMapper,
	) {

	}//end __construct()

	/**
	 * Resolve a file's confidentiality label, if any.
	 *
	 * Returns null (never throws) when `files_confidential` is not
	 * installed, the file carries no system tag that matches the
	 * configured vocabulary, or the system-tag API fails for any reason.
	 * When several assigned tags match the vocabulary, the highest-level
	 * match wins.
	 *
	 * @param int $fileId Nextcloud file id
	 *
	 * @return ConfidentialityLabel|null The resolved label, or null when no signal applies
	 *
	 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-read-a-files-confidentiality-label-availability-guarded-req-ddfcl-001
	 */
	public function getLabelForFile(int $fileId): ?ConfidentialityLabel {
		if (in_array(self::FILES_CONFIDENTIAL_APP_ID, $this->appManager->getInstalledApps(), true) === false) {
			return null;
		}

		try {
			$objectId = (string)$fileId;
			$tagIdsByObject = $this->tagObjectMapper->getTagIdsForObjects([$objectId], self::TAG_OBJECT_TYPE);
			$tagIds = $tagIdsByObject[$objectId] ?? [];
			if (empty($tagIds) === true) {
				return null;
			}

			$vocabulary = $this->getVocabulary();
			if (empty($vocabulary) === true) {
				return null;
			}

			$tags = $this->tagManager->getTagsByIds($tagIds);

			$best = null;
			foreach ($tags as $tag) {
				$name = $tag->getName();
				if (array_key_exists($name, $vocabulary) === false) {
					continue;
				}

				$level = (int)$vocabulary[$name];
				if ($best === null || $level > $best->getLevel()) {
					$best = new ConfidentialityLabel($name, $level);
				}
			}

			return $best;
		} catch (Throwable $e) {
			// Fail-safe: a signal read must never break anonymisation.
			// Fail-open is correct here because the label only adds
			// prominence — it never relaxes a control (design.md D1).
			$this->logger->debug(
				'Confidentiality label read failed; treating as no label',
				[
					'fileId' => $fileId,
					'exception' => $e,
				]
			);
			return null;
		}//end try

	}//end getLabelForFile()

	/**
	 * Read the admin-configured label vocabulary, falling back to the
	 * default TSCP/BAILS names when unset or unreadable.
	 *
	 * @return array<string, int> Map of label/tag name to normalised level
	 */
	private function getVocabulary(): array {
		$raw = $this->appConfig->getValueString('filinq', self::VOCABULARY_KEY, '');
		if ($raw === '') {
			return self::DEFAULT_VOCABULARY;
		}

		$decoded = json_decode($raw, true);
		if (is_array($decoded) === false || empty($decoded) === true) {
			return self::DEFAULT_VOCABULARY;
		}

		return $decoded;
	}//end getVocabulary()
}//end class
