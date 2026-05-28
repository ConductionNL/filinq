<?php

/**
 * Unit tests for NativeSigningProvider — finding #287 regression coverage.
 *
 * Asserts that signing sessions are persisted via the OpenRegister
 * ObjectService instead of in a per-request `$sessions` array, so that
 * `checkStatus`, `downloadSignedDocument` and `cancelSigning` running in
 * separate HTTP requests can resolve a session that `initiateSigning`
 * created.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Signing;

use OCA\DocuDesk\Service\Signing\NativeSigningProvider;
use OCA\DocuDesk\Service\SettingsService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Service\ObjectService;
use OCP\IAppConfig;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Tests for NativeSigningProvider session persistence
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service\Signing
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class NativeSigningProviderTest extends TestCase
{

    /**
     * In-memory session store shared between the provider instances under test
     *
     * @var array<string, array<string, mixed>>
     */
    private array $store = [];


    /**
     * Build a NativeSigningProvider wired to an OR ObjectService fake that
     * persists rows in `$this->store` keyed by externalId.
     *
     * @return NativeSigningProvider
     */
    private function buildProvider(): NativeSigningProvider
    {
        $userSession = $this->createMock(IUserSession::class);
        $logger      = $this->createMock(LoggerInterface::class);

        $config = $this->createMock(IAppConfig::class);
        $config->method('getValueString')->willReturnCallback(
            function (string $app, string $key, string $default = ''): string {
                return $default;
            }
        );

        // Mock the real OR ObjectService — `findAll` and `saveObject` are
        // real public methods on the deployed class so `createMock` lets us
        // stub them. `findAll(array $config)` returns the matching rows;
        // `saveObject(array $object, ...)` writes to the in-memory store.
        $objectService = $this->getMockBuilder(ObjectService::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['findAll', 'saveObject'])
            ->getMock();

        $objectService->method('findAll')->willReturnCallback(
            function (array $config = []): array {
                $filters    = $config['filters'] ?? [];
                $externalId = $filters['externalId'] ?? null;
                if ($externalId === null) {
                    return array_values($this->store);
                }

                if (isset($this->store[(string) $externalId]) === true) {
                    return [$this->store[(string) $externalId]];
                }

                return [];
            }
        );
        $objectService->method('saveObject')->willReturnCallback(
            function (array|object $object, string $register='', string $schema=''): array {
                $row = is_array($object) === true ? $object : (array) $object;
                $key = (string) ($row['externalId'] ?? $row['uuid'] ?? '');

                $this->store[$key] = $row;

                // Return the row array — the provider uses saveObject return
                // value to read the persisted id/uuid fields.
                return $row;
            }
        );

        $settingsService = $this->createMock(SettingsService::class);
        $settingsService->method('getObjectService')->willReturn($objectService);

        return new NativeSigningProvider(
            $userSession,
            $logger,
            $settingsService,
            $config
        );

    }//end buildProvider()


    /**
     * Reset the shared store before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->store = [];

    }//end setUp()


    /**
     * Initiate persists the session in OR and a subsequent checkStatus
     * (potentially on a different provider instance) finds it.
     *
     * @return void
     */
    public function testInitiateThenCheckStatusReturnsPersistedState(): void
    {
        $provider = $this->buildProvider();

        $result = $provider->initiateSigning(
            documentPath: '/foo.pdf',
            documentName: 'foo.pdf',
            signers: [['userId' => 'alice']],
            level: 'SES'
        );

        $this->assertTrue($result['success']);
        $externalId = $result['externalId'];
        $this->assertArrayHasKey($externalId, $this->store);

        $status = $provider->checkStatus(externalId: $externalId);

        $this->assertSame('pending', $status['status']);
        $this->assertSame([['userId' => 'alice']], $status['signers']);

    }//end testInitiateThenCheckStatusReturnsPersistedState()


    /**
     * Sessions written by one provider instance are visible to another —
     * the precise bug from #287 was that a fresh class instance had an
     * empty array.
     *
     * @return void
     */
    public function testSessionsSurviveAcrossProviderInstances(): void
    {
        $first = $this->buildProvider();

        $result     = $first->initiateSigning('/foo.pdf', 'foo.pdf', [], 'SES');
        $externalId = $result['externalId'];

        $second = $this->buildProvider();
        $status = $second->checkStatus(externalId: $externalId);
        $this->assertSame('pending', $status['status']);

        $this->assertTrue($second->cancelSigning(externalId: $externalId));
        $this->assertSame('cancelled', $this->store[$externalId]['status']);

    }//end testSessionsSurviveAcrossProviderInstances()


    /**
     * checkStatus on an unknown externalId throws (not silently returns).
     *
     * @return void
     */
    public function testCheckStatusOnMissingSessionThrows(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(RuntimeException::class);
        $provider->checkStatus(externalId: 'native-does-not-exist');

    }//end testCheckStatusOnMissingSessionThrows()


    /**
     * downloadSignedDocument refuses to return a path while the session
     * status is still `pending`.
     *
     * @return void
     */
    public function testDownloadRefusesWhenNotCompleted(): void
    {
        $provider = $this->buildProvider();

        $result     = $provider->initiateSigning('/foo.pdf', 'foo.pdf', [], 'SES');
        $externalId = $result['externalId'];

        $this->expectException(RuntimeException::class);
        $provider->downloadSignedDocument(externalId: $externalId);

    }//end testDownloadRefusesWhenNotCompleted()


    /**
     * downloadSignedDocument returns the persisted signedDocumentPath when
     * the session has completed (or falls back to documentPath until the
     * SES marker writer follow-up to #287 ships).
     *
     * @return void
     */
    public function testDownloadReturnsPathOnCompletion(): void
    {
        $provider = $this->buildProvider();

        $result     = $provider->initiateSigning('/foo.pdf', 'foo.pdf', [], 'SES');
        $externalId = $result['externalId'];

        // Mark the session completed in the OR-backed fake store.
        $this->store[$externalId]['status']             = 'completed';
        $this->store[$externalId]['signedDocumentPath'] = '/foo.signed.pdf';

        $this->assertSame(
            '/foo.signed.pdf',
            $provider->downloadSignedDocument(externalId: $externalId)
        );

    }//end testDownloadReturnsPathOnCompletion()


    /**
     * Non-SES levels are still rejected on initiate.
     *
     * @return void
     */
    public function testNonSesLevelRejected(): void
    {
        $provider = $this->buildProvider();

        $this->expectException(RuntimeException::class);
        $provider->initiateSigning('/x.pdf', 'x.pdf', [], 'QES');

    }//end testNonSesLevelRejected()


}//end class
