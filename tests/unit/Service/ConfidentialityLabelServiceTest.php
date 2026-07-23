<?php

/**
 * Unit tests for ConfidentialityLabelService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 *
 * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\ConfidentialityLabel;
use OCA\DocuDesk\Service\ConfidentialityLabelService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ConfidentialityLabelService
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConfidentialityLabelServiceTest extends TestCase
{

    /**
     * @var ConfidentialityLabelService
     */
    private ConfidentialityLabelService $service;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;

    /**
     * @var IAppManager|MockObject
     */
    private IAppManager|MockObject $mockAppManager;

    /**
     * @var IAppConfig|MockObject
     */
    private IAppConfig|MockObject $mockAppConfig;

    /**
     * @var ISystemTagManager|MockObject
     */
    private ISystemTagManager|MockObject $mockTagManager;

    /**
     * @var ISystemTagObjectMapper|MockObject
     */
    private ISystemTagObjectMapper|MockObject $mockTagObjectMapper;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLogger          = $this->createMock(LoggerInterface::class);
        $this->mockAppManager      = $this->createMock(IAppManager::class);
        $this->mockAppConfig       = $this->createMock(IAppConfig::class);
        $this->mockTagManager      = $this->createMock(ISystemTagManager::class);
        $this->mockTagObjectMapper = $this->createMock(ISystemTagObjectMapper::class);

        $this->service = new ConfidentialityLabelService(
            $this->mockLogger,
            $this->mockAppManager,
            $this->mockAppConfig,
            $this->mockTagManager,
            $this->mockTagObjectMapper
        );

    }//end setUp()

    /**
     * Build a mocked ISystemTag with a given id and display name.
     *
     * @param string $id   Tag id
     * @param string $name Tag display name
     *
     * @return ISystemTag|MockObject
     */
    private function makeTag(string $id, string $name): ISystemTag|MockObject
    {
        $tag = $this->createMock(ISystemTag::class);
        $tag->method('getId')->willReturn($id);
        $tag->method('getName')->willReturn($name);
        return $tag;

    }//end makeTag()

    /**
     * Files_confidential absent → null, no tag/vocabulary lookups performed.
     *
     * @return void
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-absent-app-yields-no-signal-no-crash
     */
    public function testAbsentAppReturnsNull(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['openregister']);

        $this->mockTagObjectMapper->expects($this->never())->method('getTagIdsForObjects');

        $this->assertNull($this->service->getLabelForFile(101));

    }//end testAbsentAppReturnsNull()

    /**
     * A file with no assigned tags returns null.
     *
     * @return void
     */
    public function testUntaggedFileReturnsNull(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['files_confidential']);
        $this->mockTagObjectMapper->method('getTagIdsForObjects')->willReturn(['101' => []]);

        $this->assertNull($this->service->getLabelForFile(101));

    }//end testUntaggedFileReturnsNull()

    /**
     * A file tagged with a name matching the vocabulary returns its label + level.
     *
     * @return void
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-labelled-file-returns-its-label-and-level
     */
    public function testLabelledFileReturnsLabel(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['files_confidential']);
        $this->mockTagObjectMapper->method('getTagIdsForObjects')->willReturn(['101' => ['tag-1']]);
        $this->mockTagManager->method('getTagsByIds')->willReturn(['tag-1' => $this->makeTag('tag-1', 'Confidential')]);
        $this->mockAppConfig->method('getValueString')->willReturn('');

        $label = $this->service->getLabelForFile(101);

        $this->assertInstanceOf(ConfidentialityLabel::class, $label);
        $this->assertSame('Confidential', $label->getLabel());
        $this->assertSame(2, $label->getLevel());

    }//end testLabelledFileReturnsLabel()

    /**
     * A tag name that does not appear in the vocabulary is ignored, so no
     * label resolves.
     *
     * @return void
     */
    public function testUnmatchedTagNameReturnsNull(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['files_confidential']);
        $this->mockTagObjectMapper->method('getTagIdsForObjects')->willReturn(['101' => ['tag-1']]);
        $this->mockTagManager->method('getTagsByIds')->willReturn(['tag-1' => $this->makeTag('tag-1', 'Not In Vocabulary')]);
        $this->mockAppConfig->method('getValueString')->willReturn('');

        $this->assertNull($this->service->getLabelForFile(101));

    }//end testUnmatchedTagNameReturnsNull()

    /**
     * When several assigned tags match the vocabulary, the highest-level
     * match wins.
     *
     * @return void
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#requirement-read-a-files-confidentiality-label-availability-guarded-req-ddfcl-001
     */
    public function testHighestLevelWinsOnMultipleMatches(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['files_confidential']);
        $this->mockTagObjectMapper->method('getTagIdsForObjects')->willReturn(['101' => ['tag-1', 'tag-2']]);
        $this->mockTagManager->method('getTagsByIds')->willReturn(
            [
                'tag-1' => $this->makeTag('tag-1', 'Internal'),
                'tag-2' => $this->makeTag('tag-2', 'Secret'),
            ]
        );
        $this->mockAppConfig->method('getValueString')->willReturn('');

        $label = $this->service->getLabelForFile(101);

        $this->assertSame('Secret', $label->getLabel());
        $this->assertSame(3, $label->getLevel());

    }//end testHighestLevelWinsOnMultipleMatches()

    /**
     * An admin-configured vocabulary (JSON) overrides the default names.
     *
     * @return void
     */
    public function testCustomVocabularyIsHonoured(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['files_confidential']);
        $this->mockTagObjectMapper->method('getTagIdsForObjects')->willReturn(['101' => ['tag-1']]);
        $this->mockTagManager->method('getTagsByIds')->willReturn(['tag-1' => $this->makeTag('tag-1', 'Zeer Geheim')]);
        $this->mockAppConfig->method('getValueString')->willReturn(json_encode(['Zeer Geheim' => 9]));

        $label = $this->service->getLabelForFile(101);

        $this->assertSame('Zeer Geheim', $label->getLabel());
        $this->assertSame(9, $label->getLevel());

    }//end testCustomVocabularyIsHonoured()

    /**
     * Any exception from the tag API degrades to "no label" rather than
     * propagating into the anonymisation path.
     *
     * @return void
     *
     * @spec openspec/changes/files-confidential-labels/specs/files-confidential-labels/spec.md#scenario-tag-api-failure-degrades-to-no-label
     */
    public function testTagApiFailureReturnsNull(): void
    {
        $this->mockAppManager->method('getInstalledApps')->willReturn(['files_confidential']);
        $this->mockTagObjectMapper->method('getTagIdsForObjects')->willThrowException(new \RuntimeException('tag service down'));

        $this->assertNull($this->service->getLabelForFile(101));

    }//end testTagApiFailureReturnsNull()
}//end class
