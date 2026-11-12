<?php

/**
 * Golden test for the log entry CorrespondenceService writes for each generated letter.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/letter-correspondence-generation/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use Exception;
use JsonSerializable;
use OCA\Filinq\Kernel\JobQueueInterface;
use OCA\Filinq\Service\CorrespondenceRequest;
use OCA\Filinq\Service\CorrespondenceService;
use OCA\Filinq\Service\DataResolverService;
use OCA\Filinq\Service\PdfService;
use OCA\Filinq\Service\Support\OpenRegisterAccess;
use OCA\Filinq\Service\Support\PdfRendering;
use OCA\Filinq\Service\Support\TemplateSources;
use OCA\Filinq\Service\TemplateRenderer;
use OCA\Filinq\Service\TemplateService;
use OCA\OpenRegister\Service\Object\ObjectSaveOptions;
use OCA\OpenRegister\Service\ObjectService;
use OCP\App\IAppManager;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use Stringable;

/**
 * Every request, outcome and save answer through logCorrespondence(),
 * compared with JSON recorded from the implementation before it was split.
 *
 * @covers \OCA\Filinq\Service\CorrespondenceService
 * @covers \OCA\Filinq\Service\Support\TemplateSources
 * @covers \OCA\Filinq\Service\Support\PdfRendering
 * @covers \OCA\Filinq\Service\Support\OpenRegisterAccess
 * @uses   \OCA\Filinq\Service\CorrespondenceRequest
 */
class CorrespondenceLogGoldenTest extends TestCase {

	/**
	 * The options, the outcome, the configured default format and the save answer ('fails', 'object' or an array).
	 *
	 * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: string, 3: mixed}>
	 */
	public static function scenarios(): array {
		$outcome = ['dataRefs' => [['id' => 'klant-1'], ['id' => 'klant-2']], 'templateName' => 'Brief', 'status' => 'generated', 'errorMessage' => null];
		$options = ['recipientType' => 'person', 'caseReference' => 'Z-1', 'format' => 'docx', 'userId' => 'anna'];
		return [
			'everything' => [$options, $outcome, '', ['id' => 'log-1']],
			'no options' => [[], $outcome, '', ['id' => 'log-2']],
			'the configured format' => [[], $outcome, 'odt', ['id' => 'log-3']],
			'no data refs' => [$options, ['dataRefs' => []] + $outcome, '', ['id' => 'log-4']],
			'a first ref without id' => [$options, ['dataRefs' => [['naam' => 'x'], ['id' => 'y']]] + $outcome, '', ['id' => 'log-5']],
			'a failure' => [$options, ['status' => 'failed', 'errorMessage' => 'template gone'] + $outcome, '', ['id' => 'log-6']],
			'an entity answer' => [$options, $outcome, '', 'object'],
			'the save fails' => [$options, $outcome, '', 'fails'],
		];
	}//end scenarios()

	/**
	 * The answer, the saved entry and the log match the recorded ones.
	 *
	 * @param array<string, mixed> $options The request options.
	 * @param array<string, mixed> $outcome The generation outcome.
	 * @param string $format The configured default format.
	 * @param mixed $answer The save answer.
	 *
	 * @dataProvider scenarios
	 *
	 * @return void
	 */
	public function testLog(array $options, array $outcome, string $format, mixed $answer): void {
		$logger = new class extends AbstractLogger {
			/**
			 * @var array<int, array<int, mixed>>
			 */
			public array $lines = [];

			/**
			 * Record one line.
			 *
			 * @param mixed $level The level.
			 * @param string|Stringable $message The message.
			 * @param array<mixed> $context The context.
			 *
			 * @return void
			 */
			public function log($level, string|Stringable $message, array $context = []): void {
				$this->lines[] = [$level, (string)$message, $context];
			}
		};

		$saved = [];
		$objects = $this->createMock(ObjectService::class);
		$objects->method('saveObject')->willReturnCallback(function (...$args) use (&$saved, $answer) {
			$entry = $args['object'] ?? $args[0];
			$saved[] = ['generatedAt is ISO 8601' => preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d[+-]\d\d:\d\d$/', (string)($entry['generatedAt'] ?? '')) === 1] + array_diff_key($entry, ['generatedAt' => true]) + ['rest' => self::restInOldShape(args: $args)];
			if ($answer === 'fails') {
				throw new Exception('register offline');
			}

			if ($answer === 'object') {
				return new class implements JsonSerializable {
					/**
					 * The serialized log entry.
					 *
					 * @return array<string, mixed>
					 */
					public function jsonSerialize(): array {
						return ['id' => 'log-object'];
					}
				};
			}

			return $answer;
		});
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturn($objects);
		$apps = $this->createMock(IAppManager::class);
		$apps->method('getInstalledApps')->willReturn(['openregister']);
		$config = $this->createMock(IAppConfig::class);
		$config->method('getValueString')->willReturn($format);

		$service = new CorrespondenceService(
			new TemplateSources(templates: $this->createMock(TemplateService::class), data: $this->createMock(DataResolverService::class)),
			new PdfRendering(renderer: $this->createMock(TemplateRenderer::class), pdf: $this->createMock(PdfService::class)),
			new OpenRegisterAccess(apps: $apps, container: $container),
			$this->createMock(JobQueueInterface::class),
			$logger,
			$config
		);
		$request = new CorrespondenceRequest(templateId: 'tpl-1', options: $options);
		$result = (new ReflectionMethod($service, 'logCorrespondence'))->invoke($service, $request, $outcome);
		$trace = ['result' => $result, 'saved' => $saved, 'logs' => $logger->lines];

		$path = __DIR__ . '/golden/correspondence-log.json';
		$recorded = [];
		if (is_file($path) === true) {
			$recorded = json_decode((string)file_get_contents($path), true);
		}

		$name = (string)$this->dataName();
		if (getenv('GOLDEN_RECORD') === '1') {
			$recorded[$name] = $trace;
			file_put_contents($path, json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		}

		$this->assertSame($recorded[$name] ?? null, $trace);
	}//end testLog()

	/**
	 * The save arguments after the object, recorded in the pre-#9123 order:
	 * register, schema, uuid, _rbac, _multitenancy, _unowned, _owner.
	 *
	 * @param array<int|string, mixed> $args The saveObject() arguments.
	 *
	 * @return array<int, mixed> The rest.
	 */
	private static function restInOldShape(array $args): array {
		$options = ($args[1] ?? null);
		if (($options instanceof ObjectSaveOptions) === false) {
			return array_slice($args, 1);
		}

		return [
			$options->target->register,
			$options->target->schema,
			$options->uuid,
			($args[2] ?? true),
			($args[3] ?? true),
			$options->actor->unowned,
			$options->actor->owner,
		];

	}//end restInOldShape()
}//end class
