<?php

/**
 * Unit tests for TemplateRenderer conditional section conversion.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\Charts\ChartSvgRenderer;
use OCA\Filinq\Service\Charts\TableHtmlRenderer;
use OCA\Filinq\Service\Charts\TemplateImageResolver;
use OCA\Filinq\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests conditional section conversion and rendering.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplateRendererTest extends TestCase {

	/**
	 * The TemplateRenderer instance
	 *
	 * @var TemplateRenderer
	 */
	private TemplateRenderer $renderer;

	/**
	 * Set up the test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$logger = $this->createMock(LoggerInterface::class);
		$this->renderer = new TemplateRenderer($logger, new ChartSvgRenderer(), new TableHtmlRenderer());

	}//end setUp()

	/**
	 * Test equals operator conversion.
	 *
	 * @return void
	 */
	public function testConvertEqualsCondition(): void {
		$html = '<div data-condition-field="zaaktype" '
			. 'data-condition-op="equals" '
			. 'data-condition-value="omgevingsvergunning">'
			. '<p>Content</p></div>';

		$result = $this->renderer->convertConditionalSections(html: $html);
		$this->assertStringContainsString('{% if zaaktype == "omgevingsvergunning" %}', $result);
		$this->assertStringContainsString('{% endif %}', $result);

	}//end testConvertEqualsCondition()

	/**
	 * Test not_equals operator conversion.
	 *
	 * @return void
	 */
	public function testConvertNotEqualsCondition(): void {
		$html = '<div data-condition-field="status" '
			. 'data-condition-op="not_equals" '
			. 'data-condition-value="afgesloten">'
			. '<p>Active</p></div>';

		$result = $this->renderer->convertConditionalSections(html: $html);
		$this->assertStringContainsString('{% if status != "afgesloten" %}', $result);

	}//end testConvertNotEqualsCondition()

	/**
	 * Test is_empty operator conversion.
	 *
	 * @return void
	 */
	public function testConvertIsEmptyCondition(): void {
		$html = '<div data-condition-field="opmerkingen" '
			. 'data-condition-op="is_empty" '
			. 'data-condition-value="">'
			. '<p>No remarks</p></div>';

		$result = $this->renderer->convertConditionalSections(html: $html);
		$this->assertStringContainsString('{% if opmerkingen is empty %}', $result);

	}//end testConvertIsEmptyCondition()

	/**
	 * Test that HTML without conditions passes through unchanged.
	 *
	 * @return void
	 */
	public function testNoConditionsPassedThrough(): void {
		$html = '<div><p>Normal content</p></div>';
		$result = $this->renderer->convertConditionalSections(html: $html);
		$this->assertEquals($html, $result);

	}//end testNoConditionsPassedThrough()

	/**
	 * Test conditional section renders correctly with matching data.
	 *
	 * @return void
	 */
	public function testConditionalSectionRendersWithData(): void {
		$html = '<div data-condition-field="zaaktype" '
			. 'data-condition-op="equals" '
			. 'data-condition-value="omgevingsvergunning">'
			. 'Shown content</div>';

		$processed = $this->renderer->convertConditionalSections(html: $html);
		$result = $this->renderer->renderTemplate(
			templateContent: $processed,
			data: ['zaaktype' => 'omgevingsvergunning']
		);
		$this->assertStringContainsString('Shown content', $result);

	}//end testConditionalSectionRendersWithData()

	/**
	 * Test conditional section is hidden when condition is not met.
	 *
	 * @return void
	 */
	public function testConditionalSectionHiddenWhenNotMet(): void {
		$html = '<div data-condition-field="zaaktype" '
			. 'data-condition-op="equals" '
			. 'data-condition-value="omgevingsvergunning">'
			. 'Should be hidden</div>';

		$processed = $this->renderer->convertConditionalSections(html: $html);
		$result = $this->renderer->renderTemplate(
			templateContent: $processed,
			data: ['zaaktype' => 'bouwvergunning']
		);
		$this->assertStringNotContainsString('Should be hidden', $result);

	}//end testConditionalSectionHiddenWhenNotMet()

	/**
	 * The sandbox whitelist is extended with exactly `chart` and
	 * `data_table` — both are now callable — while a non-whitelisted
	 * function and object method/property access remain refused exactly as
	 * before this change (REQ-DDTCH-005).
	 *
	 * @return void
	 */
	public function testWhitelistIsExact(): void {
		$chartResult = $this->renderer->renderTemplate(
			templateContent: '{{ chart("bar", {labels: ["A"], series: [{name: "S", values: [1]}]}) }}',
			data: []
		);
		$this->assertStringContainsString('<svg', $chartResult);

		$tableResult = $this->renderer->renderTemplate(
			templateContent: '{{ data_table([{name: "Acme"}], [{key: "name", label: "Name"}]) }}',
			data: []
		);
		$this->assertStringContainsString('<table', $tableResult);

		$this->expectException(\Exception::class);
		$this->renderer->renderTemplate(templateContent: '{{ dump(1) }}', data: []);

	}//end testWhitelistIsExact()

	/**
	 * Object method calls remain refused after the whitelist extension.
	 *
	 * @return void
	 */
	public function testObjectMethodCallsStillRefused(): void {
		$this->expectException(\Exception::class);
		$this->renderer->renderTemplate(
			templateContent: '{{ now.format("Y") }}',
			data: ['now' => new \DateTime()]
		);

	}//end testObjectMethodCallsStillRefused()

	/**
	 * `chart()` and `data_table()` render together inside a document
	 * template — the combined Twig-path integration required by
	 * template-charts task 5.1.
	 *
	 * @return void
	 */
	public function testRenderTemplateWithChartAndDataTable(): void {
		$template = '<h1>{{ title }}</h1>'
			. '{{ chart("bar", {labels: labels, series: [{name: "Stars", values: values}]}, {title: "Competitors"}) }}'
			. '{{ data_table(rows, columns) }}';

		$data = [
			'title' => 'Report',
			'labels' => ['Alpha', 'Beta'],
			'values' => [10, 20],
			'rows' => [['name' => 'Alpha', 'stars' => 10], ['name' => 'Beta', 'stars' => 20]],
			'columns' => [
				['key' => 'name', 'label' => 'Name'],
				['key' => 'stars', 'label' => 'Stars', 'format' => 'number'],
			],
		];

		$result = $this->renderer->renderTemplate(templateContent: $template, data: $data);

		$this->assertStringContainsString('<h1>Report</h1>', $result);
		$this->assertStringContainsString('<svg', $result);
		$this->assertStringContainsString('Competitors', $result);
		$this->assertStringContainsString('<table', $result);
		$this->assertStringContainsString('Alpha', $result);
		$this->assertEmpty($this->renderer->getLastRenderWarnings());

	}//end testRenderTemplateWithChartAndDataTable()

	/**
	 * A malformed `chart()` call inside a template produces a visible
	 * `[chart error: ...]` marker and a generation warning rather than a
	 * fatal Twig error (REQ-DDTCH-002).
	 *
	 * @return void
	 */
	public function testChartErrorSurfacesAsWarning(): void {
		$result = $this->renderer->renderTemplate(
			templateContent: '{{ chart("bar", {labels: ["A"]}) }}',
			data: []
		);

		$this->assertStringContainsString('chart error', $result);
		$this->assertNotEmpty($this->renderer->getLastRenderWarnings());

	}//end testChartErrorSurfacesAsWarning()

	/**
	 * The huisstijl primary color is threaded through to the chart palette
	 * seed when supplied to renderTemplate().
	 *
	 * @return void
	 */
	public function testHuisstijlSeedsChartPalette(): void {
		$template = '{{ chart("bar", {labels: ["A"], series: [{name: "S", values: [1]}]}) }}';

		$withHuisstijl = $this->renderer->renderTemplate(
			templateContent: $template,
			data: [],
			huisstijl: ['primaryColor' => '#008040']
		);
		$withoutHuisstijl = $this->renderer->renderTemplate(templateContent: $template, data: []);

		$this->assertNotSame($withoutHuisstijl, $withHuisstijl);

	}//end testHuisstijlSeedsChartPalette()

	/**
	 * The `chart()`/`data_table()` implementations are pure with respect to
	 * the instance: no write side effects and no network access are
	 * reachable from either function's execution path (REQ-DDTCH-005 —
	 * a static/manual contract check, since neither function accepts any
	 * writable resource or URL argument by construction).
	 *
	 * @return void
	 */
	public function testVisualFunctionsAreSideEffectBounded(): void {
		// Both functions accept only plain data (string/array) arguments —
		// there is no file handle, URL, or database argument in their
		// signatures for a template to pass, and their implementations
		// (ChartSvgRenderer, TableHtmlRenderer) perform pure string
		// assembly with no I/O calls. Exercise both once to prove they
		// execute without requiring any injected I/O collaborator.
		$result = $this->renderer->renderTemplate(
			templateContent: '{{ chart("pie", {labels: ["A","B"], series: [{name:"S", values:[1,2]}]}) }}'
				. '{{ data_table([{a: 1}], [{key: "a", label: "A"}]) }}',
			data: []
		);

		$this->assertStringContainsString('<svg', $result);
		$this->assertStringContainsString('<table', $result);

	}//end testVisualFunctionsAreSideEffectBounded()

	/**
	 * More than the per-document chart cap degrades the extra charts to a
	 * visible placeholder instead of growing the document unboundedly.
	 *
	 * @return void
	 */
	public function testMaxChartsPerDocumentGuardrail(): void {
		$call = '{{ chart("bar", {labels: ["A"], series: [{name:"S", values:[1]}]}) }}';
		$template = str_repeat($call, 22);

		$result = $this->renderer->renderTemplate(templateContent: $template, data: []);

		$this->assertStringContainsString('exceeds the maximum of 20 charts', $result);
		$this->assertNotEmpty($this->renderer->getLastRenderWarnings());

	}//end testMaxChartsPerDocumentGuardrail()

	/**
	 * The sandbox whitelist is exactly the eight functions, nc_image included.
	 *
	 * @return void
	 */
	public function testFunctionWhitelistIsPinnedExactly(): void {
		$constant = new \ReflectionClassConstant(TemplateRenderer::class, 'ALLOWED_FUNCTIONS');

		$this->assertSame(
			['range', 'cycle', 'date', 'max', 'min', 'chart', 'data_table', 'nc_image'],
			$constant->getValue()
		);

	}//end testFunctionWhitelistIsPinnedExactly()

	/**
	 * A readable raster image is embedded as a data URI with an escaped alt text.
	 *
	 * @return void
	 */
	public function testNcImageEmbedsAnImageTheUserCanRead(): void {
		$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
		$renderer = $this->rendererWithImage(file: $this->file(bytes: $png));

		$result = $renderer->renderTemplate(
			templateContent: '{{ nc_image(logo, {alt: "Logo <b>", width: 120, height: "x"}) }}',
			data: ['logo' => 42]
		);

		$this->assertStringStartsWith('<img src="data:image/png;base64,', $result);
		$this->assertStringContainsString('alt="Logo &lt;b&gt;"', $result);
		$this->assertStringContainsString('width="120"', $result);
		$this->assertStringNotContainsString('height=', $result);
		$this->assertSame([], $renderer->getLastRenderWarnings());

	}//end testNcImageEmbedsAnImageTheUserCanRead()

	/**
	 * A file the user cannot reach becomes a marker and a warning, and no byte of it lands in the output.
	 *
	 * @return void
	 */
	public function testNcImageOfAnUnreachableFileIsAMarkerAndAWarning(): void {
		$renderer = $this->rendererWithImage(file: null);

		$result = $renderer->renderTemplate(templateContent: '{{ nc_image(7) }}', data: []);

		$this->assertSame('<span>[image unavailable: not found or no access]</span>', $result);
		$this->assertSame(['image unavailable: not found or no access'], $renderer->getLastRenderWarnings());

	}//end testNcImageOfAnUnreachableFileIsAMarkerAndAWarning()

	/**
	 * Without a resolver wired, nc_image still degrades visibly instead of failing the render.
	 *
	 * @return void
	 */
	public function testNcImageWithoutAResolverDegradesVisibly(): void {
		$result = $this->renderer->renderTemplate(templateContent: '{{ nc_image(7) }}', data: []);

		$this->assertStringContainsString('[image unavailable: image support is not available]', $result);
		$this->assertCount(1, $this->renderer->getLastRenderWarnings());

	}//end testNcImageWithoutAResolverDegradesVisibly()

	/**
	 * With a translator wired, the markers and the empty-table row come out translated.
	 *
	 * @return void
	 */
	public function testMarkersAndEmptyRowAreTranslated(): void {
		$l10n = $this->createMock(\OCP\IL10N::class);
		$l10n->method('t')->willReturnCallback(
			static fn (string $text, array $parameters = []) => 'NL(' . vsprintf($text, $parameters) . ')'
		);
		$logger = $this->createMock(LoggerInterface::class);
		$renderer = new TemplateRenderer($logger, new ChartSvgRenderer($l10n), new TableHtmlRenderer(), null, $l10n);

		$result = $renderer->renderTemplate(
			templateContent: '{{ chart("radar", {labels: ["A"], series: [{name: "S", values: [1]}]}) }}'
				. '{{ data_table([], [{key: "a", label: "A"}]) }}{{ nc_image(1) }}',
			data: []
		);

		$this->assertStringContainsString('NL(chart error: unsupported chart type &quot;radar&quot;)', $result);
		$this->assertStringContainsString('NL(No data available)', $result);
		$this->assertStringContainsString('NL(image unavailable: NL(image support is not available))', $result);

	}//end testMarkersAndEmptyRowAreTranslated()

	/**
	 * A renderer whose image resolver is the real class over a user folder holding one file (or none).
	 *
	 * @param \OCP\Files\File|null $file The file id 42 and 7 resolve to.
	 *
	 * @return TemplateRenderer
	 */
	private function rendererWithImage(?\OCP\Files\File $file): TemplateRenderer {
		$user = $this->createMock(\OCP\IUser::class);
		$user->method('getUID')->willReturn('alice');
		$session = $this->createMock(\OCP\IUserSession::class);
		$session->method('getUser')->willReturn($user);
		$folder = $this->createMock(\OCP\Files\Folder::class);
		$folder->method('getFirstNodeById')->willReturn($file);
		$root = $this->createMock(\OCP\Files\IRootFolder::class);
		$root->method('getUserFolder')->with('alice')->willReturn($folder);
		$config = $this->createMock(\OCP\IAppConfig::class);
		$config->method('getValueInt')->willReturnCallback(static fn (string $app, string $key, int $default) => $default);

		return new TemplateRenderer(
			$this->createMock(LoggerInterface::class),
			new ChartSvgRenderer(),
			new TableHtmlRenderer(),
			new TemplateImageResolver($root, $session, $config)
		);

	}//end rendererWithImage()

	/**
	 * A readable file with the given bytes.
	 *
	 * @param string $bytes The content.
	 *
	 * @return \OCP\Files\File
	 */
	private function file(string $bytes): \OCP\Files\File {
		$file = $this->createMock(\OCP\Files\File::class);
		$file->method('getSize')->willReturn(strlen($bytes));
		$file->method('isReadable')->willReturn(true);
		$file->method('getContent')->willReturn($bytes);
		return $file;

	}//end file()

}//end class
