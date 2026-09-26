<?php

/**
 * The plain-language counterpart, and the three ways it could quietly go wrong.
 *
 * 🔴 A PLAIN LETTER WITH A HOLE WHERE THE TERM SHOULD BE IS WORSE THAN NO PLAIN
 * LETTER. The reader believes they have been told when they may object, and they
 * have not. So an unresolved required statement refuses the whole generation,
 * before either rendition is filed, and the refusal names the statement.
 *
 * 🔴 A TEMPLATE THAT DECLARES NO COUNTERPART GETS NO PLAIN LETTER, AND NOTHING
 * IS INVENTED IN ITS PLACE. Generated plain text reads fluently whether or not
 * the organisation ever approved it, which is exactly why nobody would catch it.
 *
 * 🔴 AND A MACHINE DRAFT STAYS HERE UNTIL A NAMED PERSON ACCEPTS IT, WITH THE
 * MOMENT. "Accepted: true" can be written by the same machine that drafted the
 * text.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @link https://www.filinq.app
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Exception\PlainRenditionRefusedException;
use OCA\Filinq\Service\PlainLanguageRenditionService;
use OCA\Filinq\Service\PlainRenditionAcceptanceGate;
use OCA\Filinq\Service\TemplateService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * `PlainLanguageRenditionService`.
 *
 * @covers \OCA\Filinq\Service\PlainLanguageRenditionService
 *
 * @uses \OCA\Filinq\Exception\PlainRenditionRefusedException
 * @uses \OCA\Filinq\Service\PlainRenditionAcceptanceGate
 */
class PlainLanguageRenditionServiceTest extends TestCase {

	/**
	 * A service whose template store answers as told.
	 *
	 * The template store double uses `onlyMethods` so it cannot invent a
	 * `getTemplate()` the real TemplateService lacks: a plan built on a method
	 * nothing implements would pass here and 500 in production.
	 *
	 * @param array<string, mixed>|RuntimeException $counterpart What getTemplate() answers, or throws.
	 *
	 * @return PlainLanguageRenditionService The service.
	 */
	private function service(array|RuntimeException $counterpart = ['content' => 'Wij hebben uw aanvraag afgewezen.']): PlainLanguageRenditionService {
		$templates = $this->getMockBuilder(TemplateService::class)
			->disableOriginalConstructor()
			->onlyMethods(['getTemplate'])
			->getMock();

		if ($counterpart instanceof RuntimeException) {
			$templates->method('getTemplate')->willThrowException($counterpart);
		} else {
			$templates->method('getTemplate')->willReturn($counterpart);
		}

		return new PlainLanguageRenditionService(
			$templates,
			new PlainRenditionAcceptanceGate(),
			new NullLogger()
		);
	}//end service()

	/**
	 * A template declaring a counterpart.
	 *
	 * @param array<int, string> $statements The required statements.
	 * @param string             $source     Where the plain text comes from.
	 *
	 * @return array<string, mixed> The template.
	 */
	private function declaring(array $statements = ['besluit', 'bezwaartermijn'], string $source = 'template'): array {
		return [
			'name' => 'Besluit',
			'content' => 'De formele tekst.',
			'plainLanguage' => [
				'templateId' => 'plain-besluit',
				'requiredStatements' => $statements,
				'source' => $source,
			],
		];
	}//end declaring()

	/**
	 * A template with no counterpart plans nothing.
	 *
	 * @return void
	 */
	public function testNoCounterpartPlansNothing(): void {
		$plan = $this->service()->plan(
			template: ['name' => 'Brief', 'content' => 'De tekst.'],
			data: []
		);

		self::assertNull($plan);
	}//end testNoCounterpartPlansNothing()

	/**
	 * A declaration naming no template is not a counterpart.
	 *
	 * @return void
	 */
	public function testADeclarationWithoutATemplateIsNotACounterpart(): void {
		$plan = $this->service()->plan(
			template: ['content' => 'x', 'plainLanguage' => ['requiredStatements' => ['besluit']]],
			data: []
		);

		self::assertNull($plan);
	}//end testADeclarationWithoutATemplateIsNotACounterpart()

	/**
	 * Both renditions come from one plan, and the plain one carries the statements.
	 *
	 * @return void
	 */
	public function testPlansTheCounterpartFromTheSameData(): void {
		$plan = $this->service()->plan(
			template: $this->declaring(),
			data: ['besluit' => 'afgewezen', 'bezwaartermijn' => '6 weken']
		);

		self::assertNotNull($plan);
		self::assertSame('plain-besluit', $plan['templateId']);
		self::assertSame(['besluit', 'bezwaartermijn'], $plan['statements']);
		self::assertSame('Wij hebben uw aanvraag afgewezen.', $plan['content']);
	}//end testPlansTheCounterpartFromTheSameData()

	/**
	 * An unresolved required statement refuses the generation, naming it.
	 *
	 * @return void
	 */
	public function testAnUnresolvedStatementRefusesTheGeneration(): void {
		$service = $this->service();

		try {
			$service->plan(
				template: $this->declaring(),
				data: ['besluit' => 'afgewezen']
			);
			self::fail('the generation should have been refused');
		} catch (PlainRenditionRefusedException $e) {
			// The statement is named, not merely counted: a handler told "the
			// generation failed" goes looking through a template for a hole
			// they cannot see.
			self::assertSame(['bezwaartermijn'], $e->getUnresolved());
			self::assertStringContainsString('bezwaartermijn', $e->getMessage());
			self::assertStringContainsString('Neither version was filed', $e->getMessage());
		}
	}//end testAnUnresolvedStatementRefusesTheGeneration()

	/**
	 * A statement resolving to an empty string is unresolved; a zero is an answer.
	 *
	 * @return void
	 */
	public function testEmptyIsUnresolvedButZeroIsAnAnswer(): void {
		$service = $this->service();

		self::assertSame(
			['bezwaartermijn'],
			$service->unresolved(required: ['besluit', 'bezwaartermijn'], data: ['besluit' => 'afgewezen', 'bezwaartermijn' => '  '])
		);

		// A term of 0 days is a strange decision but it IS a decision, and
		// refusing it would make the letter impossible to send.
		self::assertSame(
			[],
			$service->unresolved(required: ['termijn'], data: ['termijn' => 0])
		);
	}//end testEmptyIsUnresolvedButZeroIsAnAnswer()

	/**
	 * A counterpart template that cannot be read refuses the generation.
	 *
	 * @return void
	 */
	public function testAnUnreadableCounterpartRefusesTheGeneration(): void {
		$this->expectException(PlainRenditionRefusedException::class);

		$this->service(new RuntimeException('no such template'))->plan(
			template: $this->declaring(statements: []),
			data: []
		);
	}//end testAnUnreadableCounterpartRefusesTheGeneration()

	/**
	 * A machine draft nobody accepted does not leave.
	 *
	 * @return void
	 */
	public function testAnUnacceptedMachineDraftIsHeld(): void {
		$this->expectException(PlainRenditionRefusedException::class);
		$this->expectExceptionMessage('still waiting');

		$this->service()->plan(
			template: $this->declaring(statements: [], source: 'machine'),
			data: []
		);
	}//end testAnUnacceptedMachineDraftIsHeld()

	/**
	 * An accepted machine draft carries who accepted it and when.
	 *
	 * @return void
	 */
	public function testAnAcceptedMachineDraftRecordsThePersonAndTheMoment(): void {
		$plan = $this->service()->plan(
			template: $this->declaring(statements: [], source: 'machine'),
			data: [],
			acceptance: ['acceptedBy' => 'anne', 'acceptedAt' => '2026-09-18T10:00:00+02:00']
		);

		self::assertSame('anne', $plan['acceptedBy']);
		self::assertSame('2026-09-18T10:00:00+02:00', $plan['acceptedAt']);
	}//end testAnAcceptedMachineDraftRecordsThePersonAndTheMoment()

	/**
	 * The plain rendition names the formal document it explains.
	 *
	 * @return void
	 */
	public function testTheRenditionNamesTheFormalDocument(): void {
		$service = $this->service();
		$plan = $service->plan(
			template: $this->declaring(statements: []),
			data: []
		);

		$fields = $service->recordFields(
			plan: $plan,
			formal: ['fileId' => 41, 'path' => '/admin/files/DocuDesk/besluit.pdf'],
			plainFile: ['fileId' => 42, 'path' => '/admin/files/DocuDesk/besluit-in-gewone-taal.pdf'],
			moment: '2026-09-18T11:00:00+02:00'
		);

		self::assertSame('/admin/files/DocuDesk/besluit.pdf', $fields['plainRenditionExplains']);
		self::assertSame(42, $fields['plainRenditionFileId']);
		self::assertSame('template', $fields['plainRenditionSource']);
	}//end testTheRenditionNamesTheFormalDocument()

	/**
	 * A plain rendition older than the formal document reads as stale.
	 *
	 * @return void
	 */
	public function testAPlainRenditionLeftBehindReadsAsStale(): void {
		$service = $this->service();

		self::assertTrue(
			$service->stale(
				['generatedAt' => '2026-09-18T12:00:00+02:00', 'plainRenditionGeneratedAt' => '2026-09-17T09:00:00+02:00']
			)
		);

		self::assertFalse(
			$service->stale(
				['generatedAt' => '2026-09-18T12:00:00+02:00', 'plainRenditionGeneratedAt' => '2026-09-18T12:00:00+02:00']
			)
		);

		// A record with no plain rendition is not stale, it simply has none.
		self::assertFalse($service->stale(['generatedAt' => '2026-09-18T12:00:00+02:00']));

		// An unreadable formal moment reads as stale: the cheap error is
		// regenerating something current, the expensive one is sending a plain
		// letter about a decision that has since been corrected.
		self::assertTrue($service->stale(['plainRenditionGeneratedAt' => '2026-09-18T12:00:00+02:00']));
	}//end testAPlainRenditionLeftBehindReadsAsStale()
}//end class
