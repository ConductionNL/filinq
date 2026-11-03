<?php

/**
 * Golden test for how services and events are wired by their constructors.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Support
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec exclude Characterization of constructor wiring; no behaviour of its own.
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Each class is built from labelled doubles: every constructor parameter gets
 * a double named after its type, a parameter whose type is a collaborator
 * bundle (namespace `...\Support\`) is built the same way, and optional
 * parameters are left out in one variant and given in the other. The golden
 * pins every double, scalar and self-built object the instance holds, and
 * how often, so the wiring is the same whatever shape the constructor has
 * and whichever property or bundle holds a collaborator. RECORD_GOLDEN=1 rewrites
 * the golden file.
 *
 * @covers \OCA\Filinq\Service\DossierSummaryDataService
 * @covers \OCA\Filinq\Service\EmlAttachmentRenderer
 * @covers \OCA\Filinq\Service\DocumentService
 * @covers \OCA\Filinq\Service\IntakeService
 * @covers \OCA\Filinq\Service\EntityConsolidationService
 * @covers \OCA\Filinq\Service\LegalBasesSummaryService
 * @covers \OCA\Filinq\Service\FolderBatchService
 * @covers \OCA\Filinq\Service\FinancialExtractionService
 * @covers \OCA\Filinq\Service\DocumentAnonymizeRunner
 * @covers \OCA\Filinq\Service\AnonymizationService
 * @covers \OCA\Filinq\Event\IntakeDocumentReceivedEvent
 * @covers \OCA\Filinq\Event\FinancialExtractionCompletedEvent
 * @covers \OCA\Filinq\Event\GlAccountSuggestedEvent
 * @uses \OCA\Filinq\Event\Support\IntakeArrival
 * @uses \OCA\Filinq\Event\Support\RequestOrigin
 * @uses \OCA\Filinq\Service\BaseLabelResolver
 * @uses \OCA\Filinq\Service\DossierEntityCollector
 * @uses \OCA\Filinq\Service\DossierObjectRepository
 * @uses \OCA\Filinq\Service\DossierPlaceholderRanker
 * @uses \OCA\Filinq\Service\FolderFileEnumerator
 * @uses \OCA\Filinq\Service\Support\AnonymisationRecords
 * @uses \OCA\Filinq\Service\Support\AnonymisedOutputs
 * @uses \OCA\Filinq\Service\Support\BatchQueue
 * @uses \OCA\Filinq\Service\Support\DocumentOutput
 * @uses \OCA\Filinq\Service\Support\DocumentProduction
 * @uses \OCA\Filinq\Service\Support\FolderEnumerationSources
 * @uses \OCA\Filinq\Service\Support\IntakeFiling
 * @uses \OCA\Filinq\Service\Support\IntakeRouting
 * @uses \OCA\Filinq\Service\Support\OpenRegisterAccess
 * @uses \OCA\Filinq\Service\Support\TemplateSources
 * @uses \OCA\Filinq\Service\Support\DossierSummaryParts
 */
class ConstructorWiringGoldenTest extends TestCase {

	private const GOLDEN = __DIR__.'/golden/constructor-wiring.json';

	/**
	 * Labels of the doubles handed out, by object id.
	 *
	 * @var array<int, string>
	 */
	private array $labels = [];

	/**
	 * The classes, and whether their optional parameters are given.
	 *
	 * @return array<string, array{0: class-string, 1: bool}> The cases.
	 */
	public static function classes(): array {
		$classes = [
			'OCA\Filinq\Service\DossierSummaryDataService',
			'OCA\Filinq\Service\EmlAttachmentRenderer',
			'OCA\Filinq\Service\DocumentService',
			'OCA\Filinq\Service\IntakeService',
			'OCA\Filinq\Service\EntityConsolidationService',
			'OCA\Filinq\Service\LegalBasesSummaryService',
			'OCA\Filinq\Service\FolderBatchService',
			'OCA\Filinq\Service\FinancialExtractionService',
			'OCA\Filinq\Service\DocumentAnonymizeRunner',
			'OCA\Filinq\Service\AnonymizationService',
			'OCA\Filinq\Event\IntakeDocumentReceivedEvent',
			'OCA\Filinq\Event\FinancialExtractionCompletedEvent',
			'OCA\Filinq\Event\GlAccountSuggestedEvent',
		];
		$cases = [];
		foreach ($classes as $class) {
			$short = substr($class, (strrpos($class, '\\') + 1));
			$cases[$short.' defaults'] = [$class, false];
			$cases[$short.' given']    = [$class, true];
		}

		return $cases;

	}//end classes()

	/**
	 * The wiring matches the golden.
	 *
	 * @param class-string $class The class.
	 * @param bool         $given Whether optional parameters are given.
	 *
	 * @return void
	 */
	#[DataProvider('classes')]
	public function testWiring(string $class, bool $given): void {
		$name = substr($class, (strrpos($class, '\\') + 1)).($given === true ? ' given' : ' defaults');
		$wiring = $this->describe(object: $this->build(class: $class, given: $given), depth: 0);

		$golden = [];
		if (file_exists(self::GOLDEN) === true) {
			$golden = json_decode((string) file_get_contents(self::GOLDEN), true);
		}

		if (getenv('RECORD_GOLDEN') === '1') {
			$golden[$name] = $wiring;
			ksort($golden);
			file_put_contents(self::GOLDEN, json_encode($golden, (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))."\n");
		}

		self::assertSame($golden[$name] ?? null, $wiring);

	}//end testWiring()

	/**
	 * Build a class from labelled doubles, by parameter name.
	 *
	 * @param class-string $class The class.
	 * @param bool         $given Whether optional parameters are given.
	 *
	 * @return object The instance.
	 */
	private function build(string $class, bool $given): object {
		$constructor = (new ReflectionClass($class))->getConstructor();
		$arguments   = [];
		foreach (($constructor?->getParameters() ?? []) as $parameter) {
			if ($parameter->isOptional() === true && $given === false) {
				continue;
			}

			$arguments[$parameter->getName()] = $this->argument(parameter: $parameter, given: $given);
		}

		return new $class(...$arguments);

	}//end build()

	/**
	 * The argument for one parameter.
	 *
	 * @param ReflectionParameter $parameter The parameter.
	 * @param bool                $given     Whether optional parameters are given.
	 *
	 * @return mixed The argument.
	 */
	private function argument(ReflectionParameter $parameter, bool $given): mixed {
		$type = $parameter->getType();
		self::assertInstanceOf(ReflectionNamedType::class, $type, $parameter->getName());
		$typeName = $type->getName();
		$samples  = [
			'string' => 'text:'.$parameter->getName(),
			'int'    => strlen($parameter->getName()),
			'float'  => (strlen($parameter->getName()) / 4),
			'bool'   => true,
			'array'  => ['list:'.$parameter->getName() => [1, 'two']],
		];
		if ($type->isBuiltin() === true) {
			return $samples[$typeName];
		}

		if (str_contains($typeName, '\\Support\\') === true) {
			return $this->build(class: $typeName, given: $given);
		}

		$reflection = new ReflectionClass($typeName);
		$double     = null;
		if ($reflection->isFinal() === true) {
			$double = $reflection->newInstanceWithoutConstructor();
		}

		$double ??= $this->createStub($typeName);

		$this->labels[spl_object_id($double)] = 'double '.$reflection->getShortName();
		return $double;

	}//end argument()

	/**
	 * What an object holds, whatever properties hold it: the sorted list of
	 * every value reachable through its properties. Collaborator bundles
	 * (`...\\Support\\`) are looked through, objects the class built itself are
	 * described with what they hold, two levels deep, and every reference is
	 * listed, so a shared instance shows up once per holder.
	 *
	 * @param object $object The object.
	 * @param int    $depth  How deep this object sits.
	 *
	 * @return array<int, mixed> The descriptions, sorted.
	 */
	private function describe(object $object, int $depth): array {
		$out = [];
		foreach ($this->values(object: $object) as $value) {
			$out[] = $this->value(value: $value, depth: $depth);
		}

		usort($out, static fn ($left, $right): int => strcmp(json_encode($left), json_encode($right)));
		return $out;

	}//end describe()

	/**
	 * The property values of an object, looking through collaborator bundles.
	 *
	 * @param object $object The object.
	 *
	 * @return array<int, mixed> The values.
	 */
	private function values(object $object): array {
		$out   = [];
		$class = new ReflectionClass($object);
		do {
			foreach ($class->getProperties() as $property) {
				if ($property->isStatic() === true || $property->isInitialized($object) === false) {
					continue;
				}

				$value = $property->getValue($object);
				if (is_object($value) === true && str_contains(get_class($value), '\\Support\\') === true) {
					array_push($out, ...$this->values(object: $value));
					continue;
				}

				$out[] = $value;
			}

			$class = $class->getParentClass();
		} while ($class !== false && str_starts_with($class->getName(), 'OCA\\Filinq\\') === true);

		return $out;

	}//end values()

	/**
	 * Describe one value.
	 *
	 * @param mixed $value The value.
	 * @param int   $depth How deep its owner sits.
	 *
	 * @return mixed The description.
	 */
	private function value(mixed $value, int $depth): mixed {
		if (is_object($value) === false) {
			return $value;
		}

		$label = ($this->labels[spl_object_id($value)] ?? null);
		if ($label !== null) {
			return $label;
		}

		$built = 'built '.get_class($value);
		if ($depth < 2 && str_starts_with(get_class($value), 'OCA\\Filinq\\') === true) {
			return [$built => $this->describe(object: $value, depth: ($depth + 1))];
		}

		return $built;

	}//end value()
}//end class
