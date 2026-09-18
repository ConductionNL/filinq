<?php

/**
 * Unit tests for IntakeDefaultRuleService
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.filinq.app
 *
 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
 */

namespace OCA\Filinq\Tests\Unit\Service;

use OCA\Filinq\Service\DocumentObjectServiceResolver;
use OCA\Filinq\Service\IntakeDefaultRuleService;
use OCA\OpenRegister\Service\ObjectService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Asserts that nothing arrives unclassified, that the stamp names the rule that
 * chose it, and that a document nothing matches says so rather than failing.
 *
 * @category Tests
 * @package  OCA\Filinq\Tests\Unit\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IntakeDefaultRuleServiceTest extends TestCase {

	/**
	 * Build the service over a fake register holding the given rules.
	 *
	 * @param array<int, array<string, mixed>> $rules The stored rules.
	 * @param bool $readable Whether the register answers at all.
	 *
	 * @return IntakeDefaultRuleService The service under test.
	 */
	private function service(array $rules, bool $readable = true): IntakeDefaultRuleService {
		$resolver = $this->createMock(DocumentObjectServiceResolver::class);
		if ($readable === false) {
			$resolver->method('resolve')->willThrowException(new RuntimeException('no register'));
		} else {
			$objectService = $this->createMock(ObjectService::class);
			$objectService->method('searchObjects')->willReturn($rules);
			$resolver->method('resolve')->willReturn($objectService);
		}

		return new IntakeDefaultRuleService($resolver, $this->createMock(LoggerInterface::class));

	}//end service()

	/**
	 * The scan rule.
	 *
	 * @return array<string, mixed> The rule.
	 */
	private function scanRule(): array {
		return [
			'uuid' => 'rule-scan',
			'name' => 'Alles van de scanner is een brief',
			'channel' => 'scan',
			'senderPattern' => '',
			'stamps' => ['documentType' => 'brief', 'confidentiality' => 'intern'],
			'order' => 100,
			'active' => true,
		];

	}//end scanRule()

	/**
	 * A scan carries both stamped values, and names the rule, before any classifier ran.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testNothingArrivesUnclassified(): void {
		$service = $this->service(rules: [$this->scanRule()]);

		$document = $service->stamp(document: ['channel' => 'scan'], channel: 'scan', sender: '');

		$this->assertSame('brief', $document['stampedDefaults']['documentType']);
		$this->assertSame('intern', $document['stampedDefaults']['confidentiality']);
		$this->assertSame('rule-scan', $document['defaultRule']);

	}//end testNothingArrivesUnclassified()

	/**
	 * A channel no rule matches is stamped with nothing, and says so.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testADocumentNoRuleMatchesIsStampedWithNothingAndSaysSo(): void {
		$service = $this->service(rules: [$this->scanRule()]);

		$document = $service->stamp(document: ['channel' => 'mail'], channel: 'mail', sender: 'jan@voorbeeld.nl');

		$this->assertSame([], $document['stampedDefaults']);
		$this->assertSame('', $document['defaultRule']);

	}//end testADocumentNoRuleMatchesIsStampedWithNothingAndSaysSo()

	/**
	 * A sender pattern narrows a rule to one party.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testASenderPatternNarrowsARuleToOneParty(): void {
		$supplier = [
			'uuid' => 'rule-supplier',
			'channel' => 'mail',
			'senderPattern' => '@leverancier.nl',
			'stamps' => ['documentType' => 'factuur'],
			'order' => 10,
			'active' => true,
		];
		$service = $this->service(rules: [$this->scanRule(), $supplier]);

		$matched = $service->match(channel: 'mail', sender: 'facturen@leverancier.nl');
		$this->assertSame('rule-supplier', $matched['uuid']);

		$unmatched = $service->match(channel: 'mail', sender: 'jan@voorbeeld.nl');
		$this->assertNull($unmatched);

	}//end testASenderPatternNarrowsARuleToOneParty()

	/**
	 * When several rules match, the lowest order wins.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testTheLowestOrderWinsWhenSeveralRulesMatch(): void {
		$general = ['uuid' => 'rule-general', 'channel' => '', 'stamps' => ['documentType' => 'stuk'], 'order' => 500, 'active' => true];
		$specific = ['uuid' => 'rule-specific', 'channel' => 'scan', 'stamps' => ['documentType' => 'brief'], 'order' => 10, 'active' => true];
		$service = $this->service(rules: [$general, $specific]);

		$this->assertSame('rule-specific', $service->match(channel: 'scan', sender: '')['uuid']);

	}//end testTheLowestOrderWinsWhenSeveralRulesMatch()

	/**
	 * An inactive rule stamps nothing, and stays readable.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAnInactiveRuleStampsNothing(): void {
		$rule = ($this->scanRule() + []);
		$rule['active'] = false;
		$service = $this->service(rules: [$rule]);

		$this->assertNull($service->match(channel: 'scan', sender: ''));

	}//end testAnInactiveRuleStampsNothing()

	/**
	 * An unreadable rule set stamps nothing rather than losing the arrival.
	 *
	 * @return void
	 *
	 * @spec openspec/changes/inbound-documents-and-the-worklist/specs/inbound-auto-classification/spec.md
	 */
	public function testAnUnreadableRuleSetStampsNothingRatherThanFailing(): void {
		$service = $this->service(rules: [], readable: false);

		$document = $service->stamp(document: ['channel' => 'scan'], channel: 'scan', sender: '');

		$this->assertSame([], $document['stampedDefaults']);
		$this->assertSame('', $document['defaultRule']);

	}//end testAnUnreadableRuleSetStampsNothingRatherThanFailing()
}//end class
