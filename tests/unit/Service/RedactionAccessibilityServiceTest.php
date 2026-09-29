<?php

/**
 * What a redaction did to the tag structure, and what publication does about it.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/accessible-redaction-output/tasks.md#task-4.1
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\VeraPdfException;
use OCA\Filinq\Service\RedactionAccessibilityService;
use OCA\Filinq\Service\VeraPdf\VeraPdfService;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;

/**
 * The mapping, the veraPDF check and the gate.
 */
class RedactionAccessibilityServiceTest extends TestCase {

	/**
	 * The service with the given settings and validator.
	 *
	 * @param array<string, string> $config  App config values by key.
	 * @param VeraPdfService|null   $veraPdf The validator.
	 *
	 * @return RedactionAccessibilityService
	 */
	private function service(array $config = [], ?VeraPdfService $veraPdf = null): RedactionAccessibilityService {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueString')->willReturnCallback(
			static fn (string $app, string $key, string $default = ''): string => ($config[$key] ?? $default)
		);

		return new RedactionAccessibilityService($appConfig, $veraPdf);
	}

	/**
	 * A validator that answers the given verdict, or throws.
	 *
	 * @param bool|null $compliant The verdict; null throws a timeout.
	 *
	 * @return VeraPdfService
	 */
	private function veraPdf(?bool $compliant): VeraPdfService {
		$veraPdf = $this->createMock(VeraPdfService::class);
		$veraPdf->method('isAvailable')->willReturn(true);
		if ($compliant === null) {
			$veraPdf->method('validateBytes')->willThrowException(new VeraPdfException(reason: VeraPdfException::REASON_TIMEOUT, message: 'slow'));
			return $veraPdf;
		}

		$veraPdf->expects($this->once())->method('validateBytes')->with($this->anything(), 'ua1')->willReturn(['compliant' => $compliant]);
		return $veraPdf;
	}

	public function testPreservationIsRequestedByDefaultAndCanBeSwitchedOff(): void {
		$this->assertTrue($this->service()->preserveRequested());
		$this->assertFalse($this->service([RedactionAccessibilityService::CFG_PRESERVE_DEFAULT => 'false'])->preserveRequested());
	}

	public function testTheMappingMatrix(): void {
		$service = $this->service();

		$preserved = $service->assess(['requested' => true, 'preserved' => true, 'tagCountBefore' => 96, 'tagCountAfter' => 96, 'lossReasons' => []]);
		$this->assertSame(['state' => 'preserved', 'requested' => true, 'preserved' => true, 'tagCountBefore' => 96, 'tagCountAfter' => 96, 'lossReasons' => []], $preserved);

		$degraded = $service->assess(['requested' => true, 'preserved' => false, 'tagCountBefore' => 96, 'tagCountAfter' => 0, 'lossReasons' => ['engine-cannot-reauthor-structtree']]);
		$this->assertSame('degraded', $degraded['state']);
		$this->assertSame(['engine-cannot-reauthor-structtree'], $degraded['lossReasons']);

		$untagged = $service->assess(['requested' => true, 'preserved' => false, 'tagCountBefore' => 0, 'tagCountAfter' => 0, 'lossReasons' => ['input-not-tagged']]);
		$this->assertSame('not-applicable', $untagged['state']);
	}

	public function testAbsentBlockIsUnknown(): void {
		$this->assertSame(['state' => 'unknown'], $this->service()->assess(null));
		$this->assertSame(['state' => 'unknown'], $this->service()->assess(['requested' => true]), 'No preserved flag is no claim.');
	}

	public function testVeraPdfConfirmsAPreservedClaim(): void {
		$outcome = $this->service([], $this->veraPdf(true))->assess(['requested' => true, 'preserved' => true, 'tagCountBefore' => 5, 'tagCountAfter' => 5], '%PDF <pdfuaid:part>1</pdfuaid:part>');

		$this->assertSame('preserved', $outcome['state']);
		$this->assertTrue($outcome['veraPdfVerified']);
	}

	public function testVeraPdfContradictionDowngrades(): void {
		$outcome = $this->service([], $this->veraPdf(false))->assess(['requested' => true, 'preserved' => true, 'tagCountBefore' => 5, 'tagCountAfter' => 5], '%PDF <pdfuaid:part>1</pdfuaid:part>');

		$this->assertSame('degraded', $outcome['state']);
		$this->assertFalse($outcome['veraPdfVerified']);
		$this->assertSame([RedactionAccessibilityService::REASON_VERAPDF], $outcome['lossReasons']);
	}

	public function testWithoutVeraPdfOrAPdfUaClaimTheEngineOutcomeStands(): void {
		$report = ['requested' => true, 'preserved' => true, 'tagCountBefore' => 5, 'tagCountAfter' => 5];

		$this->assertArrayNotHasKey('veraPdfVerified', $this->service()->assess($report, '%PDF <pdfuaid:part>1</pdfuaid:part>'));
		$this->assertArrayNotHasKey('veraPdfVerified', $this->service([], $this->veraPdf(null))->assess($report, '%PDF <pdfuaid:part>1</pdfuaid:part>'), 'A veraPDF that cannot run proves nothing.');

		$notClaimed = $this->createMock(VeraPdfService::class);
		$notClaimed->method('isAvailable')->willReturn(true);
		$notClaimed->expects($this->never())->method('validateBytes');
		$this->assertSame('preserved', $this->service([], $notClaimed)->assess($report, '%PDF tagged, no PDF/UA claim')['state']);
	}

	public function testTheGateWarnsByDefault(): void {
		$gate = $this->service()->gate('degraded');

		$this->assertTrue($gate['clear']);
		$this->assertStringContainsString('lost its accessibility', (string) $gate['warning']);
		$this->assertSame(['clear' => true, 'warning' => null], $this->service()->gate('preserved'));
		$this->assertSame(['clear' => true, 'warning' => null], $this->service()->gate('not-applicable'));
	}

	public function testBlockModeStopsUntilAReasonIsRecorded(): void {
		$service = $this->service([RedactionAccessibilityService::CFG_GATE => 'block']);

		$this->assertFalse($service->gate('degraded')['clear']);
		$this->assertFalse($service->gate('unknown')['clear'], 'Unknown counts as degraded.');
		$this->assertFalse($service->gate(null, '   ')['clear']);
		$this->assertTrue($service->gate('degraded', 'Het origineel was al ontoegankelijk; toegankelijke versie volgt.')['clear']);
	}

	public function testOffRecordsOnly(): void {
		$this->assertSame(['clear' => true, 'warning' => null], $this->service([RedactionAccessibilityService::CFG_GATE => 'off'])->gate('degraded'));
	}
}//end class
