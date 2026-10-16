<?php

/**
 * An InboundClassificationService over the real classes, for the service, runner and controller tests
 *
 * The classificationResult rows live in the in-memory ClassificationObjectStore
 * (every write validated with Opis against the real fragment); detected
 * entities come from a mapper double keyed by file id; the toggle is a
 * property.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service\Classification
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/changes/inbound-auto-classification/tasks.md#4-1
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service\Classification;

require_once __DIR__ . '/../Wizard/WizardDoubles.php';
require_once __DIR__ . '/ClassificationDoubles.php';

use OCA\Filinq\Service\Classification\ClassificationResultRepository;
use OCA\Filinq\Service\Classification\ClassificationSources;
use OCA\Filinq\Service\Classification\CorrespondentRanker;
use OCA\Filinq\Service\Classification\DossierMatcher;
use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\DocumentTypeClassifier;
use OCA\Filinq\Service\FileEntityStatsService;
use OCA\Filinq\Service\InboundClassificationService;
use OCA\OpenRegister\Db\EntityRelationMapper;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

/**
 * Builds the suggestion service and keeps its store inspectable.
 */
trait InboundClassificationFixture {

	/**
	 * An invoice's OCR text.
	 *
	 * @var string
	 */
	public const INVOICE = "Heijmans B.V.\nFactuur\nFactuurnummer: 2026-0412\nTe betalen binnen 30 dagen op IBAN NL91ABNA0417164300.\nBedrag excl. btw: 1.250,00";

	/**
	 * The in-memory OpenRegister.
	 *
	 * @var ClassificationObjectStore
	 */
	private ClassificationObjectStore $store;

	/**
	 * Detected entity rows per file id; a missing file id has had no detection.
	 *
	 * @var array<int, array<int, array<string, mixed>>>
	 */
	private array $entities = [];

	/**
	 * The toggle value.
	 *
	 * @var string
	 */
	private string $toggle = '1';

	/**
	 * The service over the real classes.
	 *
	 * @return InboundClassificationService The service.
	 */
	private function service(): InboundClassificationService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		$resolver->method('resolve')->willReturn($this->store);

		$mapper = $this->createMock(EntityRelationMapper::class);
		$mapper->method('findEntitiesForFile')->willReturnCallback(fn (int $fileId): array => ($this->entities[$fileId] ?? []));
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($mapper);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);

		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturnCallback(fn (string $app, string $key, string $default): string => $key === InboundClassificationService::TOGGLE ? $this->toggle : $default);

		return new InboundClassificationService(
			classifier: new DocumentTypeClassifier(),
			ranker: new CorrespondentRanker(),
			matcher: new DossierMatcher(),
			results: new ClassificationResultRepository(objectResolver: $resolver),
			sources: new ClassificationSources(entityStats: new FileEntityStatsService(new NullLogger(), $container, $apps), objectResolver: $resolver, logger: new NullLogger()),
			appConfig: $config,
			logger: new NullLogger(),
		);

	}//end service()

	/**
	 * An intake document with OCR text.
	 *
	 * @return array<string, mixed> The fields.
	 */
	private function intake(): array {
		return ['channel' => 'scan', 'status' => 'received', 'file' => 812010, 'fileName' => 'scan-factuur-heijmans.pdf', 'contentText' => self::INVOICE];

	}//end intake()

	/**
	 * The intake document's reference.
	 *
	 * @return array<string, string> The id, register and schema.
	 */
	private function ref(): array {
		return ['id' => 'intake-1', 'register' => 'filinq', 'schema' => 'intakeDocument'];

	}//end ref()
}//end trait
