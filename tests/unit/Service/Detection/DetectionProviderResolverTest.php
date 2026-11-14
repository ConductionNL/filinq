<?php

/**
 * Tests for DetectionProviderResolver: per-organisation selection and the honest fallback.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service\Detection
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/llm-entity-detection-provider/specs/llm-entity-detection-provider/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Detection;
use OCA\Filinq\Service\Detection\DefaultDetectionProvider;
use OCA\Filinq\Service\Detection\DetectionCatalogue;
use OCA\Filinq\Service\Detection\DetectionProviderConfig;
use OCA\Filinq\Service\Detection\DetectionProviderResolver;
use OCA\Filinq\Service\Detection\DetectionRequest;
use OCA\Filinq\Service\Detection\DetectionResult;
use OCA\Filinq\Service\Detection\LlmDetectionProvider;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use UnexpectedValueException;

/**
 * @covers \OCA\Filinq\Service\Detection\DetectionProviderResolver
 * @uses   \OCA\Filinq\Service\Detection\DetectionResult
 * @uses   \OCA\Filinq\Service\Detection\DefaultDetectionProvider
 * @uses   \OCA\Filinq\Service\Detection\DetectionRequest
 */
class DetectionProviderResolverTest extends TestCase {

	/**
	 * The operator warning a fallback carries.
	 *
	 * @var string
	 */
	private const WARNING = 'LLM detection unavailable — fell back to the default detection; results may miss context-dependent entities.';

	/**
	 * Mocked config.
	 *
	 * @var DetectionProviderConfig&MockObject
	 */
	private DetectionProviderConfig&MockObject $config;

	/**
	 * Mocked LLM provider.
	 *
	 * @var LlmDetectionProvider&MockObject
	 */
	private LlmDetectionProvider&MockObject $llm;

	/**
	 * Mocked catalogue.
	 *
	 * @var DetectionCatalogue&MockObject
	 */
	private DetectionCatalogue&MockObject $catalogue;

	/**
	 * The resolver under test.
	 *
	 * @var DetectionProviderResolver
	 */
	private DetectionProviderResolver $resolver;

	/**
	 * Org A is on llm, everyone else on default.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(DetectionProviderConfig::class);
		$this->config->method('providerFor')->willReturnCallback(
			static fn (?string $organisationUuid): string => ($organisationUuid === 'org-a' ? 'llm' : 'default')
		);
		$this->llm = $this->createMock(LlmDetectionProvider::class);
		$this->llm->method('id')->willReturn('llm');
		$this->catalogue = $this->createMock(DetectionCatalogue::class);
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);

		$this->resolver = new DetectionProviderResolver(
			config: $this->config,
			defaultProvider: new DefaultDetectionProvider(),
			llmProvider: $this->llm,
			catalogue: $this->catalogue,
			l10n: $l10n,
			logger: $this->createMock(LoggerInterface::class)
		);
	}

	/**
	 * Assert a result is the honest fallback.
	 *
	 * @param DetectionResult $result The result.
	 *
	 * @return void
	 */
	private function assertFellBack(DetectionResult $result): void {
		$this->assertSame(
			['detectionProvider' => 'default', 'detectionFellBack' => true, 'detectionWarning' => self::WARNING],
			$result->toPayload()
		);
		$this->assertSame([], $result->entities);
	}

	/**
	 * Each organisation gets its own provider; the default path never touches the LLM.
	 *
	 * @return void
	 */
	public function testEachOrganisationGetsItsOwnProvider(): void {
		$this->assertSame('llm', $this->resolver->forOrganisation(organisationUuid: 'org-a')->id());
		$this->assertSame('default', $this->resolver->forOrganisation(organisationUuid: 'org-b')->id());

		$this->llm->expects($this->never())->method('detect');
		$this->catalogue->expects($this->never())->method('replace');
		$result = $this->resolver->run(organisationUuid: 'org-b', request: new DetectionRequest(fileId: 7, entityTypes: null));

		$this->assertSame(['detectionProvider' => 'default', 'detectionFellBack' => false, 'detectionWarning' => null], $result->toPayload());
	}

	/**
	 * An unavailable LLM provider falls back without being called.
	 *
	 * @return void
	 */
	public function testUnavailableFallsBackWithAWarning(): void {
		$this->llm->method('isAvailable')->willReturn(false);
		$this->llm->expects($this->never())->method('detect');
		$this->catalogue->expects($this->never())->method('replace');

		$this->assertFellBack($this->resolver->run(organisationUuid: 'org-a', request: new DetectionRequest(fileId: 7, entityTypes: null)));
	}

	/**
	 * Malformed output falls back, and nothing of it is written.
	 *
	 * @return void
	 */
	public function testMalformedOutputFallsBack(): void {
		$this->llm->method('isAvailable')->willReturn(true);
		$this->catalogue->method('chunks')->willReturn([['id' => 1, 'text' => 'Jan', 'startOffset' => 0]]);
		$this->llm->method('detect')->willThrowException(new UnexpectedValueException('The agentflow did not return an entity list.'));
		$this->catalogue->expects($this->never())->method('replace');

		$this->assertFellBack($this->resolver->run(organisationUuid: 'org-a', request: new DetectionRequest(fileId: 7, entityTypes: null)));
	}

	/**
	 * A failure on a later chunk discards the earlier chunks' entities too.
	 *
	 * @return void
	 */
	public function testAFailureOnAnyChunkWritesNothing(): void {
		$this->llm->method('isAvailable')->willReturn(true);
		$this->catalogue->method('chunks')->willReturn([
			['id' => 1, 'text' => 'Jan', 'startOffset' => 0],
			['id' => 2, 'text' => 'Piet', 'startOffset' => 100],
		]);
		$this->llm->method('detect')->willReturnOnConsecutiveCalls(
			new DetectionResult(entities: [$this->entity(value: 'Jan', start: 0)], providerUsed: 'llm'),
			$this->throwException(new RuntimeException('timed out'))
		);
		$this->catalogue->expects($this->never())->method('replace');

		$this->assertFellBack($this->resolver->run(organisationUuid: 'org-a', request: new DetectionRequest(fileId: 7, entityTypes: null)));
	}

	/**
	 * Entities from every chunk are written with absolute positions and their chunk, duplicates once.
	 *
	 * @return void
	 */
	public function testEntitiesAreWrittenWithAbsolutePositions(): void {
		$this->llm->method('isAvailable')->willReturn(true);
		$this->catalogue->method('hasLlmRelations')->willReturn(true);
		$this->catalogue->method('chunks')->with(7)->willReturn([
			['id' => 1, 'text' => 'Jan en Piet', 'startOffset' => 0],
			['id' => 2, 'text' => 'Piet', 'startOffset' => 7],
		]);
		$this->llm->expects($this->exactly(2))->method('detect')->willReturnOnConsecutiveCalls(
			new DetectionResult(entities: [$this->entity(value: 'Jan', start: 0), $this->entity(value: 'Piet', start: 7)], providerUsed: 'llm'),
			new DetectionResult(entities: [$this->entity(value: 'Piet', start: 0), $this->entity(value: 'Kees', start: null)], providerUsed: 'llm')
		);

		$written = null;
		$this->catalogue->expects($this->once())->method('replace')->willReturnCallback(
			static function (int $fileId, array $entities) use (&$written): int {
				$written = [$fileId, $entities];
				return count($entities);
			}
		);

		$result = $this->resolver->run(organisationUuid: 'org-a', request: new DetectionRequest(fileId: 7, entityTypes: ['PERSON'], force: true));

		$this->assertSame(7, $written[0]);
		$this->assertSame(
			[['Jan', 0, 3, 1], ['Piet', 7, 11, 1], ['Kees', null, null, 2]],
			array_map(static fn (array $e): array => [$e['value'], $e['positionStart'], $e['positionEnd'], $e['chunkId']], $written[1])
		);
		$this->assertSame(['detectionProvider' => 'llm', 'detectionFellBack' => false, 'detectionWarning' => null], $result->toPayload());
		$this->assertCount(3, $result->entities);
	}

	/**
	 * Resuming a file the LLM already ran on keeps its relations and does not call it.
	 *
	 * @return void
	 */
	public function testResumingKeepsTheExistingLlmRelations(): void {
		$this->llm->method('isAvailable')->willReturn(true);
		$this->catalogue->method('hasLlmRelations')->with(7)->willReturn(true);
		$this->llm->expects($this->never())->method('detect');
		$this->catalogue->expects($this->never())->method('replace');

		$result = $this->resolver->run(organisationUuid: 'org-a', request: new DetectionRequest(fileId: 7, entityTypes: null));

		$this->assertSame(['detectionProvider' => 'llm', 'detectionFellBack' => false, 'detectionWarning' => null], $result->toPayload());
	}

	/**
	 * The active organisation decides the provider.
	 *
	 * @return void
	 */
	public function testTheActiveOrganisationDecides(): void {
		$this->catalogue->method('activeOrganisationUuid')->willReturn('org-a');
		$this->llm->method('isAvailable')->willReturn(false);

		$this->assertFellBack($this->resolver->runForActiveOrganisation(request: new DetectionRequest(fileId: 7)));
	}

	/**
	 * A mapped person entity.
	 *
	 * @param string   $value The value.
	 * @param int|null $start The start offset in its chunk, or null.
	 *
	 * @return array<string, mixed> The entity.
	 */
	private function entity(string $value, ?int $start): array {
		return [
			'value'           => $value,
			'type'            => 'PERSON',
			'category'        => 'personal_data',
			'positionStart'   => $start,
			'positionEnd'     => ($start === null ? null : $start + strlen($value)),
			'confidence'      => 0.9,
			'detectionMethod' => 'llm',
		];
	}
}
