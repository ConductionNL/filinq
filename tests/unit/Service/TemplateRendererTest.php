<?php

/**
 * Unit tests for TemplateRenderer conditional section conversion.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests conditional section conversion and rendering.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TemplateRendererTest extends TestCase
{

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
    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->createMock(LoggerInterface::class);
        $this->renderer = new TemplateRenderer($logger);

    }//end setUp()


    /**
     * Test equals operator conversion.
     *
     * @return void
     */
    public function testConvertEqualsCondition(): void
    {
        $html = '<div data-condition-field="zaaktype" '
            .'data-condition-op="equals" '
            .'data-condition-value="omgevingsvergunning">'
            .'<p>Content</p></div>';

        $result = $this->renderer->convertConditionalSections(html: $html);
        $this->assertStringContainsString('{% if zaaktype == "omgevingsvergunning" %}', $result);
        $this->assertStringContainsString('{% endif %}', $result);

    }//end testConvertEqualsCondition()


    /**
     * Test not_equals operator conversion.
     *
     * @return void
     */
    public function testConvertNotEqualsCondition(): void
    {
        $html = '<div data-condition-field="status" '
            .'data-condition-op="not_equals" '
            .'data-condition-value="afgesloten">'
            .'<p>Active</p></div>';

        $result = $this->renderer->convertConditionalSections(html: $html);
        $this->assertStringContainsString('{% if status != "afgesloten" %}', $result);

    }//end testConvertNotEqualsCondition()


    /**
     * Test is_empty operator conversion.
     *
     * @return void
     */
    public function testConvertIsEmptyCondition(): void
    {
        $html = '<div data-condition-field="opmerkingen" '
            .'data-condition-op="is_empty" '
            .'data-condition-value="">'
            .'<p>No remarks</p></div>';

        $result = $this->renderer->convertConditionalSections(html: $html);
        $this->assertStringContainsString('{% if opmerkingen is empty %}', $result);

    }//end testConvertIsEmptyCondition()


    /**
     * Test that HTML without conditions passes through unchanged.
     *
     * @return void
     */
    public function testNoConditionsPassedThrough(): void
    {
        $html   = '<div><p>Normal content</p></div>';
        $result = $this->renderer->convertConditionalSections(html: $html);
        $this->assertEquals($html, $result);

    }//end testNoConditionsPassedThrough()


    /**
     * Test conditional section renders correctly with matching data.
     *
     * @return void
     */
    public function testConditionalSectionRendersWithData(): void
    {
        $html = '<div data-condition-field="zaaktype" '
            .'data-condition-op="equals" '
            .'data-condition-value="omgevingsvergunning">'
            .'Shown content</div>';

        $processed = $this->renderer->convertConditionalSections(html: $html);
        $result    = $this->renderer->renderTemplate(
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
    public function testConditionalSectionHiddenWhenNotMet(): void
    {
        $html = '<div data-condition-field="zaaktype" '
            .'data-condition-op="equals" '
            .'data-condition-value="omgevingsvergunning">'
            .'Should be hidden</div>';

        $processed = $this->renderer->convertConditionalSections(html: $html);
        $result    = $this->renderer->renderTemplate(
            templateContent: $processed,
            data: ['zaaktype' => 'bouwvergunning']
        );
        $this->assertStringNotContainsString('Should be hidden', $result);

    }//end testConditionalSectionHiddenWhenNotMet()


}//end class
