<?php

/**
 * Unit tests for IntakeRoutingService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeRoutingService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that routing is the consumer's declaration applied, never a default
 * invented here, and that a type nobody declared routes nowhere on purpose.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeRoutingServiceTest extends TestCase {

	/**
	 * Declarations the fake OpenRegister was asked to store.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $written = [];

	/**
	 * Build the service over a fake register holding the given declarations.
	 *
	 * @param array<int, array<string, mixed>> $declarations The stored declarations.
	 *
	 * @return IntakeRoutingService The service under test.
	 */
	private function service(array $declarations): IntakeRoutingService {
		$objectService = $this->createMock(ObjectService::class);
		$objectService->method('searchObjects')->willReturnCallback(
			static function (array $query) use ($declarations): array {
				$matches = [];
				foreach ($declarations as $row) {
					if ((string)($row['declaringApp'] ?? '') !== (string)($query['declaringApp'] ?? '')) {
						continue;
					}

					if ((string)($row['typeReference'] ?? '') !== (string)($query['typeReference'] ?? '')) {
						continue;
					}

					$matches[] = $row;
				}

				return $matches;
			}
		);
		$objectService->method('saveObject')->willReturnCallback(
			function (...$arguments): array {
				$this->written[] = ($arguments[0] ?? []);

				return ($arguments[0] ?? []);
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		return new IntakeRoutingService($resolver, $this->createMock(LoggerInterface::class));

	}//end service()

	/**
	 * A bezwaar routes to the jurists and waits for acceptance.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADeclaredTypeRoutesToItsGroupAndWaitsForAcceptance(): void {
		$service = $this->service(
			declarations: [
				[
					'uuid' => 'routing-1',
					'declaringApp' => 'dossiq',
					'typeReference' => 'bezwaarschrift',
					'routeTo' => 'juristen',
					'requiresAcceptance' => true,
				],
			]
		);

		$document = $service->apply(document: [], declaringApp: 'dossiq', typeReference: 'bezwaarschrift');

		$this->assertSame('juristen', $document['routing']);
		$this->assertTrue($document['acceptance']['required']);

	}//end testADeclaredTypeRoutesToItsGroupAndWaitsForAcceptance()

	/**
	 * No declaration, no routing, and no acceptance step either.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testARecordTypeNobodyDeclaredRoutesNowhere(): void {
		$service = $this->service(declarations: []);

		$document = $service->apply(document: [], declaringApp: 'dossiq', typeReference: 'melding');

		$this->assertSame('', $document['routing']);
		$this->assertFalse($document['acceptance']['required']);

	}//end testARecordTypeNobodyDeclaredRoutesNowhere()

	/**
	 * Declaring twice updates the declaration rather than adding a second one.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testDeclaringAgainReplacesTheEarlierDeclaration(): void {
		$service = $this->service(
			declarations: [
				[
					'uuid' => 'routing-1',
					'declaringApp' => 'dossiq',
					'typeReference' => 'bezwaarschrift',
					'routeTo' => 'juristen',
					'requiresAcceptance' => true,
				],
			]
		);

		$service->declare(
			declaringApp: 'dossiq',
			typeReference: 'bezwaarschrift',
			routeTo: 'bezwaarcommissie',
			requiresAcceptance: false
		);

		$this->assertCount(1, $this->written);
		$this->assertSame('bezwaarcommissie', $this->written[0]['routeTo']);

	}//end testDeclaringAgainReplacesTheEarlierDeclaration()

	/**
	 * A declaration that does not name its app and type is refused.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADeclarationWithoutAnAppOrATypeIsRefused(): void {
		$service = $this->service(declarations: []);

		$this->expectException(RuntimeException::class);
		$service->declare(declaringApp: '', typeReference: 'bezwaarschrift', routeTo: 'juristen', requiresAcceptance: false);

	}//end testADeclarationWithoutAnAppOrATypeIsRefused()
}//end class
