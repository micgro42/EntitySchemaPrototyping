<?php
declare( strict_types = 1 );

namespace EntitySchema\Wikibase;

use DataValues\DataValue;
use DataValues\StringValue;
use EntitySchema\Domain\Model\EntitySchemaId;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * @license GPL-2.0-or-later
 */
class DataValueEntitySchemaIdExtractor {

	public static function extract( DataValue $value ): EntitySchemaId {
		if ( $value instanceof EntityIdValue ) {
			$entityId = $value->getEntityId();
			if ( $entityId instanceof EntitySchemaId ) {
				return $entityId;
			} else {
				throw new InvalidArgumentException(
					'Wrong EntityId type: ' . get_class( $entityId )
				);
			}
		} elseif ( $value instanceof StringValue ) {
			return new EntitySchemaId( $value->getValue() );
		} else {
			throw new InvalidArgumentException(
				'Wrong DataValue type: ' . get_class( $value )
			);
		}
	}

}
