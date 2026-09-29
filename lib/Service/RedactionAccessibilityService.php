<?php

/**
 * Accessibility after redaction
 *
 * A redaction can destroy the tag structure a screen reader reads, and a Woo
 * publication must stay accessible. This service asks OpenRegister to keep
 * the structure, reads back whether it did, checks the claim with veraPDF
 * when veraPDF is there, and decides what the publication gate does with a
 * copy that lost it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-2.2
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCP\IAppConfig;
use Throwable;

/**
 * Maps OpenRegister's structure report to a state and gates publication on it.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class RedactionAccessibilityService {
	/**
	 * Whether redaction asks OpenRegister to keep the tag structure ('true' by default).
	 */
	public const CFG_PRESERVE_DEFAULT = 'filinq.redaction.preserve_tags_default';

	/**
	 * What publication does with a copy that lost its structure: warn (default), block or off.
	 */
	public const CFG_GATE = 'filinq.redaction.accessibility_gate';

	/**
	 * The loss reason for a claim veraPDF did not confirm.
	 */
	public const REASON_VERAPDF = 'verapdf-not-pdfua';

	/**
	 * Loss reasons that mean there was no structure to keep.
	 *
	 * @var string[]
	 */
	private const NOTHING_TO_KEEP = ['input-not-tagged'];

	/**
	 * Constructor.
	 *
	 * @param IAppConfig          $appConfig The app configuration.
	 * @param VeraPdfService|null $veraPdf   The validator; without it the engine's report stands.
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ?VeraPdfService $veraPdf = null,
	) {

	}//end __construct()

	/**
	 * What to ask OpenRegister for: keep the structure (true) or not (false).
	 *
	 * @return bool
	 *
	 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-2.1
	 */
	public function preserveRequested(): bool {
		return $this->appConfig->getValueString('filinq', self::CFG_PRESERVE_DEFAULT, 'true') !== 'false';

	}//end preserveRequested()

	/**
	 * The outcome of one redaction, as the anonymizationLink records it.
	 *
	 * No report is `unknown`, never `preserved`. A PDF output that claims
	 * preservation is checked against PDF/UA-1 by veraPDF when veraPDF is
	 * installed and the copy claims PDF/UA; a failed check turns it `degraded`. A veraPDF that cannot
	 * run leaves the engine's claim standing, without `veraPdfVerified`.
	 *
	 * @param array<string, mixed>|null $report    OpenRegister's structure report.
	 * @param string|null               $pdfBytes  The redacted copy when it is a PDF.
	 *
	 * @return array{state: string, requested?: bool, preserved?: bool, tagCountBefore?: int,
	 *               tagCountAfter?: int, lossReasons?: string[], veraPdfVerified?: bool}
	 *
	 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-2.2
	 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-2.4
	 */
	public function assess(?array $report, ?string $pdfBytes = null): array {
		if ($report === null || is_bool($report['preserved'] ?? null) === false) {
			return ['state' => 'unknown'];
		}

		$outcome = [
			'state' => 'degraded',
			'requested' => (($report['requested'] ?? false) === true),
			'preserved' => $report['preserved'],
			'tagCountBefore' => max(0, (int) ($report['tagCountBefore'] ?? 0)),
			'tagCountAfter' => max(0, (int) ($report['tagCountAfter'] ?? 0)),
			'lossReasons' => array_values(array_map('strval', (array) ($report['lossReasons'] ?? []))),
		];

		if ($outcome['preserved'] === true) {
			$outcome['state'] = 'preserved';
			return $this->verify(outcome: $outcome, pdfBytes: $pdfBytes);
		}

		if (array_intersect($outcome['lossReasons'], self::NOTHING_TO_KEEP) !== [] || $outcome['tagCountBefore'] === 0) {
			$outcome['state'] = 'not-applicable';
		}

		return $outcome;

	}//end assess()

	/**
	 * {@see assess()} for a redacted file node: its bytes go to veraPDF when it is a PDF.
	 *
	 * @param array<string, mixed>|null $report OpenRegister's structure report.
	 * @param mixed                     $output The redacted copy's node.
	 *
	 * @return array<string, mixed>
	 *
	 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-2.4
	 */
	public function assessOutput(?array $report, mixed $output): array {
		$bytes = null;
		try {
			if (is_object($output) === true
				&& method_exists($output, 'getName') === true
				&& str_ends_with(strtolower((string) $output->getName()), '.pdf') === true
				&& method_exists($output, 'getContent') === true
			) {
				$bytes = (string) $output->getContent();
			}
		} catch (Throwable $e) {
			$bytes = null;
		}

		return $this->assess(report: $report, pdfBytes: $bytes);

	}//end assessOutput()

	/**
	 * What the publication gate says about a redacted copy.
	 *
	 * `warn` (default) lets the hand-off go and says why; `block` stops it
	 * until someone records a reason; `off` records only. Unknown counts as
	 * degraded.
	 *
	 * @param string|null $state          The copy's state, null when no redaction recorded one.
	 * @param string      $overrideReason The recorded override reason.
	 *
	 * @return array{clear: bool, warning: string|null}
	 *
	 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-2.3
	 */
	public function gate(?string $state, string $overrideReason = ''): array {
		$mode = $this->appConfig->getValueString('filinq', self::CFG_GATE, 'warn');
		$lost = in_array($state ?? 'unknown', ['degraded', 'unknown'], true);
		if ($lost === false || $mode === 'off') {
			return ['clear' => true, 'warning' => null];
		}

		$warning = 'The redacted copy lost its accessibility (' . ($state ?? 'unknown') . '): screen readers may not read it';
		if ($mode === 'block' && trim($overrideReason) === '') {
			return ['clear' => false, 'warning' => $warning . '. Record a reason to publish it anyway'];
		}

		return ['clear' => true, 'warning' => $warning];

	}//end gate()

	/**
	 * Check a preserved claim with veraPDF, when it can run.
	 *
	 * @param array<string, mixed> $outcome  The outcome so far.
	 * @param string|null          $pdfBytes The redacted PDF.
	 *
	 * @return array<string, mixed>
	 */
	private function verify(array $outcome, ?string $pdfBytes): array {
		if ($pdfBytes === null || $this->veraPdf === null || $this->veraPdf->isAvailable() === false) {
			return $outcome;
		}

		// Only a copy that claims PDF/UA can be held to it: an untagged-then-
		// tagged source that never claimed PDF/UA would fail for reasons the
		// redaction did not cause.
		if (str_contains($pdfBytes, 'pdfuaid:part') === false) {
			return $outcome;
		}

		try {
			$verdict = $this->veraPdf->validateBytes(bytes: $pdfBytes, flavour: 'ua1');
		} catch (Throwable $e) {
			return $outcome;
		}

		$outcome['veraPdfVerified'] = ($verdict['compliant'] === true);
		if ($outcome['veraPdfVerified'] === false) {
			$outcome['state'] = 'degraded';
			$outcome['lossReasons'][] = self::REASON_VERAPDF;
		}

		return $outcome;

	}//end verify()
}//end class
