<?php

/**
 * Test doubles that write every call they answer into one ordered trace.
 *
 * @category Test
 * @package  OCA\Filinq\Tests\Unit\Support
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Support;

use Closure;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * For TestCase classes. `recorder()` builds a double whose listed methods log
 * `Label::method` with their arguments by parameter name (an argument equal to
 * its parameter's default is left out, so the trace is the same whichever
 * signature of an optional dependency is loaded), then answer with
 * the given value or closure. Times are written as `<time>`, doubles by their
 * label and other objects by class and public properties, so the trace does
 * not depend on the clock or on object ids. `assertTrace()` compares one case
 * with a JSON golden; GOLDEN_RECORD=1 rewrites that case.
 */
trait RecordsCalls {

	/**
	 * The calls the doubles answered, in order.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $trace = [];

	/**
	 * Labels of the doubles, by object id.
	 *
	 * @var array<int, string>
	 */
	private array $doubleLabels = [];

	/**
	 * A double of $class that records the methods in $answers.
	 *
	 * @param class-string         $class   The class or interface.
	 * @param array<string, mixed> $answers Method name to return value; a Closure is called with the arguments.
	 *
	 * @return MockObject The double.
	 */
	private function recorder(string $class, array $answers): MockObject {
		$label  = substr($class, (strrpos($class, '\\') + 1));
		$double = $this->createMock($class);
		if (interface_exists($class) === false) {
			$double = $this->getMockBuilder($class)->disableOriginalConstructor()->onlyMethods(array_keys($answers))->getMock();
		}

		$this->doubleLabels[spl_object_id($double)] = 'double '.$label;
		foreach ($answers as $method => $answer) {
			$parameters = (new ReflectionMethod($class, $method))->getParameters();
			$double->method($method)->willReturnCallback(
				function (mixed ...$arguments) use ($label, $method, $parameters, $answer): mixed {
					$named = [];
					foreach ($arguments as $index => $argument) {
						$parameter = ($parameters[$index] ?? null);
						if ($parameter?->isDefaultValueAvailable() === true && $parameter->getDefaultValue() === $argument) {
							continue;
						}

						$named[($parameter?->getName() ?? (string) $index)] = $this->traced(value: $argument);
					}

					$this->trace[] = [$label.'::'.$method => $named];
					if ($answer instanceof Closure) {
						return $answer(...$arguments);
					}

					return $answer;
				}
			);
		}//end foreach

		return $double;

	}//end recorder()

	/**
	 * Run $call and add its result, or the exception it threw, to the trace.
	 *
	 * @param Closure $call The call.
	 *
	 * @return void
	 */
	private function traceCall(Closure $call): void {
		try {
			$this->trace[] = ['result' => $this->traced(value: $call())];
		} catch (Throwable $e) {
			$this->trace[] = ['threw' => [get_class($e), $e->getMessage(), $e->getCode()]];
		}

	}//end traceCall()

	/**
	 * A value as it is written into the trace.
	 *
	 * @param mixed $value The value.
	 *
	 * @return mixed The traced form.
	 */
	private function traced(mixed $value): mixed {
		if ($value instanceof DateTimeInterface) {
			return '<time>';
		}

		if (is_string($value) === true && preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $value) === 1) {
			return '<time>';
		}

		if (is_array($value) === true) {
			return array_map(fn (mixed $item): mixed => $this->traced(value: $item), $value);
		}

		if (is_object($value) === false) {
			return $value;
		}

		$label = ($this->doubleLabels[spl_object_id($value)] ?? null);
		if ($label !== null) {
			return $label;
		}

		if ($value instanceof MockObject) {
			return 'stub '.get_parent_class($value);
		}

		$class = get_class($value);
		if ((new ReflectionClass($value))->isAnonymous() === true) {
			$class = 'object';
		}

		return [$class => $this->traced(value: get_object_vars($value))];

	}//end traced()

	/**
	 * Compare the trace with case $name of the golden file.
	 *
	 * @param string $file The golden file.
	 * @param string $name The case.
	 *
	 * @return void
	 */
	private function assertTrace(string $file, string $name): void {
		$golden = [];
		if (is_file($file) === true) {
			$golden = json_decode((string) file_get_contents($file), true);
		}

		if (getenv('GOLDEN_RECORD') === '1') {
			$golden[$name] = $this->trace;
			ksort($golden);
			file_put_contents($file, json_encode($golden, (JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))."\n");
		}

		self::assertSame(($golden[$name] ?? null), $this->trace);

	}//end assertTrace()
}//end trait
