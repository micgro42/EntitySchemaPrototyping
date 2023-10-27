<?php
declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use EntitySchema\Domain\Model\EntitySchemaId;
use InvalidArgumentException;
use LinkBatch;
use MediaWiki\Title\Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;

/**
 * @license GPL-2.0-or-later
 */
class WikibasePseudoEntitiesHandler {

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	public function onWikibasePseudoEntities_ReferencedEntities_LinkBatch(
		EntityId $entityId,
		LinkBatch $linkBatch
	): bool {
		if ( $entityId instanceof EntitySchemaId ) {
			$linkBatch->add( NS_ENTITYSCHEMA_JSON, $entityId->getSerialization() );
			return false;
		}
		return true;
	}

	public function onWikibasePseudoEntities_SearchEntities_Handlers(
		array &$pseudoEntityTypeHandlers
	): void {
		$pseudoEntityTypeHandlers['entityschema'] = static function ( array $params ): array {
			try {
				$id = new EntitySchemaId( $params['search'] );
			} catch ( InvalidArgumentException $exception ) {
				return [];
			}
			$title = Title::makeTitleSafe( NS_ENTITYSCHEMA_JSON, $id ); // TODO use TitleFactory
			if ( $title->exists() ) {
				return [ [
					'id' => $id->getSerialization(),
					'title' => $title->getPrefixedText(),
					'pageid' => $title->getArticleID(),
					'display' => [], // TODO use EntitySchemaConverter::getMonolingualNameBadgeData()?
				] ];
			}
			return [];
		};
	}

	// phpcs:ignore MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic
	public function onWikibasePseudoEntities_SnakFormatter_formatSnak(
		Snak $snak,
		&$out
	): bool {
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();
			if ( $value instanceof EntityIdValue ) {
				$entityId = $value->getEntityId();
				if ( $entityId instanceof EntitySchemaId ) {
					// no usage tracking, no formatting of any kind
					$out = $entityId->getSerialization();
					return false;
				}
			}
		}
		return true;
	}

	// phpcs:ignore MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic
	public function onWikibasePseudoEntities_PseudoEntityIdParser_parse(
		string $idSerialization,
		&$out
	): bool {
		try {
			$out = new EntitySchemaId( $idSerialization );
			return false;
		} catch ( InvalidArgumentException $e ) {
			return true;
		}
	}

	// phpcs:enable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

}
