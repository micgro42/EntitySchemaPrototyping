<?php
declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use EntitySchema\Domain\Model\EntitySchemaId;
use InvalidArgumentException;
use LinkBatch;
use MediaWiki\Title\Title;
use Wikibase\DataModel\Entity\EntityId;

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

	// phpcs:enable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

}
