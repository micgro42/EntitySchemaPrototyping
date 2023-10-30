<?php

declare( strict_types = 1 );

namespace EntitySchema\Domain\Model;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\PseudoEntityId;

/**
 * @license GPL-2.0-or-later
 */
class EntitySchemaId implements PseudoEntityId, Int32EntityId {

	private string $id;

	public const PATTERN = '/^E[1-9][0-9]*\z/';

	public function __construct( string $id ) {
		if ( !preg_match( self::PATTERN, $id ) ) {
			throw new InvalidArgumentException( 'ID must match ' . self::PATTERN );
		}

		if ( strlen( $id ) > 10 && substr( $id, 1 ) > Int32EntityId::MAX ) {
			throw new InvalidArgumentException( 'ID can not exceed ' . Int32EntityId::MAX );
		}

		$this->id = $id;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getEntityType() {
		return 'entityschema';
	}

	public function getSerialization() {
		return $this->id;
	}

	public function __toString() {
		return $this->id;
	}

	public function equals( $target ) {
		if ( $this === $target ) {
			return true;
		}

		return $target instanceof self
			&& $target->id === $this->id;
	}

	public function __serialize(): array {
		return [ 'serialization' => $this->id ];
	}

	public function __unserialize( array $data ) {
		$this->__construct( $data['serialization'] );
		if ( $this->id !== $data['serialization'] ) {
			throw new InvalidArgumentException( '$data contained invalid serialization' );
		}
	}

	public function getNumericId() {
		return (int)substr( $this->id, 1 );
	}
}
