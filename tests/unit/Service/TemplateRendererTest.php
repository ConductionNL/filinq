<?php

/**
 * Unit tests for TemplateRenderer conditional section conversion
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2025 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.DocuDesk.app
 */

namespace OCA\DocuDesk\Tests\Unit\Service;

use OCA\DocuDesk\Service\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TemplateRenderer
 *
 * Tests the conditional section conversion from HTML data attributes
 * to Twig {% if %} blocks, covering all supported operators.
 *
 * @category Tests
 * @package  OCA\DocuDesk\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.DocuDesk.nl
 *
 * @psalm-suppress PropertyNotSetInConstructor
 * @phpstan-extends TestCase
 */
class TemplateRendererTest extends TestCase
{

    /**
     * The TemplateRenderer instance being tested
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
     * Test that equals operator is converted correctly
     *
     * @return void
     */
    public function testConvertEqualsCondition(): void
    {
        $html = '<div data-condition-field="zaaktype" '
            . 'data-condition-op="equals" '
            . 'data-condition-value="omgevingsvergunning">'
            . '<p>Content here</p></div>';

        $result = $this->renderer->convertConditionalSections($html);

        $this->assertStringContainsString('{% if zaaktype == "omgevingsvergunning" %}', $result);
        $this->assertStringContainsString('{% endif %}', $result);
        $this->assertStringContainsString('<p>Content here</p>', $result);

    }//end testConvertEqualsCondition()


    /**
     * Test that not_equals operator is converted correctly
     *
     * @return void
     */
    public function testConvertNotEqualsCondition(): void
    {
        $html = '<div data-condition-field="status" '
            . 'data-condition-op="not_equals" '
            . 'data-condition-value="afgesloten">'
            . '<p>Active content</p></div>';

        $result = $this->renderer->convertConditionalSections($html);

        $this->assertStringContainsString('{% if status != "afgesloten" %}', $result);
        $this->assertStringContainsString('{% endif %}', $result);

    }//end testConvertNotEqualsCondition()


    /**
     * Test that contains operator is converted correctly
     *
     * @return void
     */
    public function testConvertContainsCondition(): void
    {
        $html = '<div data-condition-field="beschrijving" '
            . 'data-condition-op="contains" '
            . 'data-condition-value="urgent">'
            . '<p>Urgent notice</p></div>';

        $result = $this->renderer->convertConditionalSections($html);

        $this->assertStringContainsString('{% if "urgent" in beschrijving %}', $result);
        $this->assertStringContainsString('{% endif %}', $result);

    }//end testConvertContainsCondition()


    /**
     * Test that is_empty operator is converted correctly
     *
     * @return void
     */
    public function testConvertIsEmptyCondition(): void
    {
        $html = '<div data-condition-field="opmerkingen" '
            . 'data-condition-op="is_empty" '
            . 'data-condition-value="">'
            . '<p>No remarks</p></div>';

        $result = $this->renderer->convertConditionalSections($html);

        $this->assertStringContainsString('{% if opmerkingen is empty %}', $result);
        $this->assertStringContainsString('{% endif %}', $result);

    }//end testConvertIsEmptyCondition()


    /**
     * Test that is_not_empty operator is converted correctly
     *
     * @return void
     */
    public function testConvertIsNotEmptyCondition(): void
    {
        $html = '<div data-condition-field="bijlage" '
            . 'data-condition-op="is_not_empty" '
            . 'data-condition-value="">'
            . '<p>See attachment</p></div>';

        $result = $this->renderer->convertConditionalSections($html);

        $this->assertStringContainsString('{% if bijlage is not empty %}', $result);
        $this->assertStringContainsString('{% endif %}', $result);

    }//end testConvertIsNotEmptyCondition()


    /**
     * Test that HTML without conditions is passed through unchanged
     *
     * @return void
     */
    public function testNoConditionsPassedThrough(): void
    {
        $html = '<div><p>Normal content</p></div>';

        $result = $this->renderer->convertConditionalSections($html);

        $this->assertEquals($html, $result);

    }//end testNoConditionsPassedThrough()


    /**
     * Test that conditional sections render correctly with data
     *
     * @return void
     */
    public function testConditionalSectionRendersWithData(): void
    {
        $html = '<div data-condition-field="zaaktype" '
            . 'data-condition-op="equals" '
            . 'data-condition-value="omgevingsvergunning">'
            . 'Shown content</div>';

        $processed = $this->renderer->convertConditionalSections($html);
        $result    = $this->renderer->renderTemplate(
            $processed,
            ['zaaktype' => 'omgevingsvergunning']
        );

        $this->assertStringContainsString('Shown content', $result);

    }//end testConditionalSectionRendersWithData()


    /**
     * Test that conditional sections are hidden when condition is not met
     *
     * @return void
     */
    public function testConditionalSectionHiddenWhenNotMet(): void
    {
        $html = '<div data-condition-field="zaaktype" '
            . 'data-condition-op="equals" '
            . 'data-condition-value="omgevingsvergunning">'
            . 'Should be hidden</div>';

        $processed = $this->renderer->convertConditionalSections($html);
        $result    = $this->renderer->renderTemplate(
            $processed,
            ['zaaktype' => 'bouwvergunning']
        );

        $this->assertStringNotContainsString('Should be hidden', $result);

    }//end testConditionalSectionHiddenWhenNotMet()


}//end class
