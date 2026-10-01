<?php

/**
 * The wizard check inside document generation
 *
 * A generate request that carries `options.wizardContext` came from a wizard
 * run. Before anything renders, the answers are checked against the wizard as
 * stored now: it must exist, front this template and be active, and every
 * visible required question must be answered correctly. A refusal is a 422
 * with an error per question and nothing is generated or logged. When the
 * answers pass, the `generatedDocument` entry records the wizard id, the
 * wizard's OpenRegister version and the visible answers, so the interview
 * behind a document can be read back. Requests without a wizard context never
 * reach this class.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Wizard
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/guided-document-wizard/tasks.md#2-4
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Wizard;

/**
 * Validates a wizard run and produces its audit fields.
 */
class WizardGenerationGate {

	/**
	 * Constructor.
	 *
	 * @param WizardRepository $repository The stored wizards.
	 * @param WizardAnswers    $answers    Answer validation and translation.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly WizardRepository $repository,
		private readonly WizardAnswers $answers,
	) {

	}//end __construct()

	/**
	 * Check a wizard run and return the fields for its generatedDocument entry.
	 *
	 * @param string                           $templateId The template being generated.
	 * @param array<int, array<string, mixed>> $dataRefs   The request's dataRefs.
	 * @param mixed                            $context    The request's options.wizardContext.
	 *
	 * @return array{wizardContext: array{wizardId: string, wizardVersion: string, answers: array<string, mixed>}}
	 *
	 * @throws WizardRefused 422 when the wizard is unknown, fronts another template, is inactive,
	 *                       or the answers do not pass.
	 *
	 * @spec openspec/changes/guided-document-wizard/tasks.md#2-4
	 */
	public function check(string $templateId, array $dataRefs, mixed $context): array {
		if (is_array($context) === false) {
			$context = [];
		}

		$wizard = $this->repository->find(uuid: (string) ($context['wizardId'] ?? ''));
		if ($wizard === null) {
			throw new WizardRefused(message: 'The wizard of this run was not found', code: 422, errors: ['wizardId' => 'Unknown wizard.']);
		}

		if ((string) ($wizard['templateId'] ?? '') !== $templateId || ($wizard['active'] ?? true) === false) {
			throw new WizardRefused(message: 'The wizard is not the active wizard of this template', code: 422, errors: ['wizardId' => 'Not the active wizard of this template.']);
		}

		$given = ($context['answers'] ?? []);
		if (is_array($given) === false) {
			$given = [];
		}

		$errors = $this->answers->errors(wizard: $wizard, answers: $given, dataRefs: $dataRefs);
		if ($errors !== []) {
			throw new WizardRefused(message: 'The wizard answers are not complete', code: 422, errors: $errors);
		}

		return ['wizardContext' => $this->answers->translate(wizard: $wizard, answers: $given)['wizardContext']];

	}//end check()
}//end class
