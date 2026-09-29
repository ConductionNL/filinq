<?php

/**
 * Reads a veraPDF JSON report into the verdict Filinq stores.
 *
 * It reads only the fields named here (profile, compliant, rule summaries,
 * the font named in a failed check's context, the core version), so a
 * newer veraPDF that adds fields changes nothing, and one that drops a
 * field fails typed instead of reading as compliant. Rule descriptions,
 * error messages and every other text from the document are left behind:
 * the verdict holds rule references and font names only.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\VeraPdf
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\VeraPdf;

use OCA\Filinq\Exception\VeraPdfException;

/**
 * Pure: JSON in, verdict out.
 */
class VeraPdfReportParser {

	/**
	 * The most failed rules kept on a verdict.
	 */
	public const MAX_RULES = 25;

	/**
	 * The clauses that require every font program to be embedded:
	 * ISO 19005-1 6.3.4 and ISO 19005-2/3 6.2.11.4.1.
	 */
	private const FONT_EMBEDDING_CLAUSES = ['6.3.4', '6.2.11.4.1'];

	/**
	 * Parse one report.
	 *
	 * @param string $json The report veraPDF printed with --format json.
	 *
	 * @return array{flavour: string, compliant: bool, failedRuleCount: int, failedRules: array<int, array<string, mixed>>, fontsNotEmbedded: array<int, string>, fontsInImportedPages: bool, onlyFontRulesFailed: bool, validatorVersion: string} The verdict.
	 *
	 * @throws VeraPdfException When the report holds no verdict.
	 *
	 * @spec openspec/changes/archive/2026-09-29-verapdf-validation/tasks.md#task-2.1
	 */
	public function parse(string $json): array {
		$report = json_decode($json, true);
		if (is_array($report) === false || is_array($report['report']['jobs'][0] ?? null) === false) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_FAILED, message: 'veraPDF did not print a report.');
		}

		$job = $report['report']['jobs'][0];
		if (isset($job['taskException']) === true) {
			throw new VeraPdfException(
				reason: VeraPdfException::REASON_FAILED,
				message: 'veraPDF could not read the document: ' . (string) ($job['taskException']['exception'] ?? 'unknown')
			);
		}

		// veraPDF 1.24 and later print a list (one per profile), older ones one object.
		$result = ($job['validationResult'] ?? null);
		if (is_array($result) === true && array_is_list($result) === true) {
			$result = ($result[0] ?? null);
		}

		if (is_array($result) === false || is_bool($result['compliant'] ?? null) === false) {
			throw new VeraPdfException(reason: VeraPdfException::REASON_FAILED, message: 'The veraPDF report has no verdict.');
		}

		$rules = $this->failedRules(summaries: (array) ($result['details']['ruleSummaries'] ?? []));

		return [
			'flavour' => $this->flavour(profileName: (string) ($result['profileName'] ?? '')),
			'compliant' => ($result['compliant'] && $rules['rules'] === []),
			'failedRuleCount' => max((int) ($result['details']['failedRules'] ?? 0), count($rules['rules'])),
			'failedRules' => array_slice($rules['rules'], 0, self::MAX_RULES),
			'fontsNotEmbedded' => $rules['fonts'],
			'fontsInImportedPages' => $rules['imported'],
			'onlyFontRulesFailed' => ($rules['rules'] !== [] && $rules['otherFailed'] === false),
			'validatorVersion' => $this->version(report: $report),
		];

	}//end parse()

	/**
	 * The failed rules as references, and the fonts the embedding rules name.
	 *
	 * @param array<int, mixed> $summaries The rule summaries.
	 *
	 * @return array{rules: array<int, array<string, mixed>>, fonts: array<int, string>, imported: bool, otherFailed: bool} The failures.
	 */
	private function failedRules(array $summaries): array {
		$rules = [];
		$fonts = [];
		$imported = false;
		$otherFailed = false;
		foreach ($summaries as $summary) {
			if (is_array($summary) === false || strtolower((string) ($summary['status'] ?? ($summary['ruleStatus'] ?? ''))) !== 'failed') {
				continue;
			}

			$specification = (string) ($summary['specification'] ?? '');
			$clause = (string) ($summary['clause'] ?? '');
			$testNumber = (int) ($summary['testNumber'] ?? 0);
			$rules[] = [
				'ruleId' => $this->ruleId(specification: $specification, clause: $clause, testNumber: $testNumber),
				'specification' => $specification,
				'clause' => $clause,
				'testNumber' => $testNumber,
				'checksFailed' => (int) ($summary['failedChecks'] ?? 0),
			];
			if (in_array($clause, self::FONT_EMBEDDING_CLAUSES, true) === false) {
				$otherFailed = true;
				continue;
			}

			foreach ((array) ($summary['checks'] ?? []) as $check) {
				$context = (string) ($check['context'] ?? '');
				// A font reached through a form XObject sits on a page imported whole.
				$imported = ($imported === true || preg_match('/xObject\[\d+\].*font\[\d+\]/', $context) === 1);
				$font = $this->fontName(context: $context);
				if ($font !== '' && in_array($font, $fonts, true) === false) {
					$fonts[] = $font;
				}
			}
		}//end foreach

		return ['rules' => $rules, 'fonts' => $fonts, 'imported' => $imported, 'otherFailed' => $otherFailed];

	}//end failedRules()

	/**
	 * A stable rule id, such as ISO19005-3_6.2.11.4.1-1.
	 *
	 * @param string $specification The specification, such as "ISO 19005-3:2012".
	 * @param string $clause        The clause.
	 * @param int    $testNumber    The test number.
	 *
	 * @return string The id.
	 */
	private function ruleId(string $specification, string $clause, int $testNumber): string {
		$standard = (string) preg_replace('/[\s]|:\d{4}$/', '', $specification);

		return $standard . '_' . $clause . '-' . $testNumber;

	}//end ruleId()

	/**
	 * The font a check's context names, without its subset prefix.
	 *
	 * @param string $context Such as ".../font[0](ABCDEF+Arial)".
	 *
	 * @return string The font name, or '' when the context names none.
	 */
	private function fontName(string $context): string {
		if (preg_match_all('/font\[\d+\]\(([^()]{1,128})\)/', $context, $matches) < 1) {
			return '';
		}

		$name = (string) end($matches[1]);

		return (string) preg_replace('/^[A-Z]{6}\+/', '', $name);

	}//end fontName()

	/**
	 * The flavour a profile name validates, such as "3b".
	 *
	 * @param string $profileName Such as "PDF/A-3b validation profile".
	 *
	 * @return string The flavour, or '' when the name names none.
	 */
	private function flavour(string $profileName): string {
		if (preg_match('/PDF\/A-(\d)([abu])?/i', $profileName, $match) !== 1) {
			return '';
		}

		return $match[1] . strtolower($match[2] ?? '');

	}//end flavour()

	/**
	 * The validator version from the build information.
	 *
	 * @param array<string, mixed> $report The decoded report.
	 *
	 * @return string Such as "veraPDF 1.30.2", or "veraPDF" when not printed.
	 */
	private function version(array $report): string {
		foreach ((array) ($report['report']['buildInformation']['releaseDetails'] ?? []) as $release) {
			if (is_array($release) === true && ($release['id'] ?? '') === 'core') {
				return trim('veraPDF ' . (string) ($release['version'] ?? ''));
			}
		}

		return 'veraPDF';

	}//end version()
}//end class
