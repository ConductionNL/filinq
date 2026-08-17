<?php

/**
 * Conformance test for ADR-087 §5 — no leaf app may depend on one office suite.
 *
 * openspec/changes/office-suite-portability.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 *
 * @category Test
 * @package  OCA\DocuDesk\Tests\Unit\Service\Office
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git-id>
 *
 * @link https://docudesk.app
 */

declare(strict_types=1);

namespace OCA\DocuDesk\Tests\Unit\Service\Office;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Keeps ADR-087 §5 true rather than merely observing that it is.
 *
 * At the time of writing, `richdocuments` appears under `lib/` only inside
 * explanatory comments in `EditSessionService`. That is a fact about HEAD, not a
 * constraint — nothing stops the next change from reintroducing a hard dependency,
 * and the failure would stay invisible until a deployment without that suite.
 *
 * The check MUST ignore comments. `EditSessionService` names `richdocuments`
 * repeatedly while explaining why DocuDesk does NOT depend on it — the WOPI lock
 * reasoning is genuinely non-obvious and worth the prose. A naive
 * `grep richdocuments lib/` fails against correct code, and the natural way to
 * "fix" that failure is to delete the explanation, which makes the codebase worse.
 */
class SuiteIndependenceTest extends TestCase {

	/**
	 * App ids that would constitute a per-suite dependency.
	 *
	 * @var string[]
	 */
	private const SUITE_APP_IDS = ['richdocuments', 'onlyoffice', 'documentserver'];

	/**
	 * Directories scanned for a suite dependency.
	 *
	 * @var string[]
	 */
	private const SCANNED_DIRS = ['lib', 'src'];

	/**
	 * REQ: no suite app id appears in executable code.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testNoSuiteAppIdAppearsInExecutableCode(): void {
		$offenders = [];

		foreach (self::SCANNED_DIRS as $dir) {
			foreach ($this->sourceFiles(dir: $dir) as $file) {
				$offenders = array_merge($offenders, $this->scan(file: $file));
			}
		}

		$this->assertSame(
			[],
			$offenders,
			"ADR-087 §5: a leaf app must not depend on one office suite's app id.\n"
			. "Every capability must be reachable through the suite-independent path\n"
			. "(IConversionManager for conversion, in-package XML for manipulation).\n"
			. "Offending lines:\n  " . implode("\n  ", $offenders)
		);
	}//end testNoSuiteAppIdAppearsInExecutableCode()

	/**
	 * REQ: the same identifier inside a comment is NOT a dependency.
	 *
	 * This is the test's own positive control. `EditSessionService` documents at
	 * length why the WOPI lock cannot be relied on, naming `richdocuments` several
	 * times. If the scanner cannot tell that from a dependency, it is measuring the
	 * wrong thing — and the pressure it creates is to delete the explanation.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testCommentaryNamingASuiteIsNotADependency(): void {
		$path = $this->repoRoot() . '/lib/Service/Editing/EditSessionService.php';
		$this->assertFileExists($path, 'the file this control depends on has moved');

		$contents = (string)file_get_contents($path);
		$this->assertStringContainsString(
			'richdocuments',
			$contents,
			'this control is only meaningful while the file still discusses richdocuments'
		);

		$this->assertSame(
			[],
			$this->scan(file: new SplFileInfo($path)),
			'commentary explaining why a suite is NOT depended upon must not be flagged'
		);
	}//end testCommentaryNamingASuiteIsNotADependency()

	/**
	 * REQ: the scanner CAN fail — proven on a synthetic offender.
	 *
	 * Without this, a scanner that silently matched nothing would report the same
	 * green as a codebase that is genuinely clean. Every other assertion here is
	 * worthless unless this one passes.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testScannerDetectsAnIntroducedDependency(): void {
		$tmp = tempnam(sys_get_temp_dir(), 'suite') . '.php';
		file_put_contents(
			$tmp,
			"<?php\n// a comment naming richdocuments must NOT trip this\n"
			. "\$app = 'richdocuments';\n"
		);

		$offenders = $this->scan(file: new SplFileInfo($tmp));
		unlink($tmp);

		$this->assertCount(1, $offenders, 'the scanner must flag the executable line and only that line');
		$this->assertStringContainsString(':3', $offenders[0], 'it must name the offending line number');
	}//end testScannerDetectsAnIntroducedDependency()

	/**
	 * REQ: prose is skipped, but a bare identifier on a wordy line is still caught.
	 *
	 * The second half is the part that matters. Suppressing whole lines that "look
	 * like a sentence" would let `if (app === 'onlyoffice' && a && b)` through —
	 * four words, and a real dependency. Judging each quoted segment separately is
	 * what keeps the exemption narrow.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testProseIsExemptButIdentifiersOnWordyLinesAreNot(): void {
		$tmp = tempnam(sys_get_temp_dir(), 'suite') . '.js';
		file_put_contents(
			$tmp,
			"const help = 'Conversion requires an Office app such as OnlyOffice or Collabora'\n"
			. "if (appId === 'onlyoffice' && enabled && ready && loaded) { go() }\n"
		);

		$offenders = $this->scan(file: new SplFileInfo($tmp));
		unlink($tmp);

		$this->assertCount(1, $offenders, 'exactly the dependency, not the help text');
		$this->assertStringContainsString(':2', $offenders[0], 'the identifier line, not the prose line');
	}//end testProseIsExemptButIdentifiersOnWordyLinesAreNot()

	/**
	 * REQ: the suite-registry exemption is narrow and must be declared.
	 *
	 * Without a control, an exemption becomes the thing everyone reaches for. This
	 * asserts that the marker is required — a file naming a suite app id without
	 * declaring itself a registry is still flagged.
	 *
	 * @return void
	 *
	 * @spec openspec/specs/office-suite-portability/spec.md
	 */
	public function testTheSuiteRegistryExemptionMustBeDeclared(): void {
		$withoutMarker = tempnam(sys_get_temp_dir(), 'reg') . '.php';
		file_put_contents($withoutMarker, "<?php\n\$app = 'onlyoffice';\n");

		$withMarker = tempnam(sys_get_temp_dir(), 'reg') . '.php';
		file_put_contents(
			$withMarker,
			"<?php\n/** @suite-registry enumerates suites to probe each separately */\n\$app = 'onlyoffice';\n"
		);

		$flagged = $this->scan(file: new SplFileInfo($withoutMarker));
		$exempt  = $this->scan(file: new SplFileInfo($withMarker));

		unlink($withoutMarker);
		unlink($withMarker);

		$this->assertCount(1, $flagged, 'a file without the marker must still be flagged');
		$this->assertSame([], $exempt, 'a declared suite registry is exempt');
	}//end testTheSuiteRegistryExemptionMustBeDeclared()

	/**
	 * Scan one file for suite app ids in executable code.
	 *
	 * Uses PHP's own tokeniser rather than a regex, so a comment is identified the
	 * way the language identifies one. For non-PHP files (Vue, JS) the fallback
	 * strips `//` and block comments before matching.
	 *
	 * @param SplFileInfo $file The file to scan.
	 *
	 * @return string[] Offending "path:line" entries.
	 */
	private function scan(SplFileInfo $file): array {
		$contents = (string)file_get_contents($file->getPathname());
		$path     = $file->getPathname();

		// A file may declare itself a SUITE REGISTRY: something whose job is to
		// enumerate suites, such as the per-suite probe command. Naming suites is
		// what it is for.
		//
		// The exemption is deliberately narrow and must be DECLARED in the file,
		// with a reason, so it cannot be acquired by accident. What it permits is
		// enumeration; what it must never permit is a CAPABILITY that only works
		// when a named suite is present, which is what ADR-087 §5 bans.
		//
		// Keeping the marker in-file also means the exempt set is greppable:
		//   grep -rn '@suite-registry' lib/ src/
		if (str_contains($contents, '@suite-registry') === true) {
			return [];
		}

		if ($file->getExtension() === 'php') {
			return $this->scanPhp(contents: $contents, path: $path);
		}

		return $this->scanPlain(contents: $contents, path: $path);
	}//end scan()

	/**
	 * Scan PHP source using the tokeniser.
	 *
	 * @param string $contents The file contents.
	 * @param string $path     The file path.
	 *
	 * @return string[] Offending "path:line" entries.
	 */
	private function scanPhp(string $contents, string $path): array {
		$offenders = [];

		foreach (token_get_all($contents) as $token) {
			if (is_array($token) === false) {
				continue;
			}

			[$id, $text, $line] = $token;

			// Comments and docblocks are explanation, not dependency.
			if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
				continue;
			}

			// Prose is not a dependency either. See isProse().
			if ($this->isProse(text: $text) === true) {
				continue;
			}

			foreach (self::SUITE_APP_IDS as $appId) {
				if (stripos($text, $appId) !== false) {
					$offenders[] = sprintf('%s:%d (%s)', $path, $line, $appId);
					break;
				}
			}
		}

		return $offenders;
	}//end scanPhp()

	/**
	 * Split a line into its quoted segments plus the residue outside them.
	 *
	 * @param string $line The comment-stripped line.
	 *
	 * @return string[] The fragments to judge independently.
	 */
	private function fragments(string $line): array {
		$fragments = [];

		if (preg_match_all('/([\'"`])(.*?)\1/s', $line, $matches) > 0) {
			foreach ($matches[2] as $quoted) {
				$fragments[] = $quoted;
			}
		}

		// Whatever is left once the quoted parts are removed: imports, bare
		// identifiers, property access.
		$fragments[] = preg_replace('/([\'"`])(.*?)\1/s', ' ', $line);

		return $fragments;
	}//end fragments()

	/**
	 * Decide whether a fragment is human-readable prose rather than an identifier.
	 *
	 * The discriminator: **an app id used as a dependency is a bare token; an app
	 * id inside a sentence is documentation.** `'onlyoffice'` is a dependency;
	 * "…a supported Office app integration (Collabora, OnlyOffice, or Euro Office)…"
	 * is a sentence telling an administrator which suites give the best fidelity.
	 *
	 * This distinction was not in the first version of this test, and the test
	 * promptly failed on `src/views/settings/Settings.vue:142` — a translated
	 * `t()` label. Reported as a violation it would have been wrong twice over:
	 * the code is correct, and the only way to satisfy the test would have been to
	 * delete accurate user-facing help. A check that pressures you to remove true
	 * documentation is measuring the wrong thing.
	 *
	 * @param string $text The token or fragment.
	 *
	 * @return bool True when the fragment reads as prose.
	 */
	private function isProse(string $text): bool {
		$inner = trim($text, "'\"`");

		// A sentence has spaces; an app id does not. Four or more whitespace-
		// separated words is comfortably past any plausible identifier.
		return (preg_match('/\S+\s+\S+\s+\S+\s+\S+/', $inner) === 1);
	}//end isProse()

	/**
	 * Scan a non-PHP source file, stripping comments first.
	 *
	 * @param string $contents The file contents.
	 * @param string $path     The file path.
	 *
	 * @return string[] Offending "path:line" entries.
	 */
	private function scanPlain(string $contents, string $path): array {
		$offenders = [];
		$lines     = explode("\n", $contents);
		$inBlock   = false;

		foreach ($lines as $index => $line) {
			$stripped = $line;

			if ($inBlock === true) {
				$end = strpos($stripped, '*/');
				if ($end === false) {
					continue;
				}

				$stripped = substr($stripped, ($end + 2));
				$inBlock  = false;
			}

			$blockStart = strpos($stripped, '/*');
			if ($blockStart !== false) {
				$stripped = substr($stripped, 0, $blockStart);
				$inBlock  = true;
			}

			$lineComment = strpos($stripped, '//');
			if ($lineComment !== false) {
				$stripped = substr($stripped, 0, $lineComment);
			}

			$htmlComment = strpos($stripped, '<!--');
			if ($htmlComment !== false) {
				$stripped = substr($stripped, 0, $htmlComment);
			}

			// Test each quoted segment separately, plus whatever sits outside
			// quotes. Judging the WHOLE line as prose would suppress a genuine
			// `if (app === 'onlyoffice' && a && b)` — four words, but a real
			// dependency.
			foreach ($this->fragments(line: $stripped) as $fragment) {
				if ($this->isProse(text: $fragment) === true) {
					continue;
				}

				foreach (self::SUITE_APP_IDS as $appId) {
					if (stripos($fragment, $appId) !== false) {
						$offenders[] = sprintf('%s:%d (%s)', $path, ($index + 1), $appId);
						break 2;
					}
				}
			}
		}

		return $offenders;
	}//end scanPlain()

	/**
	 * Iterate the source files of a directory.
	 *
	 * @param string $dir The directory, relative to the repo root.
	 *
	 * @return SplFileInfo[] The source files.
	 */
	private function sourceFiles(string $dir): array {
		$root = $this->repoRoot() . '/' . $dir;
		if (is_dir($root) === false) {
			return [];
		}

		$wanted = ['php', 'vue', 'js', 'ts'];
		$files  = [];

		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
		foreach ($iterator as $file) {
			if ($file instanceof SplFileInfo
				&& $file->isFile() === true
				&& in_array(strtolower($file->getExtension()), $wanted, true) === true
			) {
				$files[] = $file;
			}
		}

		return $files;
	}//end sourceFiles()

	/**
	 * Resolve the repository root.
	 *
	 * @return string The absolute repo root.
	 */
	private function repoRoot(): string {
		return dirname(__DIR__, 4);
	}//end repoRoot()
}//end class
