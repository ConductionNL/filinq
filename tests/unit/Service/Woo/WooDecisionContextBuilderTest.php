<?php

/**
 * Unit tests for the woo-decision data contract.
 *
 * @category  Test
 * @package   OCA\Filinq\Tests\Unit\Service\Woo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/woo-request-workflow/specs/woo-request-workflow/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Woo;

use OCA\Filinq\Exception\WooDecisionContractException;
use OCA\Filinq\Service\DocumentGenerationRequestService;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentService;
use OCA\Filinq\Service\Grounds\RefusalGroundsResolver;
use OCA\Filinq\Service\Support\TemplateLookup;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\Filinq\Service\TemplateSlugResolver;
use OCA\Filinq\Service\Woo\WooDecisionContextBuilder;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * The woo-decision contract, checked before anything is rendered.
 *
 * The grounds come from a snapshot in dossiq's `WooRefusalGrounds` shape
 * (keys id, code, article, paragraph, letter, label, description, parent,
 * status, legalSource), read through the real resolver.
 *
 * @covers \OCA\Filinq\Service\Woo\WooDecisionContextBuilder
 * @covers \OCA\Filinq\Exception\WooDecisionContractException
 * @covers \OCA\Filinq\Service\DocumentGenerationRequestService
 * @uses   \OCA\Filinq\Service\Grounds\RefusalGroundsResolver
 * @uses   \OCA\Filinq\Service\Support\TemplateLookup
 * @uses   \OCA\Filinq\Service\DocumentGenerationRequestRules
 */
final class WooDecisionContextBuilderTest extends TestCase {

	private string $snapshot = '';

	protected function setUp(): void {
		$this->snapshot = (string)tempnam(sys_get_temp_dir(), 'grounds');
		$ground = static fn (string $code, string $article, string $paragraph, string $letter, string $label, string $status = 'active'): array => [
			'id' => 'woo-ground-' . str_replace('.', '-', $code), 'code' => $code, 'article' => $article, 'paragraph' => $paragraph, 'letter' => $letter,
			'label' => $label, 'description' => $label, 'parent' => $article, 'status' => $status, 'legalSource' => 'https://wetten.overheid.nl/',
		];
		file_put_contents($this->snapshot, json_encode([
			'version' => '1.0.0',
			'grounds' => [
				$ground('5.1.2.e', '5.1', '2', 'e', 'Eerbiediging van de persoonlijke levenssfeer'),
				$ground('5.2.1', '5.2', '1', '', 'Persoonlijke beleidsopvattingen'),
				$ground('10.2.e', '10.2', '', 'e', 'Oude Wob-grond', 'retired'),
			],
		]));
	}//end setUp()

	protected function tearDown(): void {
		@unlink($this->snapshot);
	}//end tearDown()

	/**
	 * The builder over the snapshot, dossiq absent.
	 *
	 * @return WooDecisionContextBuilder
	 */
	private function builder(): WooDecisionContextBuilder {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForAnyone')->willReturn(false);

		return new WooDecisionContextBuilder(
			new RefusalGroundsResolver($apps, $this->createMock(ContainerInterface::class), new NullLogger(), RefusalGroundsResolver::DOSSIQ_GROUNDS, $this->snapshot)
		);
	}//end builder()

	/**
	 * A decision of three documents: disclose, partly disclose, withhold.
	 *
	 * @return array<string, mixed>
	 */
	private static function decision(): array {
		return [
			'reference' => 'WOO-2026-021',
			'subject' => 'Kapvergunningen Kerkstraat',
			'receivedAt' => '2026-09-01',
			'decisionDate' => '2026-09-29',
			'decisionKind' => 'partially-disclose',
			'organisation' => 'Gemeente Zuiderdorp',
			'documents' => [
				['inventoryNumber' => '1', 'title' => 'Aanvraag', 'date' => '2026-01-10', 'assessment' => 'disclose', 'groundCodes' => []],
				['inventoryNumber' => '2', 'title' => 'Advies', 'date' => '2026-02-11', 'assessment' => 'partially-disclose', 'groundCodes' => ['5.1.2.e']],
				['inventoryNumber' => '3', 'title' => 'Intern memo', 'date' => '2026-03-12', 'assessment' => 'withhold', 'groundCodes' => ['5.1.2.e', '5.2.1']],
			],
		];
	}//end decision()

	/**
	 * The refusal's problems for a decision, or null when it is accepted.
	 *
	 * @param mixed $decision The decision.
	 * @param bool  $retired  Whether retired grounds are allowed.
	 *
	 * @return array<int, string>|null The problems.
	 */
	private function problems(mixed $decision, bool $retired = false): ?array {
		try {
			$this->builder()->build($decision, $retired);
			return null;
		} catch (WooDecisionContractException $refusal) {
			return $refusal->problems;
		}
	}//end problems()

	public function testAWithheldDocumentNeedsAGround(): void {
		$decision = self::decision();
		$decision['documents'][2]['groundCodes'] = [];

		self::assertSame(['Document 3 is assessed withhold but cites no ground.'], $this->problems($decision));
	}//end testAWithheldDocumentNeedsAGround()

	public function testAllProblemsAreReported(): void {
		$decision = self::decision();
		unset($decision['decisionDate']);
		$decision['documents'][0]['inventoryNumber'] = '4';
		$decision['documents'][1]['inventoryNumber'] = '4';

		self::assertSame(
			['decisionDate is missing or is not a date.', 'Inventory number 4 is used by more than one document.'],
			$this->problems($decision)
		);
	}//end testAllProblemsAreReported()

	public function testEveryKeyAndShapeIsChecked(): void {
		self::assertSame(['data.wooDecision is missing.'], $this->problems(null));

		$problems = $this->problems([
			'reference' => ' ', 'receivedAt' => '2026-02-30', 'decisionKind' => 'maybe', 'organisation' => 'Z',
			'documents' => ['junk', ['inventoryNumber' => '', 'assessment' => 'open']],
		]);

		self::assertSame(
			[
				'reference is missing.',
				'subject is missing.',
				'receivedAt is missing or is not a date.',
				'decisionDate is missing or is not a date.',
				'decisionKind must be one of disclose, partially-disclose, withhold, not-held.',
				'Document 1 is not an object.',
				'Document 2 has no inventory number.',
				'Document 2 has no title.',
				'Document 2 has assessment "open"; it must be one of disclose, partially-disclose, withhold.',
			],
			$problems
		);
		self::assertSame(['documents is missing or is not a list.'], $this->problems(array_merge(self::decision(), ['documents' => ['a' => []]])));
	}//end testEveryKeyAndShapeIsChecked()

	public function testGroundsAreResolvedToLabels(): void {
		$built = $this->builder()->build(self::decision());

		self::assertSame([], $built['documents'][0]['grounds']);
		self::assertSame(
			[['code' => '5.1.2.e', 'article' => '5.1', 'label' => 'Eerbiediging van de persoonlijke levenssfeer']],
			$built['documents'][1]['grounds']
		);
		self::assertSame(['5.1.2.e', '5.2.1'], array_column($built['documents'][2]['grounds'], 'code'));
		self::assertSame('Persoonlijke beleidsopvattingen', $built['documents'][2]['grounds'][1]['label']);
		self::assertSame('WOO-2026-021', $built['reference']);
	}//end testGroundsAreResolvedToLabels()

	public function testAnUnknownGroundIsRefused(): void {
		$decision = self::decision();
		$decision['documents'][1]['groundCodes'] = ['5.9.9'];

		self::assertSame(['Document 2 cites ground 5.9.9, which is not in the list of refusal grounds.'], $this->problems($decision));
	}//end testAnUnknownGroundIsRefused()

	public function testARetiredGroundNeedsTheFlag(): void {
		$decision = self::decision();
		$decision['documents'][2]['groundCodes'] = ['10.2.e'];

		self::assertSame(
			['Document 3 cites ground 10.2.e, which is retired; set allowRetiredGrounds to redraft an old decision.'],
			$this->problems($decision)
		);
		self::assertNull($this->problems($decision, retired: true));
	}//end testARetiredGroundNeedsTheFlag()

	/**
	 * The generation service over a template; returns the documents double.
	 *
	 * @param array<string, mixed>          $template The template the slug resolves to.
	 * @param WooDecisionContextBuilder|null $builder  The builder.
	 *
	 * @return array{0: DocumentGenerationRequestService, 1: DocumentService&MockObject}
	 */
	private function generation(array $template, ?WooDecisionContextBuilder $builder): array {
		$documents = $this->createMock(DocumentService::class);
		$documents->method('buildOutputTargetPath')->willReturn('Filinq/woo');
		$slugs = $this->createMock(TemplateSlugResolver::class);
		$slugs->method('resolve')->willReturn($template);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return [
			new DocumentGenerationRequestService(
				documents: $documents,
				lookup: new TemplateLookup(templates: $this->createMock(TemplateService::class), slugs: $slugs),
				renderer: $this->createMock(TemplateRenderer::class),
				objectResolver: $this->createMock(DocumentObjectServiceResolver::class),
				userSession: $session,
				eventDispatcher: $this->createMock(IEventDispatcher::class),
				wooDecisions: $builder
			),
			$documents,
		];
	}//end generation()

	public function testARefusedDecisionWritesNoFile(): void {
		$decision = self::decision();
		$decision['documents'][2]['groundCodes'] = [];
		[$service, $documents] = $this->generation(['id' => 't-1', 'slug' => 'woo-besluit', 'dataContract' => 'woo-decision', 'content' => 'x'], $this->builder());
		$documents->expects($this->never())->method('generateFromTemplate');

		$this->expectException(WooDecisionContractException::class);
		$this->expectExceptionMessage('The Woo decision cannot be rendered: Document 3 is assessed withhold but cites no ground.');

		$service->generate(['templateSlug' => 'woo-besluit', 'templateNamespace' => 'filinq', 'data' => ['wooDecision' => $decision]], 'dossiq');
	}//end testARefusedDecisionWritesNoFile()

	public function testAnAcceptedDecisionRendersWithItsGroundsInWords(): void {
		[$service, $documents] = $this->generation(['id' => 't-1', 'slug' => 'woo-besluit', 'dataContract' => 'woo-decision', 'content' => 'x'], $this->builder());
		$documents->expects($this->once())->method('generateFromTemplate')
			->with('t-1', $this->anything(), [], $this->callback(
				static fn (array $options): bool => $options['adHocData']['wooDecision']['documents'][1]['grounds'][0]['label'] === 'Eerbiediging van de persoonlijke levenssfeer'
			))
			->willReturn(['content' => 'x', 'format' => 'pdf', 'warnings' => [], 'metadata' => [], 'output' => ['fileId' => 1, 'path' => '/p', 'name' => 'n', 'size' => 1]]);

		$service->generate(['templateSlug' => 'woo-besluit', 'templateNamespace' => 'filinq', 'data' => ['wooDecision' => self::decision()]], 'dossiq');
	}//end testAnAcceptedDecisionRendersWithItsGroundsInWords()

	public function testOtherTemplatesAreUntouched(): void {
		[$service, $documents] = $this->generation(['id' => 't-2', 'slug' => 'brief', 'content' => 'x'], null);
		$documents->expects($this->once())->method('generateFromTemplate')
			->with('t-2', $this->anything(), [], $this->callback(static fn (array $options): bool => $options['adHocData']['wooDecision'] === 'anything'))
			->willReturn(['content' => 'x', 'format' => 'pdf', 'warnings' => [], 'metadata' => [], 'output' => ['fileId' => 1, 'path' => '/p', 'name' => 'n', 'size' => 1]]);

		$service->generate(['templateSlug' => 'brief', 'templateNamespace' => 'filinq', 'data' => ['wooDecision' => 'anything']], 'dossiq');
	}//end testOtherTemplatesAreUntouched()

	public function testADeclaredContractWithoutABuilderRefuses(): void {
		[$service, $documents] = $this->generation(['id' => 't-1', 'slug' => 'woo-besluit', 'dataContract' => 'woo-decision', 'content' => 'x'], null);
		$documents->expects($this->never())->method('generateFromTemplate');

		$this->expectExceptionMessage('This template declares the woo-decision data contract, which cannot be checked here.');

		$service->generate(['templateSlug' => 'woo-besluit', 'templateNamespace' => 'filinq', 'data' => ['wooDecision' => self::decision()]], 'dossiq');
	}//end testADeclaredContractWithoutABuilderRefuses()
}//end class
