<?php

/**
 * SVG Rasterizer
 *
 * LibreOffice's HTML import drops inline SVG, so a Twig template with a
 * chart would reach an ODT or DOCX file without it. This service turns every
 * inline SVG in the rendered HTML into an embedded PNG first, locally, with
 * the same soffice binary and the same host-wide lock the PDF conversion
 * backend uses.
 *
 * @category  Service
 * @package   OCA\Filinq\Service\Charts
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 */

declare(strict_types=1);

namespace OCA\Filinq\Service\Charts;

use FilesystemIterator;
use OCA\Filinq\Exception\ConversionFailedException;
use OCA\Filinq\Service\Conversion\SofficeProcessRunner;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Replaces inline SVG with PNG before an HTML to ODF or DOCX conversion.
 *
 * @category Service
 * @package  OCA\Filinq\Service\Charts
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
 */
class SvgRasterizer {

	/**
	 * The lock LibreOfficeHeadlessBackend holds: one soffice per host.
	 *
	 * @var string
	 */
	private const LOCK_KEY = 'soffice:headless:convert';

	/**
	 * App-config key for the soffice binary, shared with the PDF backend.
	 *
	 * @var string
	 */
	private const BINARY_KEY = 'filinq.conversion.libreoffice_binary_path';

	/**
	 * App-config key for the conversion timeout, shared with the PDF backend.
	 *
	 * @var string
	 */
	private const TIMEOUT_KEY = 'filinq.conversion.timeout_seconds';

	/**
	 * One inline SVG element. Chart SVG never nests, so the lazy match is exact.
	 *
	 * @var string
	 */
	private const SVG_PATTERN = '#<svg\b[^>]*>.*?</svg>#s';

	/**
	 * Constructor.
	 *
	 * @param IAppConfig           $appConfig Binary path and timeout.
	 * @param ILockingProvider     $locking   Serialises soffice with the PDF backend.
	 * @param LoggerInterface      $logger    Logs failed conversions.
	 * @param SofficeProcessRunner $runner    Runs soffice without a shell.
	 * @param IL10N|null           $l10n      Translates the marker.
	 *
	 * @return void
	 */
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly ILockingProvider $locking,
		private readonly LoggerInterface $logger,
		private readonly SofficeProcessRunner $runner,
		private readonly ?IL10N $l10n = null,
	) {
	}//end __construct()

	/**
	 * Replace every inline SVG in the HTML with an embedded PNG.
	 *
	 * 🔴 NO CHART LEAVES WITHOUT A TRACE. A chart soffice could not convert,
	 * or could not convert because another conversion held the lock, becomes a
	 * visible marker and a warning that names the format. Leaving the SVG in
	 * place would let LibreOffice drop it without a word, which is the silent
	 * gap REQ-DDTCH-007 forbids.
	 *
	 * @param string $html   The rendered HTML.
	 * @param string $format The target format, named in the warning (odf, docx).
	 *
	 * @return array{html: string, warnings: string[]} The HTML to convert and any warnings.
	 *
	 * @spec openspec/specs/template-charts/spec.md#REQ-DDTCH-007
	 */
	public function rasterizeInlineSvg(string $html, string $format): array {
		if (preg_match(self::SVG_PATTERN, $html) !== 1) {
			return ['html' => $html, 'warnings' => []];
		}

		$pass = new ChartPass(locked: $this->acquireConverter(), format: $format);
		try {
			$result = $this->replaceCharts(html: $html, pass: $pass);
		} finally {
			$this->releaseConverter(pass: $pass);
		}

		return ['html' => ($result ?? $html), 'warnings' => $pass->warnings];

	}//end rasterizeInlineSvg()

	/**
	 * Replace every inline SVG in the HTML, in document order.
	 *
	 * @param string    $html The rendered HTML.
	 * @param ChartPass $pass The pass.
	 *
	 * @return string|null The HTML with the charts replaced, or null when the pattern failed.
	 */
	private function replaceCharts(string $html, ChartPass $pass): ?string {
		return preg_replace_callback(
			self::SVG_PATTERN,
			fn (array $match): string => $this->replaceChart(svg: $match[0], pass: $pass),
			$html
		);

	}//end replaceCharts()

	/**
	 * Take the host-wide soffice lock, or log that soffice is busy.
	 *
	 * @return bool Whether the lock was taken.
	 */
	private function acquireConverter(): bool {
		try {
			$this->locking->acquireLock(self::LOCK_KEY, ILockingProvider::LOCK_EXCLUSIVE);
			return true;
		} catch (LockedException $e) {
			$this->logger->warning('[SvgRasterizer] soffice is busy, charts are not converted', ['exception' => $e]);
			return false;
		}

	}//end acquireConverter()

	/**
	 * Release the soffice lock when this pass took it.
	 *
	 * @param ChartPass $pass The pass.
	 *
	 * @return void
	 */
	private function releaseConverter(ChartPass $pass): void {
		if ($pass->locked === true) {
			$this->locking->releaseLock(self::LOCK_KEY, ILockingProvider::LOCK_EXCLUSIVE);
		}

	}//end releaseConverter()

	/**
	 * What takes one chart's place: its PNG, or a marker with a warning.
	 *
	 * @param string    $svg  The SVG markup.
	 * @param ChartPass $pass The pass.
	 *
	 * @return string The replacement markup.
	 */
	private function replaceChart(string $svg, ChartPass $pass): string {
		$png = null;
		if ($pass->locked === true) {
			$png = $this->toPng(svg: $svg);
		}

		if ($png === null) {
			return $this->marker(pass: $pass);
		}

		return $this->imageTag(svg: $svg, png: $png);

	}//end replaceChart()

	/**
	 * The visible marker of a chart that was not converted; its warning is added to the pass.
	 *
	 * @param ChartPass $pass The pass.
	 *
	 * @return string The marker markup.
	 */
	private function marker(ChartPass $pass): string {
		$message = $this->markerText(format: $pass->format);
		$pass->warnings[] = $message;
		return '<span>[' . htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8') . ']</span>';

	}//end marker()

	/**
	 * Convert one SVG to PNG bytes with soffice.
	 *
	 * @param string $svg The SVG markup.
	 *
	 * @return string|null The PNG bytes, or null when soffice produced none.
	 */
	private function toPng(string $svg): ?string {
		$tmpDir = $this->workDirFor(svg: $svg);

		try {
			return $this->convertIn(tmpDir: $tmpDir);
		} catch (ConversionFailedException $e) {
			$this->logger->warning('[SvgRasterizer] soffice failed on a chart', ['exception' => $e]);
			return null;
		} finally {
			$this->removeDir(dir: $tmpDir);
		}

	}//end toPng()

	/**
	 * A fresh private work directory holding the SVG as `chart.svg`.
	 *
	 * @param string $svg The SVG markup.
	 *
	 * @return string The directory.
	 */
	private function workDirFor(string $svg): string {
		$tmpDir = sys_get_temp_dir() . '/filinq_svg_' . bin2hex(random_bytes(8));
		mkdir($tmpDir, 0700, true);
		file_put_contents($tmpDir . '/chart.svg', $svg);

		return $tmpDir;

	}//end workDirFor()

	/**
	 * Run soffice on the work directory's SVG and read the PNG it wrote.
	 *
	 * @param string $tmpDir The work directory.
	 *
	 * @return string|null The PNG bytes, or null when soffice produced none.
	 *
	 * @throws ConversionFailedException When soffice fails or times out.
	 */
	private function convertIn(string $tmpDir): ?string {
		$exit = $this->runner->run(
			argv: $this->sofficeArguments(tmpDir: $tmpDir),
			timeout: max(1, (int)$this->appConfig->getValueString('filinq', self::TIMEOUT_KEY, '60')),
			tmpDir: $tmpDir,
			backendName: 'svg-rasterizer'
		);
		$output = $tmpDir . '/chart.png';
		if ($exit !== 0 || is_file($output) === false || filesize($output) === 0) {
			return null;
		}

		return (string)file_get_contents($output);

	}//end convertIn()

	/**
	 * The soffice command line: a private profile, headless, SVG to PNG in the work directory.
	 *
	 * @param string $tmpDir The work directory.
	 *
	 * @return string[] The arguments, the binary first.
	 */
	private function sofficeArguments(string $tmpDir): array {
		return [
			$this->binary(),
			'-env:UserInstallation=file://' . $tmpDir . '/profile',
			'--headless',
			'--norestore',
			'--convert-to',
			'png',
			'--outdir',
			$tmpDir,
			$tmpDir . '/chart.svg',
		];

	}//end sofficeArguments()

	/**
	 * The `<img>` that takes the SVG's place, at the SVG's own size.
	 *
	 * @param string $svg The SVG markup, for its width and height.
	 * @param string $png The PNG bytes.
	 *
	 * @return string The element.
	 */
	private function imageTag(string $svg, string $png): string {
		$tag = '<img src="data:image/png;base64,' . base64_encode($png) . '"';
		foreach (['width', 'height'] as $dimension) {
			if (preg_match('#^<svg\b[^>]*\s' . $dimension . '="(\d+)"#', $svg, $found) === 1) {
				$tag .= ' ' . $dimension . '="' . $found[1] . '"';
			}
		}

		return $tag . ' alt="" />';

	}//end imageTag()

	/**
	 * The marker and warning text for a chart that was not converted.
	 *
	 * @param string $format The target format.
	 *
	 * @return string The text.
	 */
	private function markerText(string $format): string {
		$text = 'chart error: the chart could not be converted for %s';
		if ($this->l10n !== null) {
			return $this->l10n->t($text, [$format]);
		}

		return sprintf($text, $format);

	}//end markerText()

	/**
	 * The soffice binary, as the PDF backend reads it.
	 *
	 * @return string The binary path or name.
	 */
	private function binary(): string {
		$path = $this->appConfig->getValueString('filinq', self::BINARY_KEY, 'soffice');
		if ($path === '') {
			return 'soffice';
		}

		return $path;

	}//end binary()

	/**
	 * Remove a temp directory and everything soffice left in it.
	 *
	 * @param string $dir The directory.
	 *
	 * @return void
	 */
	private function removeDir(string $dir): void {
		if (is_dir($dir) === false) {
			return;
		}

		$items = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($items as $item) {
			if ($item->isDir() === true) {
				rmdir($item->getPathname());
				continue;
			}

			unlink($item->getPathname());
		}

		rmdir($dir);

	}//end removeDir()
}//end class
