<?php

/**
 * MDTO Sidecar Builder
 *
 * Assembles the MDTO sidecar of a Filinq document or dossier from the
 * record's OpenRegister retention block and its mdtoSupplement.
 *
 * @category  Service
 * @package   OCA\Filinq\Service
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git_id>
 * @link      https://www.filinq.app
 *
 * SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
 * SPDX-License-Identifier: EUPL-1.2
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */

declare(strict_types=1);

namespace OCA\Filinq\Service;

use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * Design D4. One `mdto:informatieobject` per record: the core elements as
 * OpenRegister's MdtoXmlGenerator writes them (namespace, element names,
 * begrip and verwijzing shapes, waardering map), the supplement elements,
 * `heeftRepresentatie` per file and, after it, one `mdto:bestand` per file
 * with its sha256 checksum. A dossier is an informatieobject at
 * aggregatieniveau `Dossier` whose members are `bevatOnderdeel` references.
 * Only confirmed values are written: a use restriction still flagged
 * `proposed` never leaves Filinq. The output depends on the input only
 * (element order follows the MDTO-XML 1.0.1 sequence, no clock is read), so
 * an unchanged record exports byte-identical.
 *
 * @category Service
 * @package  OCA\Filinq\Service
 * @author   Conduction B.V. <info@conduction.nl>
 * @license  EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link     https://www.filinq.app
 *
 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
 */
final class MdtoSidecarBuilder {




	/**
	 * The MDTO-XML schema the document declares.
	 */
	private const SCHEMA_LOCATION = 'https://www.nationaalarchief.nl/mdto/MDTO-XML1.0.1.xsd';

	/**
	 * Appraisal => [code, label], as OpenRegister's generator maps it.
	 */
	private const APPRAISAL_MAP = [
		'bewaren' => ['B', 'Blijvend te bewaren'],
		'blijvend_bewaren' => ['B', 'Blijvend te bewaren'],
		'vernietigen' => ['V', 'Tijdelijk te bewaren'],
		'nog_niet_bepaald' => ['N', 'Nader te bepalen'],
	];

	/**
	 * Aggregation level => the begrip label.
	 */
	private const AGGREGATION_LABEL = ['archiefstuk' => 'Archiefstuk', 'dossier' => 'Dossier'];

	/**
	 * The term OpenRegister writes when no use restriction is recorded (MDTO requires one).
	 */
	private const RESTRICTION_UNRECORDED = 'Nader te bepalen';

	/**
	 * Constructor.
	 *
	 * @param MdtoXmlWriter $writer The MDTO element primitives.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function __construct(
		private readonly MdtoXmlWriter $writer=new MdtoXmlWriter(),
	) {

	}//end __construct()

	/**
	 * The MDTO sidecar of a record as XML.
	 *
	 * @param MdtoSidecarInput $input The record, its supplement, files, members and organisation.
	 *
	 * @return string The XML document.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function buildXml(MdtoSidecarInput $input): string {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->formatOutput = true;

		$root = $this->writer->root(dom: $dom, schemaLocation: self::SCHEMA_LOCATION);

		$this->addInformatieobject(parent: $root, input: $input);
		foreach ($input->files as $file) {
			$this->addBestand(parent: $root, file: $file, input: $input);
		}

		$xml = $dom->saveXML();
		if ($xml === false) {
			throw new RuntimeException(message: 'The MDTO sidecar could not be serialised.');
		}

		return $xml;

	}//end buildXml()

	/**
	 * The lossless JSON projection of the sidecar: every element by name, in
	 * document order, a repeated element as a list.
	 *
	 * @param MdtoSidecarInput $input The record, its supplement, files, members and organisation.
	 *
	 * @return array<string, mixed> The projection, rooted at `MDTO`.
	 *
	 * @spec openspec/changes/archive/2026-11-25-tmlo-mdto-metadata/specs/tmlo-mdto-metadata/spec.md#requirement-deterministic-mdto-sidecar-per-document-and-dossier-req-ddtmm-005
	 */
	public function buildJson(MdtoSidecarInput $input): array {
		$dom = new DOMDocument();
		$dom->loadXML($this->buildXml(input: $input));

		return ['MDTO' => self::project(element: $dom->documentElement)];

	}//end buildJson()

	/**
	 * The informatieobject, in the MDTO-XML 1.0.1 element order.
	 *
	 * @param DOMElement       $parent The MDTO root.
	 * @param MdtoSidecarInput $input  The input.
	 *
	 * @return void
	 */
	private function addInformatieobject(DOMElement $parent, MdtoSidecarInput $input): void {
		$object     = $this->writer->element(parent: $parent, name: 'informatieobject');
		$retention  = $input->retention();
		$supplement = $input->supplement;

		$this->writer->identificatie(parent: $object, name: 'identificatie', kenmerk: $input->uuid(), bron: $input->organisationId);
		$this->writer->text(parent: $object, name: 'naam', content: $input->name());
		$level = (string) ($supplement['aggregatieniveau'] ?? '');
		if (isset(self::AGGREGATION_LABEL[$level]) === true) {
			$this->writer->begrip(parent: $object, name: 'aggregatieniveau', label: self::AGGREGATION_LABEL[$level], list: 'Aggregatieniveaus');
		}

		$classification = self::textOf(value: ($retention['classificatie'] ?? null));
		if ($classification !== null) {
			$this->writer->begrip(parent: $object, name: 'classificatie', label: $classification, list: 'Classificatieschema');
		}

		$this->writer->optionalText(parent: $object, name: 'omschrijving', content: self::textOf(value: ($supplement['omschrijving'] ?? null)));
		$this->addCoverage(parent: $object, supplement: $supplement);
		$this->writer->optionalText(parent: $object, name: 'taal', content: (self::textOf(value: ($supplement['taal'] ?? null)) ?? 'nl'));
		$this->addAppraisal(parent: $object, retention: $retention);

		foreach ($input->members as $member) {
			$this->writer->verwijzing(
				parent: $object,
				name: 'bevatOnderdeel',
				naam: (string) (self::textOf(value: ($member['title'] ?? ($member['naam'] ?? null))) ?? ''),
				kenmerk: (string) ($member['uuid'] ?? ''),
				bron: $input->organisationId
			);
		}

		foreach ($input->files as $file) {
			$this->writer->verwijzing(
				parent: $object,
				name: 'heeftRepresentatie',
				naam: (string) $file['name'],
				kenmerk: strtolower((string) $file['checksum']),
				bron: 'SHA-256'
			);
		}

		$this->addArchiefvormer(parent: $object, input: $input);
		$this->addParties(parent: $object, supplement: $supplement);
		$this->addRestrictions(parent: $object, supplement: $supplement);

	}//end addInformatieobject()

	/**
	 * The coverage in time, when the supplement holds a start date.
	 *
	 * @param DOMElement           $parent     The informatieobject.
	 * @param array<string, mixed> $supplement The supplement.
	 *
	 * @return void
	 */
	private function addCoverage(DOMElement $parent, array $supplement): void {
		$start = self::textOf(value: ($supplement['dekkingInTijdBegin'] ?? null));
		if ($start === null) {
			return;
		}

		$coverage = $this->writer->element(parent: $parent, name: 'dekkingInTijd');
		$this->writer->begrip(parent: $coverage, name: 'dekkingInTijdType', label: 'Inhoud', list: 'DekkingInTijdType');
		$this->writer->text(parent: $coverage, name: 'dekkingInTijdBegindatum', content: $start);
		$end = self::textOf(value: ($supplement['dekkingInTijdEind'] ?? null));
		$this->writer->optionalText(parent: $coverage, name: 'dekkingInTijdEinddatum', content: $end);

	}//end addCoverage()

	/**
	 * Waardering, bewaartermijn and informatiecategorie from the retention block.
	 *
	 * @param DOMElement           $parent    The informatieobject.
	 * @param array<string, mixed> $retention The retention block.
	 *
	 * @return void
	 */
	private function addAppraisal(DOMElement $parent, array $retention): void {
		$appraisal = (string) ($retention['archiefnominatie'] ?? '');
		if (isset(self::APPRAISAL_MAP[$appraisal]) === true) {
			[$code, $label] = self::APPRAISAL_MAP[$appraisal];
			$this->writer->begrip(parent: $parent, name: 'waardering', label: $label, list: 'Waarderingen', code: $code);
		}

		$period = self::textOf(value: ($retention['bewaartermijn'] ?? null));
		$end    = self::textOf(value: ($retention['archiefactiedatum'] ?? null));
		if ($period !== null || $end !== null) {
			$term = $this->writer->element(parent: $parent, name: 'bewaartermijn');
			$this->writer->optionalText(parent: $term, name: 'termijnLooptijd', content: $period);
			$this->writer->optionalText(parent: $term, name: 'termijnEinddatum', content: $end);
		}

		$category = self::textOf(value: ($retention['vernietigingsCategorie'] ?? null));
		if ($category !== null) {
			$list = (self::textOf(value: ($retention['selectielijstBron'] ?? null)) ?? 'Selectielijst');
			$this->writer->begrip(parent: $parent, name: 'informatiecategorie', label: $category, list: $list);
		}

	}//end addAppraisal()

	/**
	 * The archiefvormer: the supplement's override, else the organisation.
	 *
	 * @param DOMElement       $parent The informatieobject.
	 * @param MdtoSidecarInput $input  The input.
	 *
	 * @return void
	 */
	private function addArchiefvormer(DOMElement $parent, MdtoSidecarInput $input): void {
		$override = self::textOf(value: ($input->supplement['archiefvormerOverride'] ?? null));
		if ($override !== null) {
			$this->writer->verwijzing(parent: $parent, name: 'archiefvormer', naam: $override, kenmerk: '', bron: '');
			return;
		}

		$this->writer->verwijzing(
			parent: $parent,
			name: 'archiefvormer',
			naam: $input->organisationName,
			kenmerk: $input->organisationId,
			bron: 'OpenRegister'
		);

	}//end addArchiefvormer()

	/**
	 * One betrokkene per party: its role and its reference, never a name in clear.
	 *
	 * @param DOMElement           $parent     The informatieobject.
	 * @param array<string, mixed> $supplement The supplement.
	 *
	 * @return void
	 */
	private function addParties(DOMElement $parent, array $supplement): void {
		foreach ((array) ($supplement['betrokkene'] ?? []) as $party) {
			$reference = self::textOf(value: ($party['naamRef'] ?? null));
			if (is_array($party) === false || $reference === null) {
				continue;
			}

			$element = $this->writer->element(parent: $parent, name: 'betrokkene');
			$role    = (self::textOf(value: ($party['rol'] ?? null)) ?? 'betrokkene');
			$this->writer->begrip(parent: $element, name: 'betrokkeneTypeRelatie', label: $role, list: 'Relatietypen betrokkenen');
			$this->writer->verwijzing(parent: $element, name: 'betrokkeneActor', naam: $reference, kenmerk: $reference, bron: 'Filinq');
		}

	}//end addParties()

	/**
	 * One beperkingGebruik per confirmed restriction; the unrecorded term when there is none.
	 *
	 * @param DOMElement           $parent     The informatieobject.
	 * @param array<string, mixed> $supplement The supplement.
	 *
	 * @return void
	 */
	private function addRestrictions(DOMElement $parent, array $supplement): void {
		$confirmed = array_values(
			array_filter(
				(array) ($supplement['beperkingGebruik'] ?? []),
				static fn (mixed $entry): bool => is_array($entry) === true
					&& ($entry['proposed'] ?? false) !== true
					&& self::textOf(value: ($entry['type'] ?? null)) !== null
			)
		);
		if ($confirmed === []) {
			$element = $this->writer->element(parent: $parent, name: 'beperkingGebruik');
			$this->writer->begrip(parent: $element, name: 'beperkingGebruikType', label: self::RESTRICTION_UNRECORDED, list: 'BeperkingGebruikTypeLijst');
			return;
		}

		foreach ($confirmed as $entry) {
			$element = $this->writer->element(parent: $parent, name: 'beperkingGebruik');
			$this->writer->begrip(parent: $element, name: 'beperkingGebruikType', label: (string) $entry['type'], list: 'BeperkingGebruikTypeLijst');
			$described = array_filter([self::textOf(value: ($entry['grondslag'] ?? null)), self::textOf(value: ($entry['omschrijving'] ?? null))]);
			if ($described !== []) {
				$this->writer->text(parent: $element, name: 'beperkingGebruikNadereBeschrijving', content: implode(' — ', $described));
			}

			$until = self::textOf(value: ($entry['einddatum'] ?? null));
			if ($until !== null) {
				$term = $this->writer->element(parent: $element, name: 'beperkingGebruikTermijn');
				$this->writer->text(parent: $term, name: 'termijnEinddatum', content: $until);
			}
		}

	}//end addRestrictions()

	/**
	 * One bestand: identificatie by checksum, name, size, format, checksum and what it represents.
	 *
	 * @param DOMElement           $parent The MDTO root.
	 * @param array<string, mixed> $file   The file facts.
	 * @param MdtoSidecarInput     $input  The input.
	 *
	 * @return void
	 */
	private function addBestand(DOMElement $parent, array $file, MdtoSidecarInput $input): void {
		$bestand  = $this->writer->element(parent: $parent, name: 'bestand');
		$checksum = strtolower((string) $file['checksum']);
		$type     = strtolower(trim(explode(';', (string) $file['format'])[0]));
		$slash    = strpos($type, '/');
		$label    = $type;
		if ($slash !== false) {
			$label = substr($type, ($slash + 1));
		}

		$this->writer->identificatie(parent: $bestand, name: 'identificatie', kenmerk: $checksum, bron: 'SHA-256');
		$this->writer->text(parent: $bestand, name: 'naam', content: (string) $file['name']);
		$this->writer->text(parent: $bestand, name: 'omvang', content: (string) (int) $file['size']);
		$this->writer->begrip(parent: $bestand, name: 'bestandsformaat', label: $label, list: 'IANA Media types', code: $type);

		$sum = $this->writer->element(parent: $bestand, name: 'checksum');
		$this->writer->begrip(parent: $sum, name: 'checksumAlgoritme', label: 'SHA-256', list: 'Begrippenlijst ChecksumAlgoritme MDTO');
		$this->writer->text(parent: $sum, name: 'checksumWaarde', content: $checksum);
		$this->writer->text(parent: $sum, name: 'checksumDatum', content: (string) $file['checksumDate']);

		$this->writer->verwijzing(
			parent: $bestand,
			name: 'isRepresentatieVan',
			naam: $input->name(),
			kenmerk: $input->uuid(),
			bron: $input->organisationId
		);

	}//end addBestand()

	/**
	 * One element as JSON: its text, or its children by local name.
	 *
	 * @param DOMElement $element The element.
	 *
	 * @return array<string, mixed>|string
	 */
	private static function project(DOMElement $element): array|string {
		$children = [];
		foreach ($element->childNodes as $child) {
			if ($child instanceof DOMElement === true) {
				$children[] = $child;
			}
		}

		if ($children === []) {
			return $element->textContent;
		}

		$projected = [];
		foreach ($children as $child) {
			$projected[$child->localName][] = self::project(element: $child);
		}

		return array_map(static fn (array $values): mixed => self::single(values: $values), $projected);

	}//end project()

	/**
	 * A one-element list as its element, a longer list as it is.
	 *
	 * @param array<int, mixed> $values The values of one element name.
	 *
	 * @return mixed
	 */
	private static function single(array $values): mixed {
		if (count($values) === 1) {
			return $values[0];
		}

		return $values;

	}//end single()

	/**
	 * A trimmed, non-blank text, or null.
	 *
	 * @param mixed $value The value.
	 *
	 * @return string|null
	 */
	private static function textOf(mixed $value): ?string {
		if (is_string($value) === false || trim($value) === '') {
			return null;
		}

		return trim($value);

	}//end textOf()

}//end class
