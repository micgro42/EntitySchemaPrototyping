<?php

declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Validators;

use EntitySchema\Wikibase\DataValueEntitySchemaIdExtractor;
use MediaWiki\Title\TitleFactory;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * @license GPL-2.0-or-later
 */
class EntitySchemaExistsValidator implements ValueValidator {

	private TitleFactory $titleFactory;

	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/** @inheritDoc */
	public function validate( $value ): Result {
		$id = DataValueEntitySchemaIdExtractor::extract( $value )->getSerialization();

		$title = $this->titleFactory->makeTitleSafe( NS_ENTITYSCHEMA_JSON, $id );
		if ( $title !== null && $title->exists() ) {
			return Result::newSuccess();
		} else {
			return Result::newError( [
				Error::newError(
					'EntitySchema not found: ' . $id,
					null,
					'no-such-entity-schema',
					[ $id ]
				),
			] );
		}
	}

}
