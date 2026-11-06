<?php

/**
 * Every background job can be built by the container.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\AppInfo
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-10-09-case-documents-and-the-flat-list/specs/document-register/spec.md
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\AppInfo;

use OCA\Filinq\AppInfo\RegistrationBootstrap;
use OCA\Filinq\BackgroundJob\DomainFolderReconciliationJob;
use OCA\Filinq\Service\DomainFolderGateway;
use OCA\Filinq\Service\NextcloudDomainFolderGateway;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Nextcloud autowires concrete classes but not interfaces or abstract classes.
 * A job whose dependency graph reaches an unbound app interface cannot be
 * constructed, and cron.php dies on it for every app on the instance.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\AppInfo
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */
class BackgroundJobWiringTest extends TestCase {

	/**
	 * The domain folder gateway is bound to its Nextcloud implementation.
	 *
	 * @return void
	 */
	public function testTheDomainFolderGatewayIsBound(): void {
		$bindings = $this->bindings();

		$this->assertSame(NextcloudDomainFolderGateway::class, $bindings[DomainFolderGateway::class] ?? null);
		$this->assertTrue(is_subclass_of(NextcloudDomainFolderGateway::class, DomainFolderGateway::class));

	}//end testTheDomainFolderGatewayIsBound()

	/**
	 * The job list covers the jobs declared in info.xml and the job directories.
	 *
	 * @return void
	 */
	public function testTheReconciliationJobIsAmongTheCheckedJobs(): void {
		$this->assertContains(DomainFolderReconciliationJob::class, $this->jobClasses());

	}//end testTheReconciliationJobIsAmongTheCheckedJobs()

	/**
	 * Every app interface or abstract class a job's constructor reaches is bound.
	 *
	 * @return void
	 */
	public function testEveryJobDependencyCanBeResolved(): void {
		$bindings   = $this->bindings();
		$unresolved = [];

		foreach ($this->jobClasses() as $job) {
			$seen = [];
			$this->collectUnresolved(class: $job, bindings: $bindings, seen: $seen, unresolved: $unresolved, trail: $job);
		}

		$this->assertSame([], $unresolved, 'Unbound dependencies: ' . implode("\n", $unresolved));

	}//end testEveryJobDependencyCanBeResolved()

	/**
	 * Walk a class's constructor and record every dependency the container cannot build.
	 *
	 * @param string                $class      The class to construct.
	 * @param array<string, string> $bindings   Bound name to target.
	 * @param array<string, bool>   $seen       Classes already walked.
	 * @param array<int, string>    $unresolved Collected failures.
	 * @param string                $trail      How the walk reached this class.
	 *
	 * @return void
	 */
	private function collectUnresolved(string $class, array $bindings, array &$seen, array &$unresolved, string $trail): void {
		if (isset($seen[$class]) === true) {
			return;
		}

		$seen[$class] = true;
		$constructor  = (new ReflectionClass($class))->getConstructor();
		if ($constructor === null) {
			return;
		}

		foreach ($constructor->getParameters() as $parameter) {
			$type = $parameter->getType();
			if ($parameter->isDefaultValueAvailable() === true
				|| ($type instanceof ReflectionNamedType) === false
				|| $type->isBuiltin() === true
			) {
				continue;
			}

			$name = $type->getName();
			if (str_starts_with($name, 'OCA\\') === false) {
				continue;
			}

			$path = $trail . ' -> ' . $name;
			if (isset($bindings[$name]) === true) {
				if ($bindings[$name] !== '' && class_exists($bindings[$name]) === true) {
					$this->collectUnresolved(class: $bindings[$name], bindings: $bindings, seen: $seen, unresolved: $unresolved, trail: $path);
				}

				continue;
			}

			if (class_exists($name) === false && interface_exists($name) === false) {
				// Another app's class this test environment cannot load; that
				// app's own container builds it.
				continue;
			}

			$reflection = new ReflectionClass($name);
			if ($reflection->isInterface() === true || $reflection->isAbstract() === true) {
				$unresolved[] = $path;
				continue;
			}

			$this->collectUnresolved(class: $name, bindings: $bindings, seen: $seen, unresolved: $unresolved, trail: $path);
		}

	}//end collectUnresolved()

	/**
	 * Every background job class: declared in info.xml or living in a job directory.
	 *
	 * @return array<int, string> The class names.
	 */
	private function jobClasses(): array {
		$root = dirname(__DIR__, 3);
		$info = simplexml_load_string((string)file_get_contents($root . '/appinfo/info.xml'));
		$jobs = [];
		foreach ($info->{'background-jobs'}->job ?? [] as $job) {
			$jobs[] = trim((string)$job);
		}

		foreach (['BackgroundJob', 'Cron'] as $directory) {
			foreach (glob($root . '/lib/' . $directory . '/*.php') ?: [] as $file) {
				$class = 'OCA\\Filinq\\' . $directory . '\\' . basename($file, '.php');
				if (class_exists($class) === true && (new ReflectionClass($class))->isInstantiable() === true) {
					$jobs[] = $class;
				}
			}
		}

		return array_values(array_unique($jobs));

	}//end jobClasses()

	/**
	 * What the bootstrap binds: aliases to their target, factories to ''.
	 *
	 * @return array<string, string> Bound name to target.
	 */
	private function bindings(): array {
		$context = new class implements IRegistrationContext {
			/**
			 * Bound name to target.
			 *
			 * @var array<string, string>
			 */
			public array $bound = [];

			public function registerService(string $name, callable $factory, bool $shared = true): void {
				unset($factory, $shared);
				$this->bound[$name] = '';
			}

			public function registerAlias(string $alias, string $target): void {
				$this->bound[$alias] = $target;
			}

			public function registerServiceAlias(string $alias, string $target): void {
				$this->bound[$alias] = $target;
			}

			public function registerParameter(string $name, mixed $value): void {
				unset($value);
				$this->bound[$name] = '';
			}

			public function registerEventListener(string $event, string $listener, int $priority = 0): void {
				unset($event, $listener, $priority);
			}

			/**
			 * Every other registration member, ignored.
			 *
			 * @param string            $name      The member.
			 * @param array<int, mixed> $arguments Its arguments.
			 *
			 * @return void
			 */
			public function __call(string $name, array $arguments): void {
				unset($name, $arguments);
			}
		};

		(new RegistrationBootstrap())->register(context: $context);

		return $context->bound;

	}//end bindings()
}//end class
