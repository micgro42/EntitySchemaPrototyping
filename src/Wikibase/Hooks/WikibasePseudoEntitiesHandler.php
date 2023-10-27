<?php

declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use EntitySchema\Domain\Model\EntitySchemaId;
use InvalidArgumentException;
use MediaWiki\Title\Title;

/**
 * @license GPL-2.0-or-later
 */
class WikibasePseudoEntitiesHandler {

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public function onWikibasePseudoEntities_PseudoEntityIdParser( array &$pseudoEntityParsers ): void {
		$pseudoEntityParsers[EntitySchemaId::PATTERN] = static function ( $idSerialization ): EntitySchemaId {
			return new EntitySchemaId( $idSerialization );
		};
	}

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
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
