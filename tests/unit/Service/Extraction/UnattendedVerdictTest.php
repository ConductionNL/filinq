<?php

/**
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Extraction;

use OCA\Filinq\Event\UnattendedOutcome;
use OCA\Filinq\Service\Extraction\ExtractionDuplicateLookup;
use OCA\Filinq\Service\Extraction\UnattendedVerdict;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * REQ-ERP-003: the unattended verdict and its reason codes.
 *
 * @spec openspec/changes/extraction-receipt-photo-and-invoice-agent/tasks.md#3.1
 */
class UnattendedVerdictTest extends TestCase {

	/**
	 * A clean supplier invoice read at 0.95 everywhere.
	 *
	 * @return array{fields: array<string, mixed>, confidence: array<string, float>}
	 */
	private static function clean(): array {
		return [
			'fields' => [
				'supplierName' => 'Hostbaar B.V.',
				'supplierIban' => 'NL91ABNA0417164300',
				'supplierKvk' => null,
				'supplierVatId' => null,
				'invoiceNumber' => '2026-0412',
				'issueDate' => '2026-04-12',
				'totalIncl' => 121.0,
			],
			'confidence' => [
				'supplierName' => 0.95,
				'supplierIban' => 0.95,
				'invoiceNumber' => 0.95,
				'issueDate' => 0.95,
				'totalIncl' => 0.95,
			],
		];
	}

	private static function decide(array $fields, array $confidence, bool $reconciled = true, array $modelFilled = [], bool $duplicate = false, float $threshold = 0.9): UnattendedOutcome {
		return UnattendedVerdict::decide(
			fields: $fields,
			confidence: $confidence,
			reconciled: $reconciled,
			modelFilled: $modelFilled,
			duplicate: $duplicate,
			threshold: $threshold,
		);
	}

	public function testACleanInvoiceFromAKnownSupplierPasses(): void {
		$clean = self::clean();

		$outcome = self::decide(fields: $clean['fields'], confidence: $clean['confidence']);

		$this->assertSame(['unattendedVerdict' => 'pass', 'verdictReasons' => [], 'verdictThreshold' => 0.9], $outcome->toStored());
	}

	/**
	 * @return array<string, array{string, mixed}>
	 */
	public static function missingFields(): array {
		return [
			'no supplier name' => ['supplierName', null],
			'no issue date' => ['issueDate', ''],
			'no total' => ['totalIncl', null],
			'no supplier identifier' => ['supplierIban', null],
		];
	}

	#[DataProvider('missingFields')]
	public function testAMissingFieldGoesToReview(string $field, mixed $value): void {
		$clean = self::clean();
		$clean['fields'][$field] = $value;

		$outcome = self::decide(fields: $clean['fields'], confidence: $clean['confidence']);

		$this->assertSame('review', $outcome->verdict);
		$this->assertSame(['missing-field'], $outcome->reasons);
	}

	public function testAnyIdentifierWillDo(): void {
		$clean = self::clean();
		$clean['fields']['supplierIban'] = null;
		$clean['fields']['supplierVatId'] = 'NL001234567B01';
		$clean['confidence']['supplierVatId'] = 0.92;

		$this->assertSame('pass', self::decide(fields: $clean['fields'], confidence: $clean['confidence'])->verdict);
	}

	public function testTheBestReadIdentifierIsTheOneJudged(): void {
		$clean = self::clean();
		$clean['fields']['supplierKvk'] = '12345678';
		$clean['confidence']['supplierKvk'] = 0.4;

		$this->assertSame('pass', self::decide(fields: $clean['fields'], confidence: $clean['confidence'])->verdict);
	}

	public function testAFieldBelowTheThresholdIsLowConfidenceOnce(): void {
		$clean = self::clean();
		$clean['confidence']['issueDate'] = 0.89;
		$clean['confidence']['totalIncl'] = 0.7;

		$outcome = self::decide(fields: $clean['fields'], confidence: $clean['confidence']);

		$this->assertSame(['low-confidence'], $outcome->reasons);
	}

	public function testExactlyTheThresholdPasses(): void {
		$clean = self::clean();

		$this->assertSame('pass', self::decide(fields: $clean['fields'], confidence: $clean['confidence'], threshold: 0.95)->verdict);
		$this->assertSame(['low-confidence'], self::decide(fields: $clean['fields'], confidence: $clean['confidence'], threshold: 0.96)->reasons);
	}

	public function testAPresentFieldIsStillJudgedWhenAnotherIsMissing(): void {
		$clean = self::clean();
		$clean['fields']['supplierIban'] = null;
		$clean['confidence']['totalIncl'] = 0.5;

		$this->assertSame(['missing-field', 'low-confidence'], self::decide(fields: $clean['fields'], confidence: $clean['confidence'])->reasons);
	}

	public function testUnreconciledTotalsGoToReview(): void {
		$clean = self::clean();

		$this->assertSame(['not-reconciled'], self::decide(fields: $clean['fields'], confidence: $clean['confidence'], reconciled: false)->reasons);
	}

	public function testAFieldFilledByTheModelAloneGoesToReview(): void {
		$clean = self::clean();

		$this->assertSame(['model-only'], self::decide(fields: $clean['fields'], confidence: $clean['confidence'], modelFilled: ['dueDate'])->reasons);
	}

	public function testTheSameInvoiceSentTwiceGoesToReview(): void {
		$clean = self::clean();

		$this->assertSame(['possible-duplicate'], self::decide(fields: $clean['fields'], confidence: $clean['confidence'], duplicate: true)->reasons);
	}

	public function testEveryFailedCheckAddsItsOwnReasonInOrder(): void {
		$outcome = self::decide(fields: ['supplierName' => null], confidence: [], reconciled: false, modelFilled: ['supplierName'], duplicate: true);

		$this->assertSame(['missing-field', 'not-reconciled', 'model-only', 'possible-duplicate'], $outcome->reasons);
	}

	/**
	 * @return array<string, array{string, float}>
	 */
	public static function thresholds(): array {
		return [
			'unset' => ['', 0.9],
			'set' => ['0.95', 0.95],
			'lowest' => ['0.6', 0.6],
			'highest' => ['1.0', 1.0],
			'below the floor' => ['0.5', 0.9],
			'above one' => ['1.2', 0.9],
			'not a number' => ['hoog', 0.9],
		];
	}

	#[DataProvider('thresholds')]
	public function testTheThresholdIsTheSettingWithinBoundsElseTheDefault(string $stored, float $expected): void {
		$verdict = new UnattendedVerdict($this->config(threshold: $stored), $this->createMock(ExtractionDuplicateLookup::class));

		$this->assertSame($expected, $verdict->threshold());
	}

	public function testJudgeUsesTheSettingAndTheDuplicateCheckAndKeepsItsThreshold(): void {
		$clean = self::clean();
		$duplicates = $this->createMock(ExtractionDuplicateLookup::class);
		$duplicates->expects($this->once())->method('hasEarlier')->with($clean['fields'])->willReturn(false);
		$config = $this->config(threshold: '0.9');
		$verdict = new UnattendedVerdict($config, $duplicates);

		$outcome = $verdict->judge(
			result: ['fields' => $clean['fields'], 'fieldConfidence' => $clean['confidence'], 'reconciled' => true],
			modelFilled: []
		);

		$this->assertSame('pass', $outcome->verdict);
		$this->assertSame(0.9, $outcome->threshold);

		// An admin raises the threshold afterwards: the stored verdict keeps what held.
		$stored = $outcome->toStored();
		$raised = new UnattendedVerdict($this->config(threshold: '0.99'), $this->createMock(ExtractionDuplicateLookup::class));
		$this->assertSame(0.99, $raised->threshold());
		$this->assertSame(['unattendedVerdict' => 'pass', 'verdictReasons' => [], 'verdictThreshold' => 0.9], $stored);
	}

	private function config(string $threshold): IAppConfig {
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($app === 'filinq' && $key === UnattendedVerdict::SETTING_KEY) ? $threshold : $default
		);

		return $config;
	}
}
