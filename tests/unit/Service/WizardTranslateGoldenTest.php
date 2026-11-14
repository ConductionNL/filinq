<?php

/**
 * Golden test for WizardAnswers::translate: answers into dataRefs, adHocData and the wizard context.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-10-01-guided-document-wizard/tasks.md#2-3
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Wizard\WizardAnswers;
use OCA\Filinq\Service\Wizard\WizardConditions;
use PHPUnit\Framework\TestCase;

/**
 * Every wizard and answer set through translate(), compared with JSON
 * recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Wizard\WizardAnswers
 * @uses   \OCA\Filinq\Service\Wizard\WizardConditions
 */
class WizardTranslateGoldenTest extends TestCase {

	/**
	 * The wizard and the answers.
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>}>
	 */
	public static function scenarios(): array {
		$questions = [
			['key' => 'zaak', 'type' => 'registerObject', 'register' => 'zaken', 'schema' => 'zaak'],
			['key' => 'soort', 'type' => 'choice', 'mapsTo' => 'brief.soort'],
			['key' => 'reden', 'type' => 'text', 'mapsTo' => ' brief.reden.tekst ', 'condition' => ['questionKey' => 'soort', 'operator' => 'equals', 'value' => 'weigering']],
			['key' => 'notitie', 'type' => 'text'],
			['key' => 'datum', 'type' => 'date', 'mapsTo' => 'brief.datum'],
			'geen vraag',
			['key' => 'klant', 'type' => 'registerObject'],
		];
		$wizard = ['uuid' => 'w-1', 'version' => 3, 'questions' => $questions];
		return [
			'everything answered' => [$wizard, ['zaak' => 'z-1', 'soort' => 'weigering', 'reden' => 'Te laat', 'notitie' => 'x', 'datum' => '2026-10-31', 'klant' => 7]],
			'a hidden question' => [$wizard, ['zaak' => 'z-1', 'soort' => 'toekenning', 'reden' => 'Te laat']],
			'blank answers' => [$wizard, ['zaak' => '', 'soort' => '  ', 'datum' => [], 'notitie' => null]],
			'an answer for no question' => [$wizard, ['onbekend' => 'x', 'datum' => '2026-01-01']],
			'no answers' => [$wizard, []],
			'a duplicate key' => [['questions' => [['key' => 'a', 'mapsTo' => 'x.a'], ['key' => 'a', 'mapsTo' => 'y.a'], ['key' => 'a', 'type' => 'registerObject', 'register' => 'r']]], ['a' => 'v']],
			'no questions' => [['uuid' => 'w-2'], ['a' => 'v']],
			'questions not a list' => [['questions' => 'x'], ['a' => 'v']],
			'a list answer' => [['questions' => [['key' => 'tags', 'mapsTo' => 'tags']]], ['tags' => ['a', 'b']]],
		];
	}//end scenarios()

	/**
	 * The translation matches the recorded one.
	 *
	 * @param array<string, mixed> $wizard The wizard.
	 * @param array<string, mixed> $answers The answers.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTranslate(array $wizard, array $answers): void {
		$trace = (new WizardAnswers(new WizardConditions()))->translate($wizard, $answers);

		$path = __DIR__ . '/golden/wizard-translate.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testTranslate()
}//end class
