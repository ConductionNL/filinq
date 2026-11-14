<?php

/**
 * Filinq Run OCR Flow Operation
 *
 * Runs text recognition on a file through the same OCR run service, and so
 * the same admin settings, as every other OCR surface; the result is recorded
 * with triggeredBy flow. The run summary holds confidence and text length,
 * never the text.
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

use OCA\Filinq\Kernel\Contracts\OcrRunServiceInterface;
use OCA\Filinq\Flow\FlowOperationOutcome;
use OCA\Filinq\Flow\FlowOperationTrigger;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IL10N;
use OCP\IURLGenerator;

/**
 * The Run OCR operation.
 *
 * @category Flow
 * @package  OCA\Filinq\Flow\Operation
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
 */
class RunOcrOperation extends AbstractFileOperation {

	/**
	 * The triggeredBy value of an OCR result a Flow rule started.
	 */
	public const TRIGGER = 'flow';

	/**
	 * OCR refusals that fail the run; a non-candidate file is skipped instead.
	 *
	 * @var array<int, string>
	 */
	private const FAILING = [
		OcrRunServiceInterface::SKIP_DISABLED,
		OcrRunServiceInterface::SKIP_NO_TESSERACT,
		OcrRunServiceInterface::SKIP_NO_TEXT,
		OcrRunServiceInterface::SKIP_FAILED,
	];

	/**
	 * Constructor.
	 *
	 * @param IL10N                $l10n         Translates the name and description.
	 * @param IURLGenerator        $urlGenerator Builds the icon URL.
	 * @param FlowOperationTrigger $trigger      Queues the run.
	 * @param OcrRunServiceInterface $ocr          Runs and records OCR.
	 *
	 * @return void
	 */
	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		FlowOperationTrigger $trigger,
		private readonly OcrRunServiceInterface $ocr,
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
		return 'ocr';

	}//end key()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated name.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Run OCR');

	}//end getDisplayName()

	/**
	 * {@inheritDoc}
	 *
	 * @return string The translated description.
	 *
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.2
	 */
	public function getDescription(): string {
		return $this->l10n->t('Recognise the text of a scanned document or image, with the OCR settings of Filinq');

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
	 * @spec openspec/changes/flow-operations/tasks.md#task-2.5
	 */
	public function execute(File $file, array $config, Folder $ownerHome): FlowOperationOutcome {
		$result = $this->ocr->run(file: $file, trigger: self::TRIGGER);
		if ($result['processed'] === true) {
			$stored = (array) ($result['result'] ?? []);
			return FlowOperationOutcome::succeeded(
				summary: [
					'confidence' => (float) ($stored['confidence'] ?? 0.0),
					'textLength' => (int) ($stored['textLength'] ?? mb_strlen($result['text'])),
				]
			);
		}

		$reason = (string) ($result['reason'] ?? OcrRunServiceInterface::SKIP_FAILED);
		if (in_array($reason, self::FAILING, true) === true) {
			return FlowOperationOutcome::failed(reason: $reason, message: $this->message(reason: $reason));
		}

		return FlowOperationOutcome::skipped(reason: $reason);

	}//end execute()

	/**
	 * The owner-facing message for an OCR refusal.
	 *
	 * @param string $reason The reason.
	 *
	 * @return string The message.
	 */
	private function message(string $reason): string {
		return match ($reason) {
			OcrRunServiceInterface::SKIP_DISABLED => $this->l10n->t('Text recognition is turned off on this server'),
			OcrRunServiceInterface::SKIP_NO_TESSERACT => $this->l10n->t('Text recognition is not installed on this server'),
			OcrRunServiceInterface::SKIP_NO_TEXT => $this->l10n->t('No text was found in this document'),
			default => $this->l10n->t('Text recognition failed'),
		};

	}//end message()
}//end class
