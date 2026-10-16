<?php

/**
 * Document type classifier for inbound documents
 *
 * Suggests one Dutch intake type for a document from its text: brief,
 * besluit, factuur, rapport, contract or formulier, with `overig` as the
 * honest fallback when nothing scores above the threshold. The vocabularies
 * and the scoring live here and nowhere else (the REQ-META-11 boundary
 * applied to a sibling classifier); LanguageClassifier keeps its own
 * language and topic vocabularies untouched, because a topic and an intake
 * type are different axes: a besluit can be legal and financial at once.
 *
 * Stateless: no settings, no storage, no learning. A suggestion it makes
 * is only ever a suggestion; a person confirms or corrects it.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

/**
 * Rule and keyword classification into Dutch intake types.
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-1
 */
class DocumentTypeClassifier {

	/**
	 * The type a document gets when no vocabulary scores above the threshold.
	 *
	 * @var string
	 */
	public const FALLBACK = 'overig';

	/**
	 * The confidence a suggestion needs to name a type other than the fallback.
	 *
	 * @var float
	 */
	public const MIN_CONFIDENCE = 0.4;

	/**
	 * The score at which a type counts as fully evidenced.
	 *
	 * @var float
	 */
	private const SATURATION = 5.0;

	/**
	 * Words and phrases per intake type. Each phrase counts once, however
	 * often it occurs, so a long letter that repeats "factuur" is not an
	 * invoice for that alone.
	 *
	 * @var array<string, string[]>
	 */
	public const TYPE_KEYWORDS = [
		'brief' => ['geachte', 'met vriendelijke groet', 'hoogachtend', 'betreft', 'uw kenmerk', 'ons kenmerk', 'naar aanleiding van uw'],
		'besluit' => [
			'besluit',
			'besluiten',
			'overwegende dat',
			'gelet op',
			'burgemeester en wethouders',
			'bezwaar maken',
			'bezwaarschrift',
			'beschikking',
			'het college',
		],
		'factuur' => [
			'factuur',
			'factuurnummer',
			'factuurdatum',
			'te betalen',
			'btw',
			'excl btw',
			'incl btw',
			'vervaldatum',
			'debiteurnummer',
			'betalingstermijn',
		],
		'rapport' => ['rapport', 'rapportage', 'samenvatting', 'inleiding', 'conclusie', 'conclusies', 'aanbevelingen', 'onderzoek', 'bijlagen'],
		'contract' => ['overeenkomst', 'partijen', 'komen overeen', 'artikel 1', 'looptijd', 'opzegging', 'ondertekening', 'hierna te noemen'],
		'formulier' => ['formulier', 'aanvraagformulier', 'in te vullen', 'invullen', 'handtekening aanvrager', 'aankruisen', 'naam aanvrager'],
	];

	/**
	 * Structural cues per type: a pattern on the raw text and its weight.
	 * A cue is stronger evidence than a word because it is the shape of the
	 * document, not its subject: an IBAN with an invoice number is an
	 * invoice, a letter that mentions an invoice is not.
	 *
	 * @var array<string, array<string, float>>
	 */
	private const STRUCTURAL_CUES = [
		'factuur' => [
			'/\bNL\d{2}\s?[A-Z]{4}\s?\d{4}\s?\d{4}\s?\d{2}\b/' => 2.0,
			'/factuurnummer\s*:?\s*\S+/iu' => 2.0,
		],
		'besluit' => ['/^\s*besluit\b/imu' => 2.0],
		'brief' => ['/^\s*geachte\b/imu' => 1.0],
		'contract' => ['/^\s*artikel\s+\d+/imu' => 1.0],
		'formulier' => ['/\[\s?\]|☐/u' => 1.0],
	];

	/**
	 * Classify a document's text.
	 *
	 * @param string $text The document text.
	 *
	 * @return array{type: string, confidence: float, scores: array<string, float>}
	 *     The suggested type, its confidence (0 to 1) and the raw score per type.
	 *
	 * @spec openspec/changes/inbound-auto-classification/tasks.md#2-1
	 */
	public function classify(string $text): array {
		$scores = $this->scores(text: $text);
		arsort($scores);
		$ranked = array_values($scores);
		$best = $ranked[0];
		$second = $ranked[1];
		$type = (string) array_key_first($scores);

		$confidence = 0.0;
		if ($best > 0.0) {
			// Evidence (how much) times dominance (how clearly ahead of the next type).
			$confidence = round(min(1.0, $best / self::SATURATION) * ($best / ($best + $second)), 2);
		}

		if ($confidence < self::MIN_CONFIDENCE) {
			$type = self::FALLBACK;
		}

		return ['type' => $type, 'confidence' => $confidence, 'scores' => $scores];

	}//end classify()

	/**
	 * The raw score per type.
	 *
	 * @param string $text The document text.
	 *
	 * @return array<string, float> Score per type, in TYPE_KEYWORDS order.
	 */
	private function scores(string $text): array {
		$words = ' ' . $this->normalise(text: $text) . ' ';
		$scores = [];
		foreach (self::TYPE_KEYWORDS as $type => $phrases) {
			$score = 0.0;
			foreach ($phrases as $phrase) {
				if (str_contains($words, ' ' . $this->normalise(text: $phrase) . ' ') === true) {
					$score += 1.0;
				}
			}

			foreach (self::STRUCTURAL_CUES[$type] ?? [] as $pattern => $weight) {
				if (preg_match($pattern, $text) === 1) {
					$score += $weight;
				}
			}

			$scores[$type] = $score;
		}

		return $scores;

	}//end scores()

	/**
	 * Lower case, every run of non-letters and non-digits one space.
	 *
	 * @param string $text The text.
	 *
	 * @return string The normalised text.
	 */
	private function normalise(string $text): string {
		return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($text)));

	}//end normalise()
}//end class
