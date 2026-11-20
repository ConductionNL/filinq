<?php

/**
 * Unit tests for MdtoSidecarBuilder: the core elements as OpenRegister's
 * generator writes them, the supplement elements, the bestand section,
 * determinism and dossier aggregation.
 *
 * @category  Tests
 * @package   OCA\Filinq\Tests\Unit\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */

declare(strict_types=1);

namespace OCA\Filinq\Tests\Unit\Service;

use DOMDocument;
use DOMXPath;
use OCA\Filinq\Service\MdtoSidecarBuilder;
use OCA\Filinq\Service\MdtoSidecarInput;
use OCA\Filinq\Service\MdtoXmlWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MdtoSidecarBuilder::class)]
#[CoversClass(MdtoSidecarInput::class)]
#[CoversClass(MdtoXmlWriter::class)]
class MdtoSidecarBuilderTest extends TestCase {

	/**
	 * A complete document with a confirmed and a proposed restriction and one file.
	 *
	 * @param array<string, mixed> $supplement Supplement fields to override.
	 *
	 * @return MdtoSidecarInput
	 */
	private static function document(array $supplement=[]): MdtoSidecarInput {
		return new MdtoSidecarInput(
			record: [
				'uuid' => '6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f',
				'title' => 'Besluit subsidietoekenning cultuur',
				'retention' => ['archiefnominatie' => 'bewaren', 'classificatie' => '1.1', 'bewaartermijn' => 'P20Y', 'archiefactiedatum' => '2044-11-03'],
			],
			supplement: array_merge(
				[
					'aggregatieniveau' => 'archiefstuk',
					'omschrijving' => 'Besluit',
					'taal' => 'nl',
					'dekkingInTijdBegin' => '2024-11-03',
					'dekkingInTijdEind' => '2024-11-03',
					'beperkingGebruik' => [
						['type' => 'avg-persoonsgegevens', 'grondslag' => 'AVG art. 6 lid 1 sub e', 'proposed' => false],
						['type' => 'woo-uitzondering', 'grondslag' => 'Woo', 'proposed' => true],
					],
					'betrokkene' => [['rol' => 'aanvrager', 'naamRef' => '00000000-0000-0000-0000-0000000000c1']],
				],
				$supplement
			),
			organisationId: '00000001002220647000',
			organisationName: 'Gemeente Demostad',
			files: [['name' => 'besluit.pdf', 'format' => 'application/pdf', 'size' => 2048, 'checksum' => str_repeat('AB', 32), 'checksumDate' => '2026-11-14T10:00:00+01:00']]
		);

	}//end document()

	/**
	 * An XPath over the sidecar with the mdto prefix bound.
	 *
	 * @param string $xml The sidecar.
	 *
	 * @return DOMXPath
	 */
	private static function xpath(string $xml): DOMXPath {
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace('m', 'https://www.nationaalarchief.nl/mdto');

		return $xpath;

	}//end xpath()

	public function testTheDocumentSidecarMatchesItsFixtureByteForByte(): void {
		$this->assertSame(
			(string) file_get_contents(__DIR__ . '/../../fixtures/mdto/document-sidecar.xml'),
			(new MdtoSidecarBuilder())->buildXml(self::document())
		);

	}//end testTheDocumentSidecarMatchesItsFixtureByteForByte()

	public function testExportingTwiceYieldsIdenticalBytes(): void {
		$builder = new MdtoSidecarBuilder();

		$this->assertSame($builder->buildXml(self::document()), $builder->buildXml(self::document()));

	}//end testExportingTwiceYieldsIdenticalBytes()

	public function testTheCoreElementsAreWrittenAsOpenRegistersGeneratorWritesThem(): void {
		$xpath = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document()));
		$io    = '/m:MDTO/m:informatieobject';

		$this->assertSame('6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f', $xpath->evaluate("string($io/m:identificatie/m:identificatieKenmerk)"));
		$this->assertSame('00000001002220647000', $xpath->evaluate("string($io/m:identificatie/m:identificatieBron)"));
		$this->assertSame('Besluit subsidietoekenning cultuur', $xpath->evaluate("string($io/m:naam)"));
		$this->assertSame('B', $xpath->evaluate("string($io/m:waardering/m:begripCode)"));
		$this->assertSame('Blijvend te bewaren', $xpath->evaluate("string($io/m:waardering/m:begripLabel)"));
		$this->assertSame('Waarderingen', $xpath->evaluate("string($io/m:waardering/m:begripBegrippenlijst/m:verwijzingNaam)"));
		$this->assertSame('1.1', $xpath->evaluate("string($io/m:classificatie/m:begripLabel)"));
		$this->assertSame('P20Y', $xpath->evaluate("string($io/m:bewaartermijn/m:termijnLooptijd)"));
		$this->assertSame('Gemeente Demostad', $xpath->evaluate("string($io/m:archiefvormer/m:verwijzingNaam)"));
		$this->assertSame('OpenRegister', $xpath->evaluate("string($io/m:archiefvormer/m:verwijzingIdentificatie/m:identificatieBron)"));

	}//end testTheCoreElementsAreWrittenAsOpenRegistersGeneratorWritesThem()

	public function testOnlyConfirmedRestrictionsAreExported(): void {
		$xpath = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document()));

		$this->assertSame(1, $xpath->query('//m:informatieobject/m:beperkingGebruik')->length);
		$this->assertSame('avg-persoonsgegevens', $xpath->evaluate('string(//m:beperkingGebruik/m:beperkingGebruikType/m:begripLabel)'));

	}//end testOnlyConfirmedRestrictionsAreExported()

	public function testWithoutAConfirmedRestrictionTheUnrecordedTermIsWritten(): void {
		$supplement = ['beperkingGebruik' => [['type' => 'woo-uitzondering', 'grondslag' => 'Woo', 'proposed' => true]]];
		$xpath      = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document($supplement)));

		$this->assertSame('Nader te bepalen', $xpath->evaluate('string(//m:beperkingGebruik/m:beperkingGebruikType/m:begripLabel)'));
		$this->assertSame(0, $xpath->query('//m:beperkingGebruikNadereBeschrijving')->length);

	}//end testWithoutAConfirmedRestrictionTheUnrecordedTermIsWritten()

	public function testARestrictionEndDateBecomesItsTerm(): void {
		$supplement = ['beperkingGebruik' => [['type' => 'vertrouwelijk', 'grondslag' => 'Gemeentewet', 'omschrijving' => 'Tot besluitvorming', 'einddatum' => '2030-01-01']]];
		$xpath      = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document($supplement)));

		$this->assertSame('Gemeentewet — Tot besluitvorming', $xpath->evaluate('string(//m:beperkingGebruikNadereBeschrijving)'));
		$this->assertSame('2030-01-01', $xpath->evaluate('string(//m:beperkingGebruikTermijn/m:termijnEinddatum)'));

	}//end testARestrictionEndDateBecomesItsTerm()

	public function testTheBestandCarriesTheSha256AndPointsBackAtTheRecord(): void {
		$xpath = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document()));

		$this->assertSame(str_repeat('ab', 32), $xpath->evaluate('string(/m:MDTO/m:bestand/m:checksum/m:checksumWaarde)'));
		$this->assertSame('SHA-256', $xpath->evaluate('string(/m:MDTO/m:bestand/m:checksum/m:checksumAlgoritme/m:begripLabel)'));
		$this->assertSame('2048', $xpath->evaluate('string(/m:MDTO/m:bestand/m:omvang)'));
		$this->assertSame('6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f', $xpath->evaluate('string(/m:MDTO/m:bestand/m:isRepresentatieVan/m:verwijzingIdentificatie/m:identificatieKenmerk)'));

	}//end testTheBestandCarriesTheSha256AndPointsBackAtTheRecord()

	public function testAnOverrideReplacesTheArchiefvormer(): void {
		$xpath = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document(['archiefvormerOverride' => 'Gemeenschappelijke regeling Noord'])));

		$this->assertSame('Gemeenschappelijke regeling Noord', $xpath->evaluate('string(//m:archiefvormer/m:verwijzingNaam)'));
		$this->assertSame(0, $xpath->query('//m:archiefvormer/m:verwijzingIdentificatie')->length);

	}//end testAnOverrideReplacesTheArchiefvormer()

	public function testAPartyIsWrittenByItsReference(): void {
		$xpath = self::xpath((new MdtoSidecarBuilder())->buildXml(self::document(['betrokkene' => [['rol' => 'aanvrager', 'naamRef' => ''], ['naamRef' => 'urn:contact:7']]])));

		$this->assertSame(1, $xpath->query('//m:betrokkene')->length);
		$this->assertSame('betrokkene', $xpath->evaluate('string(//m:betrokkene/m:betrokkeneTypeRelatie/m:begripLabel)'));
		$this->assertSame('urn:contact:7', $xpath->evaluate('string(//m:betrokkene/m:betrokkeneActor/m:verwijzingNaam)'));

	}//end testAPartyIsWrittenByItsReference()

	public function testADossierAggregatesItsMembersAsParts(): void {
		$input = new MdtoSidecarInput(
			record: ['uuid' => 'd0551e00-0000-4000-8000-000000000001', 'title' => 'Woo-verzoek 2024-017', 'retention' => ['archiefnominatie' => 'vernietigen', 'classificatie' => '6']],
			supplement: ['aggregatieniveau' => 'dossier'],
			organisationId: 'org',
			members: [
				['uuid' => 'a0000000-0000-4000-8000-000000000001', 'title' => 'Verzoek'],
				['uuid' => 'a0000000-0000-4000-8000-000000000002', 'naam' => 'Besluit'],
			]
		);
		$xpath = self::xpath((new MdtoSidecarBuilder())->buildXml($input));

		$this->assertSame('Dossier', $xpath->evaluate('string(//m:informatieobject/m:aggregatieniveau/m:begripLabel)'));
		$this->assertSame(2, $xpath->query('//m:informatieobject/m:bevatOnderdeel')->length);
		$this->assertSame('Besluit', $xpath->evaluate('string(//m:bevatOnderdeel[2]/m:verwijzingNaam)'));
		$this->assertSame('a0000000-0000-4000-8000-000000000002', $xpath->evaluate('string(//m:bevatOnderdeel[2]/m:verwijzingIdentificatie/m:identificatieKenmerk)'));
		$this->assertSame('V', $xpath->evaluate('string(//m:waardering/m:begripCode)'));
		$this->assertSame(0, $xpath->query('/m:MDTO/m:bestand')->length);
		$this->assertSame(0, $xpath->query('//m:bewaartermijn')->length);

	}//end testADossierAggregatesItsMembersAsParts()

	public function testTheJsonProjectionMirrorsTheXml(): void {
		$json = (new MdtoSidecarBuilder())->buildJson(self::document(['betrokkene' => [['rol' => 'a', 'naamRef' => 'urn:x:1'], ['rol' => 'b', 'naamRef' => 'urn:x:2']]]));
		$io   = $json['MDTO']['informatieobject'];

		$this->assertSame('6f1c2d3e-4b5a-4c6d-8e7f-0a1b2c3d4e5f', $io['identificatie']['identificatieKenmerk']);
		$this->assertSame('Archiefstuk', $io['aggregatieniveau']['begripLabel']);
		$this->assertSame(['urn:x:1', 'urn:x:2'], array_column(array_column($io['betrokkene'], 'betrokkeneActor'), 'verwijzingNaam'));
		$this->assertSame(str_repeat('ab', 32), $json['MDTO']['bestand']['checksum']['checksumWaarde']);
		$this->assertSame(
			['identificatie', 'naam', 'aggregatieniveau', 'classificatie', 'omschrijving', 'dekkingInTijd', 'taal', 'waardering', 'bewaartermijn', 'heeftRepresentatie', 'archiefvormer', 'betrokkene', 'beperkingGebruik'],
			array_keys($io)
		);

	}//end testTheJsonProjectionMirrorsTheXml()
}//end class
