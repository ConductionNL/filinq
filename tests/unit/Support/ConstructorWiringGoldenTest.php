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
 * pins which double, scalar or self-built object ends up in which property,
 * so the wiring is the same whatever shape the constructor has. Properties
 * that hold a bundle are left out of the record. RECORD_GOLDEN=1 rewrites
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
	 * Every property of an object and what it holds, as a flat map from
	 * property path to description. Objects the class built itself are
	 * followed two levels deep; an object reached again is recorded as the
	 * first path (in sorted order) it was reached by.
	 *
	 * @param object $object The object.
	 * @param int    $depth  How deep this object sits.
	 *
	 * @return array<string, mixed> Property path to description.
	 */
	private function describe(object $object, int $depth): array {
		$pairs = $this->properties(object: $object, prefix: '', depth: $depth);
		ksort($pairs);
		$first = [];
		$out   = [];
		foreach ($pairs as $path => $value) {
			if (is_object($value) === false) {
				$out[$path] = $value;
				continue;
			}

			$id = spl_object_id($value);
			if (isset($first[$id]) === true) {
				$out[$path] = 'same as '.$first[$id];
				continue;
			}

			$first[$id] = $path;
			$out[$path] = ($this->labels[$id] ?? 'built '.get_class($value));
		}

		return $out;

	}//end describe()

	/**
	 * The property values of an object by path, following self-built objects.
	 *
	 * @param object $object The object.
	 * @param string $prefix The path of the object.
	 * @param int    $depth  How deep this object sits.
	 *
	 * @return array<string, mixed> Property path to value.
	 */
	private function properties(object $object, string $prefix, int $depth): array {
		$out   = [];
		$class = new ReflectionClass($object);
		do {
			foreach ($class->getProperties() as $property) {
				if ($property->isStatic() === true || $property->isInitialized($object) === false) {
					continue;
				}

				$value = $property->getValue($object);
				if (is_object($value) === true && str_contains(get_class($value), '\\Support\\') === true) {
					continue;
				}

				$path       = $prefix.$class->getShortName().'::'.$property->getName();
				$out[$path] = $value;
				if ($this->followed(value: $value, depth: $depth) === true) {
					$out += $this->properties(object: $value, prefix: $path.' > ', depth: ($depth + 1));
				}
			}

			$class = $class->getParentClass();
		} while ($class !== false && str_starts_with($class->getName(), 'OCA\\Filinq\\') === true);

		return $out;

	}//end properties()

	/**
	 * Whether a value is an object the class built itself, close enough to follow.
	 *
	 * @param mixed $value The value.
	 * @param int   $depth How deep its owner sits.
	 *
	 * @return bool True to follow it.
	 */
	private function followed(mixed $value, int $depth): bool {
		return is_object($value) === true
			&& $depth < 2
			&& isset($this->labels[spl_object_id($value)]) === false
			&& str_starts_with(get_class($value), 'OCA\\Filinq\\') === true;

	}//end followed()
}//end class
