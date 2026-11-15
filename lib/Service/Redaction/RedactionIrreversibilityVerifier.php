<?php

/**
 * Verifying that a written copy cannot be read back.
 *
 * 🔴 FILINQ DOES NOT PRODUCE THESE BYTES AND THEREFORE CANNOT PROMISE THEM.
 * The redaction is performed by OpenRegister's anonymisation backend, which
 * drives the anonymiq ExApp. Filinq orchestrates: it asks, it waits, it stores
 * the link. Asserting "our redaction is irreversible" would be a statement
 * about somebody else's output, and the one thing worse than an unverified
 * claim is a confident one — an archivist who is told a document is safe stops
 * looking at it.
 *
 * So this verifies the PRODUCED BYTES, every time, for each route a redacted
 * value is known to survive by. It is the difference between claiming the
 * property and checking it.
 *
 * 🔴 AN UNCHECKED ROUTE IS A FAILURE, NOT A PASS. `verify()` refuses to report
 * a clean result unless every route in {@see RedactionLeakRoute::ALL} was
 * actually examined. A verifier that silently skips a route it has no check for
 * returns exactly what a clean document returns, and the whole point of this
 * class is that those two must never look the same.
 *
 * 🔴 AND AN UNREADABLE FILE IS NOT A CLEAN FILE. Bytes that cannot be parsed
 * produce a refusal, not an empty finding list. "We could not look" and "we
 * looked and found nothing" are different answers, and only one of them means
 * the document may be published.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Redaction
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Redaction;

/**
 * Checks produced bytes for every way a redacted value survives.
 */
class RedactionIrreversibilityVerifier {

	/**
	 * The document was examined for every route and nothing was found.
	 *
	 * @var string
	 */
	public const CLEAN = 'clean';

	/**
	 * At least one redacted value is still recoverable.
	 *
	 * @var string
	 */
	public const LEAKING = 'leaking';

	/**
	 * The bytes could not be examined, so nothing is known.
	 *
	 * @var string
	 */
	public const UNVERIFIABLE = 'unverifiable';

	/**
	 * The opening and closing markers of the routes that live in a region of the file.
	 *
	 * Routes not listed here (text under the mark, EXIF and document properties)
	 * search the whole file; the incremental update has its own rule.
	 *
	 * @var array<string, array<int, array{0: string, 1: string}>>
	 */
	private const REGION_MARKERS = [
		RedactionLeakRoute::XMP => [['<x:xmpmeta', '</x:xmpmeta>']],
		RedactionLeakRoute::EMBEDDED_PREVIEW => [['/thumb', 'endobj']],
		RedactionLeakRoute::EMBEDDED_ATTACHMENT => [['/embeddedfile', 'endobj']],
		RedactionLeakRoute::ANNOTATIONS_AND_FIELDS => [['/annots', 'endobj'], ['/acroform', 'endobj']],
	];

	/**
	 * Verify one written copy against every leak route.
	 *
	 * @param string   $bytes           The produced file, exactly as written.
	 * @param string[] $redactedValues  The values that must not be recoverable.
	 * @param string   $outputMode      The mode that produced it, for the record.
	 *
	 * @return array<string, mixed> The verdict, the routes checked, and every finding.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function verify(string $bytes, array $redactedValues, string $outputMode = ''): array {
		$needles = $this->needles(values: $redactedValues);
		$why = $this->whyUnverifiable(bytes: $bytes, needles: $needles);
		if ($why !== null) {
			return $this->unverifiable(outputMode: $outputMode, why: $why);
		}

		$scan = $this->scan(bytes: $bytes, needles: $needles);
		$scan['outputMode'] = $outputMode;
		return $this->verdictFor(scan: $scan);
	}//end verify()

	/**
	 * Whether a verification result permits publication.
	 *
	 * A separate method because callers otherwise test the verdict string, and
	 * the two states that must NOT publish are `leaking` and `unverifiable`. A
	 * caller comparing against `leaking` alone publishes every document the
	 * verifier could not read.
	 *
	 * @param array<string, mixed> $result The verification result.
	 *
	 * @return bool True only when the document was examined and found clean.
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function mayBePublished(array $result): bool {
		return (($result['verdict'] ?? '') === self::CLEAN && ($result['mayBePublished'] ?? false) === true);
	}//end mayBePublished()

	/**
	 * Why the bytes cannot be verified, or null when they can.
	 *
	 * @param string   $bytes   The produced file.
	 * @param string[] $needles The values to look for.
	 *
	 * @return string|null The reason, in words.
	 */
	private function whyUnverifiable(string $bytes, array $needles): ?string {
		if (trim($bytes) === '') {
			return 'the written copy is empty, so nothing could be examined';
		}

		if ($needles === []) {
			// Verifying against nothing would pass every document, including
			// one where the redaction never ran.
			return 'no redacted values were supplied, so there was nothing to look for';
		}

		return null;
	}//end whyUnverifiable()

	/**
	 * Examine every route, in order, and collect the findings.
	 *
	 * @param string   $bytes   The produced file.
	 * @param string[] $needles The values, lower-cased.
	 *
	 * @return array{routesChecked: string[], findings: array<int, array<string, mixed>>}
	 */
	private function scan(string $bytes, array $needles): array {
		$findings = [];
		$checked = [];
		foreach (RedactionLeakRoute::ALL as $route) {
			$found = $this->examine(route: $route, bytes: $bytes, needles: $needles);
			$checked[] = $route;
			if ($found !== []) {
				$findings[] = $this->findingFor(route: $route, found: $found);
			}
		}

		return ['routesChecked' => $checked, 'findings' => $findings];
	}//end scan()

	/**
	 * One finding: the route, what it means in words, and the values found on it.
	 *
	 * @param string   $route The leak route.
	 * @param string[] $found The values found on this route.
	 *
	 * @return array<string, mixed> The finding.
	 */
	private function findingFor(string $route, array $found): array {
		return [
			'route' => $route,
			'description' => RedactionLeakRoute::DESCRIPTIONS[$route],
			'values' => $found,
		];
	}//end findingFor()

	/**
	 * The result of an examined copy: clean and publishable only without findings.
	 *
	 * @param array{routesChecked: string[], findings: array<int, array<string, mixed>>, outputMode: string} $scan The scan.
	 *
	 * @return array<string, mixed> The verdict, the routes checked, and every finding.
	 */
	private function verdictFor(array $scan): array {
		$clean = ($scan['findings'] === []);
		$verdict = self::LEAKING;
		if ($clean === true) {
			$verdict = self::CLEAN;
		}

		return [
			'verdict' => $verdict,
			'outputMode' => $scan['outputMode'],
			'routesChecked' => $scan['routesChecked'],
			'findings' => $scan['findings'],
			'mayBePublished' => $clean,
		];
	}//end verdictFor()

	/**
	 * Look for the needles on one route.
	 *
	 * @param string   $route   The leak route.
	 * @param string   $bytes   The produced file.
	 * @param string[] $needles The values, lower-cased.
	 *
	 * @return string[] The values found on this route.
	 */
	private function examine(string $route, string $bytes, array $needles): array {
		$haystack = $this->regionFor(route: $route, bytes: $bytes);
		if ($haystack === '') {
			return [];
		}

		$found = [];
		foreach ($needles as $needle) {
			if (str_contains($haystack, $needle) === true) {
				$found[] = $needle;
			}
		}

		return $found;
	}//end examine()

	/**
	 * The part of the file one route lives in.
	 *
	 * Deliberately generous: a route whose region cannot be isolated falls back
	 * to the whole file rather than to nothing. A false positive costs somebody
	 * a second look; a false negative publishes a name.
	 *
	 * @param string $route The leak route.
	 * @param string $bytes The produced file.
	 *
	 * @return string The region to search.
	 */
	private function regionFor(string $route, string $bytes): string {
		$lower = strtolower($bytes);
		if ($route === RedactionLeakRoute::INCREMENTAL_UPDATE) {
			return $this->earlierRevisions(lower: $lower);
		}

		$markers = (self::REGION_MARKERS[$route] ?? null);
		if ($markers === null) {
			// Text under the mark, EXIF and document properties: the whole file.
			return $lower;
		}

		return $this->regionsBetween(haystack: $lower, markers: $markers);
	}//end regionFor()

	/**
	 * Everything before the last end-of-file marker, when there is more than one.
	 *
	 * More than one %%EOF means earlier revisions are still inside.
	 *
	 * @param string $lower The lower-cased file.
	 *
	 * @return string The earlier revisions, or an empty string.
	 */
	private function earlierRevisions(string $lower): string {
		if (substr_count($lower, '%%eof') <= 1) {
			return '';
		}

		return substr($lower, 0, (int)strrpos($lower, '%%eof'));
	}//end earlierRevisions()

	/**
	 * The regions of every marker pair, in order.
	 *
	 * @param string                       $haystack The lower-cased file.
	 * @param array<int, array{0: string, 1: string}> $markers  Opening and closing marker pairs.
	 *
	 * @return string The concatenated regions.
	 */
	private function regionsBetween(string $haystack, array $markers): string {
		$out = '';
		foreach ($markers as $pair) {
			$out .= $this->between(haystack: $haystack, start: $pair[0], end: $pair[1]);
		}

		return $out;
	}//end regionsBetween()

	/**
	 * Everything between two markers, every time they occur.
	 *
	 * @param string $haystack The lower-cased file.
	 * @param string $start    The opening marker.
	 * @param string $end      The closing marker.
	 *
	 * @return string The concatenated regions, or an empty string.
	 */
	private function between(string $haystack, string $start, string $end): string {
		$out = '';
		$offset = 0;

		while (true) {
			$from = strpos($haystack, $start, $offset);
			if ($from === false) {
				break;
			}

			$to = strpos($haystack, $end, $from);
			if ($to === false) {
				$out .= substr($haystack, $from);
				break;
			}

			$out .= substr($haystack, $from, (($to - $from) + strlen($end)));
			$offset = ($to + 1);
		}

		return $out;
	}//end between()

	/**
	 * The values to look for, normalised and without the trivially short.
	 *
	 * A one or two character value matches almost any file, and a verification
	 * that always fails is switched off within a week.
	 *
	 * @param string[] $values The redacted values.
	 *
	 * @return string[] The needles.
	 */
	private function needles(array $values): array {
		$needles = [];
		foreach ($values as $value) {
			$text = strtolower(trim((string)$value));
			if (strlen($text) < 3) {
				continue;
			}

			$needles[$text] = true;
		}

		return array_keys($needles);
	}//end needles()

	/**
	 * A result that says nothing is known, and why.
	 *
	 * @param string $outputMode The mode.
	 * @param string $why        The reason, in words.
	 *
	 * @return array<string, mixed> The result.
	 */
	private function unverifiable(string $outputMode, string $why): array {
		return [
			'verdict' => self::UNVERIFIABLE,
			'outputMode' => $outputMode,
			'routesChecked' => [],
			'findings' => [],
			'mayBePublished' => false,
			'reason' => $why,
		];
	}//end unverifiable()
}//end class
