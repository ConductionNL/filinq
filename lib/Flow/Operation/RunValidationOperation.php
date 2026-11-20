<?php

/**
 * Filinq Run Validation Checks Flow Operation
 *
 * Runs the document validation checks of a document-type profile on a file
 * and records the findings and the verdict. A blocking finding fails the run,
 * so the owner is told; warnings leave it succeeded with the findings.
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

use OCA\Filinq\Kernel\Contracts\DocumentValidationServiceInterface;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\FlowOperationTrigger;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCA\Filinq\Service\Validation\ValidationProfileResolver;
use Throwable;
use UnexpectedValueException;

/**
 * The Run validation checks operation.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
 */
class RunValidationOperation extends AbstractFileOperation {

	/**
	 * Reason: a blocking finding.
	 */
	public const REASON_FAILED = 'validation_failed';

	/**
	 * Reason: the checks could not run.
	 */
	public const REASON_ERROR = 'validation_error';

	/**
	 * Constructor.
	 *
	 * @param IL10N                $l10n         Translates the name and description.
	 * @param IURLGenerator        $urlGenerator Builds the icon URL.
	 * @param FlowOperationTrigger $trigger      Queues the run.
	 * @param DocumentValidationServiceInterface $validation Runs the checks.
	 * @param ValidationProfileResolver          $profiles   Knows the configured profiles.
	 *
	 * @return void
	 */
	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		FlowOperationTrigger $trigger,
		private readonly DocumentValidationServiceInterface $validation,
		private readonly ValidationProfileResolver $profiles,
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
		return 'validate';

	}//end key()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated name.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Run validation checks');

	}//end getDisplayName()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated description.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDescription(): string {
		return $this->l10n->t('Check a document against the validation profile of a document type and record what is wrong with it');

	}//end getDescription()

	/**
	 * Only `documentType` is accepted, and only a configured profile key.
	 *
	 * @param array<string, mixed> $config The decoded options.
	 *
	 * @return void
	 *
	 * @throws UnexpectedValueException For an unknown option or document type.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	protected function validateConfig(array $config): void {
		if (array_diff(array_keys($config), ['documentType']) !== []) {
			throw new UnexpectedValueException($this->l10n->t('Only a document type can be set for this operation'));
		}

		$type = $config['documentType'] ?? '';
		if (is_string($type) === false) {
			throw new UnexpectedValueException($this->l10n->t('The document type must be text'));
		}

		if ($type !== '' && in_array($type, $this->profiles->documentTypes(), true) === false) {
			throw new UnexpectedValueException($this->l10n->t('No validation profile exists for document type "%s"', [$type]));
		}

	}//end validateConfig()

	/**
	 * {@inheritDoc}
	 *
	 * @param File                 $file      The file.
	 * @param array<string, mixed> $config    `documentType`, optional.
	 * @param Folder               $ownerHome Unused.
	 *
	 * @return FlowOperationOutcome The outcome.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
	 */
	public function execute(File $file, array $config, Folder $ownerHome): FlowOperationOutcome {
		$type = (string) ($config['documentType'] ?? '');
		$documentType = null;
		$profile = 'default';
		if ($type !== '') {
			$documentType = $type;
			$profile = $type;
		}

		try {
			$result = $this->validation->validate(file: $file, record: [], documentType: $documentType);
		} catch (Throwable) {
			return FlowOperationOutcome::failed(
				reason: self::REASON_ERROR,
				message: $this->l10n->t('The validation checks could not run on this document')
			);
		}

		$findings = array_values((array) ($result['validationFindings'] ?? []));
		$verdict = (string) ($result['validationStatus'] ?? '');
		$summary = [
			'documentType' => $profile,
			'verdict' => $verdict,
			'findingCount' => count($findings),
			'findings' => $findings,
		];

		if ($verdict === DocumentValidationServiceInterface::STATUS_FAILED) {
			return FlowOperationOutcome::failed(
				reason: self::REASON_FAILED,
				message: $this->l10n->t('The document does not pass the validation checks'),
				summary: $summary
			);
		}

		return FlowOperationOutcome::succeeded(summary: $summary);

	}//end execute()
}//end class
