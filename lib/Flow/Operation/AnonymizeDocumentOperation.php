<?php

/**
 * Filinq Anonymise Document Flow Operation
 *
 * Anonymisation intake on a Flow trigger: extraction and detection (with the
 * grondslag proposals and policy matching they hang), so the document enters
 * the human review queue. It never writes a review, never passes an override
 * and never produces the anonymised copy; the run summary carries counts per
 * entity type, never an entity value.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Flow\Operation;

use OCA\Filinq\Exception\ProhibitionGateException;
use OCA\Filinq\Kernel\Contracts\AnonymizationServiceInterface;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\FlowOperationTrigger;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;
use Throwable;

/**
 * The Anonymise document operation.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
 */
class AnonymizeDocumentOperation extends AbstractFileOperation {

	/**
	 * Reason: an absolute publication prohibition applies to the document.
	 */
	public const REASON_PROHIBITION = 'prohibition_gate';

	/**
	 * Reason: extraction or detection failed.
	 */
	public const REASON_DETECTION = 'detection_failed';

	/**
	 * Constructor.
	 *
	 * @param IL10N                $l10n         Translates the name and description.
	 * @param IURLGenerator        $urlGenerator Builds the icon URL.
	 * @param FlowOperationTrigger $trigger      Queues the run.
	 * @param AnonymizationServiceInterface $anonymization Runs the intake and asks the prohibition gate.
	 *
	 * @return void
	 */
	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		FlowOperationTrigger $trigger,
		private readonly AnonymizationServiceInterface $anonymization,
	) {
		parent::__construct(l10n: $l10n, urlGenerator: $urlGenerator, trigger: $trigger);

	}//end __construct()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The key.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function key(): string {
		return 'anonymize';

	}//end key()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated name.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Anonymise document');

	}//end getDisplayName()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated description.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDescription(): string {
		return $this->l10n->t('Detect the personal data in a document and put it in the anonymisation review queue; a person decides what is redacted');

	}//end getDescription()

	/**
	 * {@inheritDoc}
	 *
	 * @param File                 $file      The file.
	 * @param array<string, mixed> $config    No options.
	 * @param Folder               $ownerHome Unused.
	 *
	 * @return FlowOperationOutcome The outcome.
	 *
	 * @SuppressWarnings(PHPMD.StaticAccess) FlowOperationOutcome is a stateless helper or named
	 * constructor; an instance would add nothing.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
	 */
	public function execute(File $file, array $config, Folder $ownerHome): FlowOperationOutcome {
		$fileId = (int) $file->getId();

		try {
			$detected = $this->anonymization->extractAndDetectEntities(fileId: $fileId);
		} catch (ProhibitionGateException $e) {
			return FlowOperationOutcome::failed(reason: self::REASON_PROHIBITION, message: $e->getMessage());
		} catch (Throwable $e) {
			return FlowOperationOutcome::failed(
				reason: self::REASON_DETECTION,
				message: $this->l10n->t('Personal data could not be detected in this document')
			);
		}

		$counts = FileEntityStatsService::countByType(entities: (array) ($detected['entities'] ?? []));
		$summary = [
			'entityCounts' => $counts,
			'entityCount' => array_sum($counts),
			'reviewRequired' => true,
		];

		if ($this->prohibited(fileId: $fileId) === true) {
			return FlowOperationOutcome::failed(
				reason: self::REASON_PROHIBITION,
				message: $this->l10n->t('A publication prohibition applies to this document; it is in the review queue and nothing was published'),
				summary: $summary
			);
		}

		return FlowOperationOutcome::succeeded(summary: $summary);

	}//end execute()

	/**
	 * Whether an absolute publication prohibition applies. A gate that cannot
	 * be asked counts as applying: the run fails closed.
	 *
	 * @param int $fileId The file.
	 *
	 * @return bool True when the gate refuses the file.
	 */
	private function prohibited(int $fileId): bool {
		try {
			return $this->anonymization->absoluteProhibitionViolations(fileId: $fileId) !== [];
		} catch (Throwable) {
			return true;
		}

	}//end prohibited()
}//end class
