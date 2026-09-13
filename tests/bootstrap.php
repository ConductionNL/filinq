<?php

/**
 * Bootstrap file for PHPUnit tests
 *
 * @category Test
 * @package  OCA\Filinq\Tests
 *
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://filinq.app
 */

declare(strict_types=1);

// Define that we're running PHPUnit.
define('PHPUNIT_RUN', 1);

// Include Composer's autoloader.
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Tell whether a Nextcloud root is an INSTALLED instance, not just a source tree.
 *
 * `lib/base.php` from a source tree that was never installed (the workspace
 * checkout above apps-extra/ has a 0-byte config/config.php) still declares
 * `OC` and builds `\OC::$server` before it throws "Not installed". That server
 * cannot be undone (`OC::$server` is a typed static), so from then on every
 * `\OC::$server->get()` in the code under test hits a container that knows
 * none of this app's registrations and autowires from scratch; constructor
 * cycles then recurse until memory runs out (19 GB and 6 GB of swap in one
 * openregister run on 2026-09-08). So the decision has to be made BEFORE
 * base.php is loaded, and the only cheap signal is the `installed` flag in
 * config/config.php.
 *
 * @param string $ncRoot Candidate Nextcloud root.
 *
 * @return bool True when config/config.php declares `installed => true`.
 */
function filinq_nc_root_is_installed(string $ncRoot): bool
{
	$configFile = $ncRoot . '/config/config.php';
	if (is_file($configFile) === false || filesize($configFile) === 0) {
		return false;
	}

	// The config file is a plain `$CONFIG = [...]` script; including it in a
	// closure keeps `$CONFIG` out of the global scope.
	$config = (static function () use ($configFile): array {
		$CONFIG = [];
		try {
			include $configFile;
		} catch (\Throwable) {
			return [];
		}

		if (is_array($CONFIG) === false) {
			return [];
		}

		return $CONFIG;
	})();

	return ($config['installed'] ?? false) === true;
}//end filinq_nc_root_is_installed()

// Bootstrap Nextcloud if not already done. Only an INSTALLED root is booted;
// a bare source tree runs in pure-unit mode with the composer autoload only.
// NC's tests/autoload.php requires lib/base.php itself, so it sits behind the
// same guard, and so do the OC_App calls that need a booted server.
if (!defined('OC_CONSOLE')) {
	$filinqNcRoot = realpath(__DIR__ . '/../../..');
	if ($filinqNcRoot !== false && file_exists($filinqNcRoot . '/lib/base.php') === true) {
		if (filinq_nc_root_is_installed($filinqNcRoot) === true) {
			try {
				require_once $filinqNcRoot . '/lib/base.php';

				// Load Test\TestCase and other NC test classes (NC convention).
				if (file_exists($filinqNcRoot . '/tests/autoload.php') === true) {
					require_once $filinqNcRoot . '/tests/autoload.php';
				}

				// Load all enabled apps, then our specific app, then clear hooks
				// for testing. These need a booted server, so they belong inside the
				// same try: when base.php fails part-way there is no OC_App to call,
				// and the warning below is the whole answer.
				\OC_App::loadApps();
				\OC_App::loadApp('filinq');
				OC_Hook::clear();
			} catch (\Throwable $e) {
				// The tree IS installed, so the dangerous case this guard exists for
				// (loading a bare source tree) did not happen. base.php still failed
				// part-way.
				//
				// This does NOT abort. `OC::$server` is a typed static, so a half-built
				// container cannot be unset, and aborting was tried: it turned all six
				// PHPUnit legs red on a suite that passes (humaniq, 2026-09-08). The
				// runaway this guard exists for needs an autowiring lookup to reach the
				// poisoned container, this app has none in lib, and phpunit.xml's 2G cap
				// bounds one anyway.
				//
				// So: say plainly that the container is unreliable, and let the pure unit
				// tests run. A container-bound test failing loudly is the intended outcome.
				fwrite(
					STDERR,
					sprintf(
						"[filinq/tests/bootstrap] Nextcloud at %s could not finish booting (%s).\n"
						. "  \\OC::\$server now holds a HALF-BUILT container and cannot be unset. Pure unit tests\n"
						. "  continue; anything resolving a service from that container is UNVERIFIED by this run.\n",
						$filinqNcRoot,
						$e->getMessage()
					)
				);
			}
		} else {
			fwrite(
				STDERR,
				sprintf(
					"[filinq/tests/bootstrap] Nextcloud root at %s is not an installed instance (config/config.php lacks installed => true); "
					. "skipping lib/base.php and running with composer autoload only (pure-unit mode).\n",
					$filinqNcRoot
				)
			);
		}
	}

	unset($filinqNcRoot);
}
