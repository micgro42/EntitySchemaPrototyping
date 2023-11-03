<?php

declare( strict_types = 1 );

namespace EntitySchema\DataAccess;

use EntitySchema\MediaWiki\Content\EntitySchemaContent;
use EntitySchema\Services\Converter\EntitySchemaConverter;
use EntitySchema\Services\Converter\FullViewEntitySchemaData;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Title\TitleFactory;
use Wikibase\DataModel\Entity\PseudoEntityId;
use Wikibase\DataModel\Services\Lookup\PseudoTermLookup;

/**
 * TODO: We may wish to have some form of caching somewhere.
 *       Probably at least for the content of loadSchemaData.
 *
 * @license GPL-2.0-or-later
 */
class EntitySchemaTermLookup implements PseudoTermLookup {

	private WikiPageFactory $wikiPageFactory;
	private TitleFactory $titleFactory;

	public function __construct(
		TitleFactory $titleFactory,
		WikiPageFactory $wikiPageFactory
	) {
		$this->titleFactory = $titleFactory;
		$this->wikiPageFactory = $wikiPageFactory;
	}

	public function getLabel( PseudoEntityId $entityId, string $languageCode ): ?string {
		$schemaData = $this->loadSchemaData( $entityId );

		if ( $schemaData === null ) {
			return null;
		}

		if ( !isset( $schemaData->nameBadges[$languageCode] ) ) {
			return null;
		}

		$nameBadge = $schemaData->nameBadges[$languageCode];

		if ( $nameBadge->label === '' ) {
			return null;
		}

		return $nameBadge->label;
	}

	public function getLabels( PseudoEntityId $entityId, array $languageCodes ): array {
		$schemaData = $this->loadSchemaData( $entityId );

		if ( $schemaData === null ) {
			return [];
		}

		$labels = [];

		foreach ( $languageCodes as $languageCode ) {
			if ( !isset( $schemaData->nameBadges[$languageCode] ) ) {
				continue;
			}

			$nameBadge = $schemaData->nameBadges[$languageCode];

			if ( $nameBadge->label === '' ) {
				continue;
			}

			$labels[$languageCode] = $nameBadge->label;
		}

		return $labels;
	}

	public function getDescription( PseudoEntityId $entityId, string $languageCode ): ?string {
		$schemaData = $this->loadSchemaData( $entityId );

		if ( $schemaData === null ) {
			return null;
		}

		if ( !isset( $schemaData->nameBadges[$languageCode] ) ) {
			return null;
		}

		$nameBadge = $schemaData->nameBadges[$languageCode];

		if ( $nameBadge->description === '' ) {
			return null;
		}

		return $nameBadge->description;
	}

	public function getDescriptions( PseudoEntityId $entityId, array $languageCodes ): array {
		$schemaData = $this->loadSchemaData( $entityId );

		if ( $schemaData === null ) {
			return [];
		}

		$labels = [];

		foreach ( $languageCodes as $languageCode ) {
			if ( !isset( $schemaData->nameBadges[$languageCode] ) ) {
				continue;
			}

			$nameBadge = $schemaData->nameBadges[$languageCode];

			if ( $nameBadge->description === '' ) {
				continue;
			}

			$labels[$languageCode] = $nameBadge->description;
		}

		return $labels;
	}

	private function loadSchemaData( PseudoEntityId $entityId ): ?FullViewEntitySchemaData {
		$entitySchemaId = $entityId->getSerialization();
		$schemaPageIdentity = $this->titleFactory->newFromText( $entitySchemaId, NS_ENTITYSCHEMA_JSON );
		if ( $schemaPageIdentity === null ) {
			return null;
		}
		$schemaPage = $this->wikiPageFactory->newFromTitle( $schemaPageIdentity );
		$content = $schemaPage->getContent();
		if ( !( $content instanceof EntitySchemaContent ) ) {
			return null;
		}
		$schema = $content->getText();

		$converter = new EntitySchemaConverter();
		$schemaData = $converter->getFullViewSchemaData( $schema, [] );
		return $schemaData;
	}
}
