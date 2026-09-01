<?php

/**
 * Boot proof: the signing wiring loads with the retired approval surface absent.
 *
 * Run standalone (`php tests/scripts/boot-without-openregister.php`), and by
 * `RetiredApprovalSurfaceTest` as a subprocess. The process autoloads
 * filinq's `lib/` (composer) plus the Nextcloud stubs, and deliberately does
 * NOT load `tests/stubs/OpenRegisterStubs.php`: the point of the experiment
 * is a world where no `OCA\OpenRegister\*` class resolves at all — the world
 * an instance is in when openregister#3302 (flow-approval-consolidation) has
 * removed the retired classes, or when OpenRegister is not installed. An
 * in-process PHPUnit test cannot run this experiment, because the unit
 * bootstrap loads the OR stubs for every other test, and a stub that exists
 * is exactly what the proof must exclude.
 *
 * Exit code 0 and the line `BOOT-OK` mean: the retired classes are
 * unresolvable, every signing-surface class links, and
 * `SigningEventRegistrar::register()` completes, registering no retired
 * event name.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Scripts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/migrate-signing-to-or-tasks/tasks.md#4-2
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../stubs/GlobalStubs.php';
require_once __DIR__ . '/../stubs/NextcloudStubs.php';

// The OCP event-dispatcher contracts ship in vendor/nextcloud/ocp but are
// not classmap-autoloaded (same situation as tests/bootstrap-unit.php).
$ocpEventDispatcherDir = __DIR__ . '/../../vendor/nextcloud/ocp/OCP/EventDispatcher';
foreach (['Event.php', 'IEventListener.php', 'IEventDispatcher.php'] as $ocpEventFile) {
	$ocpEventPath = $ocpEventDispatcherDir . '/' . $ocpEventFile;
	if (is_file($ocpEventPath) === true) {
		require_once $ocpEventPath;
	}
}

/**
 * Fail the experiment loudly.
 *
 * @param string $message Why the proof failed.
 *
 * @return never
 */
function bootProofFail(string $message): never {
	fwrite(STDERR, 'BOOT-FAIL: ' . $message . "\n");
	exit(1);
}//end bootProofFail()

// 1. The retired surface must be genuinely unresolvable in this process —
//    this is what "stub-free" means. List per the retirement inventory
//    (openregister tests/fixtures/approval-consolidation/retired-approval-surface.json).
$retired = [
	'OCA\\OpenRegister\\Db\\ApprovalChain',
	'OCA\\OpenRegister\\Db\\ApprovalChainMapper',
	'OCA\\OpenRegister\\Db\\ApprovalStep',
	'OCA\\OpenRegister\\Db\\ApprovalStepMapper',
	'OCA\\OpenRegister\\Service\\ApprovalService',
	'OCA\\OpenRegister\\Controller\\ApprovalController',
	'OCA\\OpenRegister\\Event\\ApprovalStepInitiatedEvent',
	'OCA\\OpenRegister\\Event\\ApprovalStepApprovedEvent',
	'OCA\\OpenRegister\\Event\\ApprovalStepRejectedEvent',
	'OCA\\OpenRegister\\Event\\ApprovalStepCompletedEvent',
];
foreach ($retired as $retiredClass) {
	if (class_exists($retiredClass) === true) {
		bootProofFail('retired class resolves in this process: ' . $retiredClass);
	}
}

// 2. Every signing-surface class must link with the retired classes absent.
//    class_exists(..., true) forces autoload + link, so a signature, parent
//    or interface that needs a missing class fatals here.
$signingSurface = [
	'OCA\\Filinq\\AppInfo\\SigningEventRegistrar',
	'OCA\\Filinq\\EventListener\\SigningTaskListener',
	'OCA\\Filinq\\EventListener\\SignerEventTranslator',
	'OCA\\Filinq\\Event\\SignerStepPendingEvent',
	'OCA\\Filinq\\Event\\SignerStepApprovedEvent',
	'OCA\\Filinq\\Event\\SignerStepRejectedEvent',
	'OCA\\Filinq\\Event\\SignerChainCompletedEvent',
];
foreach ($signingSurface as $surfaceClass) {
	if (class_exists($surfaceClass) === false) {
		bootProofFail('signing-surface class does not link: ' . $surfaceClass);
	}
}

// 3. register() must complete against a minimal context, and register no
//    retired event name.
$context = new class implements \OCP\AppFramework\Bootstrap\IRegistrationContext {
	/**
	 * The event names listeners were registered for.
	 *
	 * @var array<int, string>
	 */
	public array $events = [];

	public function registerService(string $name, callable $factory, bool $shared = true): void {
	}

	public function registerAlias(string $alias, string $target): void {
	}

	public function registerServiceAlias(string $alias, string $target): void {
	}

	public function registerParameter(string $name, mixed $value): void {
	}

	public function registerEventListener(string $event, string $listener, int $priority = 0): void {
		$this->events[] = $event;
	}
};

(new \OCA\Filinq\AppInfo\SigningEventRegistrar())->register(context: $context);

$expected = [
	'OCA\\OpenRegister\\Event\\TaskTransitionedEvent',
	'OCA\\OpenRegister\\Event\\TaskTerminalEvent',
	'OCA\\OpenRegister\\Event\\TaskSequenceCompletedEvent',
	'OCA\\Filinq\\Event\\DocumentSigningRequestedEvent',
];
if ($context->events !== $expected) {
	bootProofFail('unexpected registrations: ' . implode(', ', $context->events));
}

foreach ($context->events as $registeredEvent) {
	if (str_contains($registeredEvent, 'Approval') === true) {
		bootProofFail('a retired event name was registered: ' . $registeredEvent);
	}
}

echo 'BOOT-OK: signing wiring loads with the retired approval surface absent; registered: '
	. implode(', ', $context->events) . "\n";
exit(0);
