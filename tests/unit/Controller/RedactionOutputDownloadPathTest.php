<?php

/**
 * The download gate as a reader meets it: controller, gate and store together.
 *
 * 🔴 THIS FILE EXISTS BECAUSE EVERY OTHER SUITE DOUBLED THE THING THAT WAS
 * BROKEN. `DownloadAgreementGateTest` hands the gate a `forDocument` stub that
 * returns the terms, so it proves the rule and says nothing about whether the
 * terms are ever found. `RedactionOutputControllerTest` doubles the gate, so it
 * proves the route reaches something and says nothing about what that something
 * decides. The defect lived one layer below both: the store asked OpenRegister
 * with register and schema SLUGS on a call whose contract is numeric ids, got
 * zero rows and no error, and reported every gated document as having no terms.
 * Measured on a live instance on 2026-09-19: a declared, unaccepted agreement
 * and a document nobody had declared anything about both answered
 * `{"mayDownload":true,"gated":false}`.
 *
 * 🔑 SO THE ONLY DOUBLE HERE IS OPENREGISTER ITSELF, and it answers
 * `searchObjects` the way the real one does when handed a slug: with an empty
 * list. Nothing in this file mocks a filinq class. A regression to the
 * slug-unsafe call reddens the assertion on what the reader receives, not a
 * line of setup.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Controller
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

namespace OCA\Filinq\Tests\Unit\Controller;

use OCA\Filinq\Controller\RedactionOutputController;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\Redaction\DownloadAgreementGate;
use OCA\Filinq\Service\Redaction\DownloadAgreementRepository;
use OCA\Filinq\Service\Redaction\PublicationListComposer;
use OCA\Filinq\Service\Redaction\RedactionOutputGuard;
use OCA\OpenRegister\Service\ObjectService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * The download-agreement endpoints over the real gate and the real store.
 *
 * @covers \OCA\Filinq\Controller\RedactionOutputController
 * @covers \OCA\Filinq\Service\Redaction\DownloadAgreementGate
 * @covers \OCA\Filinq\Service\Redaction\DownloadAgreementRepository
 */
class RedactionOutputDownloadPathTest extends TestCase {

	/**
	 * The terms declared on the gated document.
	 *
	 * @var array<string, mixed>
	 */
	private const TERMS = [
		'document' => 'woo-besluit-7',
		'version' => '1',
		'text' => 'Hergebruik is toegestaan met bronvermelding.',
		'locale' => 'nl',
	];

	/**
	 * The rows the fake register holds, by reference for the write path.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $rows = [];

	/**
	 * Whether anything asked the slug-unsafe search during the last call.
	 *
	 * @var bool
	 */
	private bool $askedTheNumericIdSearch = false;

	/**
	 * Reset the fake register between tests.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->rows = [];
		$this->askedTheNumericIdSearch = false;

	}//end setUp()

	/**
	 * A controller over the real gate, the real store and a faked OpenRegister.
	 *
	 * `onlyMethods` on the ObjectService double so it cannot answer a call the
	 * real class does not declare.
	 *
	 * @param \Throwable|null $readFails What the register throws on a read, if anything.
	 * @param string          $uid       Who is asking.
	 *
	 * @return RedactionOutputController The controller, wired as the container wires it.
	 */
	private function controller(?\Throwable $readFails = null, string $uid = 'sem'): RedactionOutputController {
		$objectService = $this->getMockBuilder(ObjectService::class)
			->disableOriginalConstructor()
			->onlyMethods(['searchObjects', 'searchObjectsBySlug', 'saveObject'])
			->getMock();

		// The real `searchObjects` has a numeric-id contract on
		// `@self.register` and `@self.schema`. Handed the slugs this app uses
		// it returns an empty list and no error, which is exactly what the
		// defect looked like, so the fake answers the same way.
		$objectService->method('searchObjects')->willReturnCallback(
			function (array $query = []): array {
				$this->askedTheNumericIdSearch = true;

				return [];
			}
		);

		$objectService->method('searchObjectsBySlug')->willReturnCallback(
			function (
				string $registerSlug,
				string $schemaSlug,
				array $filters = [],
				bool $_rbac = true,
				bool $_multitenancy = true
			) use ($readFails): array {
				if ($readFails !== null) {
					throw $readFails;
				}

				$document = (string)($filters['document'] ?? '');

				return array_values(
					array_filter(
						$this->rows,
						static fn (array $row): bool => ((string)($row['document'] ?? '')) === $document
					)
				);
			}
		);

		$objectService->method('saveObject')->willReturnCallback(
			function (array $object = [], string $register = '', string $schema = ''): array {
				$this->rows[] = $object;

				return $object;
			}
		);

		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($objectService);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$session = $this->createMock(IUserSession::class);
		$session->method('getUser')->willReturn($user);

		return new RedactionOutputController(
			'filinq',
			$this->createMock(IRequest::class),
			$this->getMockBuilder(RedactionOutputGuard::class)
				->disableOriginalConstructor()
				->onlyMethods(['markChecked'])
				->getMock(),
			$this->getMockBuilder(PublicationListComposer::class)
				->disableOriginalConstructor()
				->onlyMethods(['compose'])
				->getMock(),
			new DownloadAgreementGate(
				agreements: new DownloadAgreementRepository(
					objectResolver: $resolver,
					logger: new NullLogger()
				)
			),
			$session
		);

	}//end controller()

	/**
	 * A document gated on terms nobody accepted is refused to the reader.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAnUnacceptedAgreementRefusesTheDownload(): void {
		$this->rows = [self::TERMS];

		$answer = $this->controller()->agreement(document: 'woo-besluit-7')->getData();

		$this->assertFalse(
			$answer['mayDownload'],
			'a document gated on terms nobody has accepted must not be served'
		);
		$this->assertTrue($answer['gated'], 'the reader must be told the file is gated');
		$this->assertSame(DownloadAgreementGate::NOT_ACCEPTED, $answer['reason']);
		$this->assertStringContainsString(
			'bronvermelding',
			$answer['agreement']['text'],
			'the reader is shown the terms they have to accept'
		);

	}//end testAnUnacceptedAgreementRefusesTheDownload()

	/**
	 * The two situations do not produce the same answer.
	 *
	 * 🔴 THIS IS THE ONE THE LIVE RUN FAILED. Both answered
	 * `{"mayDownload":true,"gated":false}`, so the endpoint could not tell a
	 * declared agreement from a document nobody had declared anything about.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testADeclaredAgreementAndNoAgreementAtAllAnswerDifferently(): void {
		$this->rows = [self::TERMS];
		$controller = $this->controller();

		$gated = $controller->agreement(document: 'woo-besluit-7')->getData();
		$ungated = $controller->agreement(document: 'no-such-document-zzz')->getData();

		$this->assertFalse($gated['mayDownload'], 'a declared agreement holds the file');
		$this->assertTrue($ungated['mayDownload'], 'a document nobody gated downloads as it always did');
		$this->assertFalse($ungated['gated']);
		$this->assertNotSame(
			$gated,
			$ungated,
			'a declared agreement and no agreement at all must not answer the same thing'
		);

	}//end testADeclaredAgreementAndNoAgreementAtAllAnswerDifferently()

	/**
	 * A register that cannot be read refuses, rather than reading as ungated.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAStoreThatCannotBeReadRefusesTheDownload(): void {
		$answer = $this->controller(readFails: new RuntimeException('register unreachable'))
			->agreement(document: 'woo-besluit-7')
			->getData();

		$this->assertFalse(
			$answer['mayDownload'],
			'a read that failed is not permission: nothing is served when the terms cannot be read'
		);
		$this->assertSame(DownloadAgreementGate::NOT_KNOWN, $answer['reason']);

	}//end testAStoreThatCannotBeReadRefusesTheDownload()

	/**
	 * An instance that never imported the schema gates nothing, and says so.
	 *
	 * Absence of the schema is an answer, not a failed read: refusing every
	 * download over a feature nobody enabled would take the download surface
	 * down instead of guarding it.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAnInstanceWithoutTheSchemaGatesNothing(): void {
		$answer = $this->controller(readFails: new DoesNotExistException('no downloadAgreement schema'))
			->agreement(document: 'woo-besluit-7')
			->getData();

		$this->assertTrue($answer['mayDownload'], 'an instance with no agreement schema gates nothing');
		$this->assertFalse($answer['gated']);

	}//end testAnInstanceWithoutTheSchemaGatesNothing()

	/**
	 * Accepting the terms in force frees the file, and the next read agrees.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAcceptingTheTermsFreesTheFileOnTheNextRead(): void {
		$this->rows = [self::TERMS];
		$controller = $this->controller();

		$accepted = $controller->acceptAgreement(document: 'woo-besluit-7', version: '1');
		$this->assertSame(Http::STATUS_OK, $accepted->getStatus());
		$this->assertTrue($accepted->getData()['mayDownload']);

		$after = $controller->agreement(document: 'woo-besluit-7')->getData();
		$this->assertTrue(
			$after['mayDownload'],
			'a reader who accepted the version in force may have the file'
		);
		$this->assertTrue($after['gated'], 'it is still a gated file, now with an acceptance behind it');

	}//end testAcceptingTheTermsFreesTheFileOnTheNextRead()

	/**
	 * Republished terms ask again, and the acceptance of the old text does not carry.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testANewVersionAsksAgain(): void {
		$this->rows = [self::TERMS];
		$controller = $this->controller();
		$controller->acceptAgreement(document: 'woo-besluit-7', version: '1');

		// The terms move on. The old row stays where it is, which is how the
		// store actually behaves, so the gate has to pick the newer one.
		$this->rows[] = [
			'document' => 'woo-besluit-7',
			'version' => '2',
			'text' => 'Hergebruik is toegestaan met bronvermelding en zonder bewerking.',
			'locale' => 'nl',
		];

		$answer = $controller->agreement(document: 'woo-besluit-7')->getData();

		$this->assertFalse(
			$answer['mayDownload'],
			'a reader who accepted version 1 is asked again about version 2'
		);
		$this->assertSame(DownloadAgreementGate::VERSION_MOVED_ON, $answer['reason']);
		$this->assertStringContainsString('zonder bewerking', $answer['agreement']['text']);

	}//end testANewVersionAsksAgain()

	/**
	 * An acceptance the endpoint cannot honour answers 409, never 200.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testAnAcceptanceOfTheWrongVersionIsAConflict(): void {
		$this->rows = [self::TERMS];

		$response = $this->controller()->acceptAgreement(document: 'woo-besluit-7', version: '9');

		$this->assertSame(
			Http::STATUS_CONFLICT,
			$response->getStatus(),
			'a refusal dressed as 200 is how a caller reading the status alone serves the file'
		);
		$this->assertFalse($response->getData()['mayDownload']);

	}//end testAnAcceptanceOfTheWrongVersionIsAConflict()

	/**
	 * The store never asks the search whose contract it cannot meet.
	 *
	 * A companion to the assertions above rather than a substitute: those fail
	 * on what the reader receives, this one names why.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/redaction-and-what-leaves-the-building/specs/redaction-output-guarantee/spec.md
	 */
	public function testTheStoreUsesTheSlugAwareSearch(): void {
		$this->rows = [self::TERMS];

		$this->controller()->agreement(document: 'woo-besluit-7');

		$this->assertFalse(
			$this->askedTheNumericIdSearch,
			'searchObjects takes numeric ids and answers a slug with an empty list, so the slug-aware call is the only safe one'
		);

	}//end testTheStoreUsesTheSlugAwareSearch()
}//end class
