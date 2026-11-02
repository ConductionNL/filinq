<?php

/**
 * Trace tests for the download agreement check: the lookups and the verdict.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Redaction
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Redaction;

use OCA\Filinq\Exception\AgreementStoreUnreadableException;
use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Redaction\DownloadAgreementRepository;
use OCP\IL10N;
use PHPUnit\Framework\TestCase;

/**
 * Every path through check(), compared with JSON recorded from the
 * implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\Redaction\DownloadAgreementGate
 * @uses   \OCA\Filinq\Exception\AgreementStoreUnreadableException
 */
class DownloadAgreementCheckTraceTest extends TestCase {

	/**
	 * What the repository was asked, in order.
	 *
	 * @var array<int, array<int, mixed>>
	 */
	private array $calls = [];

	/**
	 * The scenarios: the stored agreement, the stored acceptance, and whether a translator is present.
	 *
	 * @return array<string, array{0: array<string, mixed>|string|null, 1: array<string, mixed>|string|null, 2: bool}>
	 */
	public static function scenarios(): array {
		$terms = ['version' => ' 3 ', 'text' => 'Niet verder verspreiden.', 'locale' => 'nl'];
		return [
			'the terms cannot be read' => ['unreadable', null, false],
			'no terms' => [null, null, false],
			'terms, no acceptance' => [$terms, null, false],
			'terms, no acceptance, translated' => [$terms, null, true],
			'the acceptances cannot be read' => [$terms, 'unreadable', false],
			'an acceptance without a version' => [$terms, ['acceptedVersion' => '  '], false],
			'an acceptance of an older version' => [$terms, ['acceptedVersion' => '2'], false],
			'an older version, translated' => [$terms, ['acceptedVersion' => '2'], true],
			'an acceptance of this version' => [$terms, ['acceptedVersion' => '3 '], false],
			'terms without a version or text' => [['locale' => 'en'], ['acceptedVersion' => '1'], false],
		];
	}//end scenarios()

	/**
	 * The verdict and the lookups match the recorded ones.
	 *
	 * @param array<string, mixed>|string|null $agreement  The stored agreement, or 'unreadable'.
	 * @param array<string, mixed>|string|null $acceptance The stored acceptance, or 'unreadable'.
	 * @param bool                             $translated Whether a translator is present.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testTrace(array|string|null $agreement, array|string|null $acceptance, bool $translated): void {
		$repository = $this->createMock(DownloadAgreementRepository::class);
		$repository->method('forDocument')->willReturnCallback(
			function (string $document) use ($agreement): ?array {
				$this->calls[] = ['forDocument', $document];
				if ($agreement === 'unreadable') {
					throw new AgreementStoreUnreadableException('terms store offline');
				}

				return $agreement;
			}
		);
		$repository->method('acceptanceOf')->willReturnCallback(
			function (string $document, string $person) use ($acceptance): ?array {
				$this->calls[] = ['acceptanceOf', $document, $person];
				if ($acceptance === 'unreadable') {
					throw new AgreementStoreUnreadableException('acceptance store offline');
				}

				return $acceptance;
			}
		);

		$l10n = null;
		if ($translated === true) {
			$l10n = $this->createMock(IL10N::class);
			$l10n->method('t')->willReturnCallback(static fn (string $text): string => '[nl] ' . $text);
		}

		$verdict = (new DownloadAgreementGate(agreements: $repository, l10n: $l10n))->check(document: 'doc-1', person: 'burger-7');
		$trace = ['verdict' => $verdict, 'calls' => $this->calls];

		$path = __DIR__ . '/golden/download-agreement-check-trace.json';
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
	}//end testTrace()
}//end class
