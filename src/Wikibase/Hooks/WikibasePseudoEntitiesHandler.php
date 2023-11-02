<?php

declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use EntitySchema\Domain\Model\EntitySchemaId;

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
}
