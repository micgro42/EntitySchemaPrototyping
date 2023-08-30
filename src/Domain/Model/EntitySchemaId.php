<?php

declare( strict_types = 1 );

namespace EntitySchema\Domain\Model;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\SerializableEntityId;

/**
 * @license GPL-2.0-or-later
 */
class EntitySchemaId extends SerializableEntityId implements Int32EntityId {

	public const PATTERN = '/^E[1-9][0-9]*\z/';

	public function __construct( string $id ) {
		if ( !preg_match( self::PATTERN, $id ) ) {
			throw new InvalidArgumentException( 'ID must match ' . self::PATTERN );
		}
		if ( strlen( $id ) > 10 && substr( $id, 1 ) > Int32EntityId::MAX ) {
			throw new InvalidArgumentException( 'ID can not exceed ' . Int32EntityId::MAX );
		}

		parent::__construct( $id );
	}

	/** @deprecated use {@link self::getSerialization()} instead */
	public function getId(): string {
		return $this->serialization;
	}

	public function getNumericId(): int {
		return (int)substr( $this->serialization, 1 );
	}

	public function getEntityType(): string {
		return 'entityschema';
	}

	public function __serialize(): array {
		return [ 'serialization' => $this->serialization ];
	}

	public function __unserialize( array $data ): void {
		$this->__construct( $data['serialization'] );
		if ( $this->serialization !== $data['serialization'] ) {
			throw new InvalidArgumentException( '$data contained invalid serialization' );
		}
	}

}
