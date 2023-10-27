<?php
declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use EntitySchema\Domain\Model\EntitySchemaId;
use LinkBatch;
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

	// phpcs:enable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

}
