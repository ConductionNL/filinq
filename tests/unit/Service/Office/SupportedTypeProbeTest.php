<?php

/**
 * The type-support probe: measured facts, and a default of UNSUPPORTED.
 *
 * The property under test is not "does it find types" — it is "does it refuse
 * to claim types it has not measured". A probe that fails open would report a
 * suite as editing everything the moment it became unreachable, which is the
 * failure this class exists to prevent.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://conduction.nl
 *
 * @spec openspec/changes/multi-format-editing-tools/tasks.md#0-phase-0--probe-what-the-installed-suite-actually-edits-hard-gate
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Office;

use OCA\Filinq\Service\Office\SupportedTypeProbe;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SupportedTypeProbe.
 */
class SupportedTypeProbeTest extends TestCase {

	private SupportedTypeProbe $probe;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		$this->probe = new SupportedTypeProbe();
	}//end setUp()

	/**
	 * A discovery document declaring an edit action makes that type editable.
	 *
	 * @return void
	 */
	public function testAnAdvertisedEditActionIsReported(): void {
		$xml = '<wopi-discovery><action name="edit" ext="xlsx" urlsrc="x"/></wopi-discovery>';

		$d = $this->probe->declare($xml, 'collabora', '2026-08-18T00:00:00+00:00', 'http://suite/');

		$this->assertTrue($d['types']['xlsx']['edit']);
		$this->assertSame('suite advertises editing this type', $d['types']['xlsx']['reason']);
	}//end testAnAdvertisedEditActionIsReported()

	/**
	 * 🔴 The control: a type the suite does NOT advertise must come back
	 * unsupported. Without this, every assertion above passes on a probe that
	 * simply says yes to everything.
	 *
	 * @return void
	 */
	public function testAnUnadvertisedTypeIsUnsupported(): void {
		$xml = '<wopi-discovery><action name="edit" ext="xlsx" urlsrc="x"/></wopi-discovery>';

		$d = $this->probe->declare($xml, 'collabora', '2026-08-18T00:00:00+00:00', 'http://suite/');

		$this->assertFalse($d['types']['odg']['edit']);
		$this->assertSame('suite does not advertise editing this type', $d['types']['odg']['reason']);
	}//end testAnUnadvertisedTypeIsUnsupported()

	/**
	 * 🔴 A macro-bearing format stays refused even when the suite offers it.
	 * The policy sits in FRONT of the probe so a capable suite cannot talk the
	 * system into a code-execution vector in document clothing.
	 *
	 * @return void
	 */
	public function testAMacroFormatIsRefusedEvenWhenAdvertised(): void {
		$xml = '<wopi-discovery>'
			. '<action name="edit" ext="xlsm" urlsrc="x"/>'
			. '<action name="edit" ext="docm" urlsrc="x"/>'
			. '</wopi-discovery>';

		$d = $this->probe->declare($xml, 'collabora', '2026-08-18T00:00:00+00:00', 'http://suite/');

		// ⚠️ This used to assert the key was ABSENT, and absence was the weaker
		// property: a type nobody had ever considered is absent in exactly the
		// same way as one deliberately refused. It also let the refusal go dead
		// without anything noticing — the probe iterated only the candidate
		// list, which contains none of the refused types, so the guard could
		// never fire and this test passed on the strength of an omission.
		//
		// Asserted now: the type IS declared, it is NOT editable, and it says
		// so because it was refused rather than because nobody offered it.
		foreach (['xlsm', 'docm', 'pptm', 'odb'] as $refused) {
			$this->assertArrayHasKey($refused, $d['types'], "$refused must be declared, not silently omitted");
			$this->assertFalse($d['types'][$refused]['edit'], "$refused must not be editable");
			$this->assertFalse($d['types'][$refused]['view'], "$refused must not be viewable");
			$this->assertStringStartsWith(
				'refused by policy',
				$d['types'][$refused]['reason'],
				"$refused must say it was refused, not that the suite failed to advertise it"
			);
		}

		// And the ground, not just the verdict — the two refusals here rest on
		// different arguments and an operator asking for one to be lifted needs
		// to know which.
		$this->assertStringContainsString('macro-bearing', $d['types']['xlsm']['reason']);
		$this->assertStringContainsString('database', $d['types']['odb']['reason']);

		$this->assertSame([], $d['advertisedButUnhandled'], 'a refused type is refused, not merely unhandled');
	}//end testAMacroFormatIsRefusedEvenWhenAdvertised()

	/**
	 * 🔴 An unreachable suite reports every type UNSUPPORTED, never unknown.
	 * "We could not ask" must not read as "probably yes".
	 *
	 * @return void
	 */
	public function testAnUnreachableSuiteSupportsNothing(): void {
		$d = $this->probe->unavailable('connection refused', '2026-08-18T00:00:00+00:00');

		foreach (SupportedTypeProbe::CANDIDATE_TYPES as $type) {
			$this->assertFalse($d['types'][$type]['edit'], $type . ' was reported editable by a probe that never ran');
		}

		$this->assertStringContainsString('not probed', $d['types']['docx']['reason']);
	}//end testAnUnreachableSuiteSupportsNothing()

	/**
	 * 🔴 ADR-087 §4: a type editable on ONE suite must resolve absent — and
	 * visibly — on a suite that lacks it. Measured on this instance, Draw is
	 * exactly that case: Collabora edits `.odg` and the ONLYOFFICE lineage does
	 * not.
	 *
	 * "Visibly" is the load-bearing word. An absent capability that reports
	 * nothing is discovered by a tenant at the point of failure, which is what
	 * the ADR exists to prevent.
	 *
	 * @return void
	 */
	public function testASuiteSpecificTypeResolvesAbsentAndVisibly(): void {
		$collabora = '<wopi-discovery>'
			. '<action name="edit" ext="odt" urlsrc="x"/>'
			. '<action name="edit" ext="odg" urlsrc="x"/>'
			. '</wopi-discovery>';
		$onlyoffice = '<wopi-discovery><action name="edit" ext="odt" urlsrc="x"/></wopi-discovery>';

		$withDraw = $this->probe->declare($collabora, 'collabora', '2026-08-18T00:00:00+00:00', 'http://a/');
		$withoutDraw = $this->probe->declare($onlyoffice, 'onlyoffice', '2026-08-18T00:00:00+00:00', 'http://b/');

		$this->assertTrue($withDraw['types']['odg']['edit']);
		$this->assertFalse($withoutDraw['types']['odg']['edit']);

		// Absent WITH A REASON, and the declaration names the suite it was
		// measured against, so the difference is attributable rather than
		// mysterious.
		$this->assertSame(
			'suite does not advertise editing this type',
			$withoutDraw['types']['odg']['reason']
		);
		$this->assertSame('onlyoffice', $withoutDraw['suite']);
		$this->assertNotSame($withDraw['suite'], $withoutDraw['suite']);
	}//end testASuiteSpecificTypeResolvesAbsentAndVisibly()

	/**
	 * ⚠️ WOPI does not fix attribute order. Reading only `name`-then-`ext`
	 * would report a suite that writes them the other way round as editing
	 * NOTHING — indistinguishable from a suite that genuinely edits nothing.
	 *
	 * @return void
	 */
	public function testAttributeOrderDoesNotChangeTheAnswer(): void {
		$xml = '<wopi-discovery><action ext="odp" name="edit" urlsrc="x"/></wopi-discovery>';

		$d = $this->probe->declare($xml, 'collabora', '2026-08-18T00:00:00+00:00', 'http://suite/');

		$this->assertTrue($d['types']['odp']['edit']);
	}//end testAttributeOrderDoesNotChangeTheAnswer()

	/**
	 * A view action is not an edit action. Reporting `view` as editable would
	 * offer a write capability over a read-only surface.
	 *
	 * @return void
	 */
	public function testViewOnlyIsNotEditable(): void {
		$xml = '<wopi-discovery><action name="view" ext="pdf" urlsrc="x"/></wopi-discovery>';

		$d = $this->probe->declare($xml, 'collabora', '2026-08-18T00:00:00+00:00', 'http://suite/');

		$this->assertFalse($d['types']['pdf']['edit']);
		$this->assertTrue($d['types']['pdf']['view']);
	}//end testViewOnlyIsNotEditable()

	/**
	 * Types the suite edits that this app has no opinion about are surfaced,
	 * not enabled — a visible gap rather than a silent one.
	 *
	 * @return void
	 */
	public function testUnhandledAdvertisedTypesAreSurfaced(): void {
		$xml = '<wopi-discovery><action name="edit" ext="rtf" urlsrc="x"/></wopi-discovery>';

		$d = $this->probe->declare($xml, 'collabora', '2026-08-18T00:00:00+00:00', 'http://suite/');

		$this->assertContains('rtf', $d['advertisedButUnhandled']);
		$this->assertArrayNotHasKey('rtf', $d['types']);
	}//end testUnhandledAdvertisedTypesAreSurfaced()
}//end class
