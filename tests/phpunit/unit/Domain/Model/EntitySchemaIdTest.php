<?php

declare( strict_types = 1 );

namespace EntitySchema\Tests\Unit\Domain\Model;

use EntitySchema\Domain\Model\EntitySchemaId;
use InvalidArgumentException;
use MediaWikiUnitTestCase;
use Wikibase\DataModel\Entity\Int32EntityId;

/**
 * @covers EntitySchema\Domain\Model\EntitySchemaId
 *
 * @license GPL-2.0-or-later
 */
class EntitySchemaIdTest extends MediaWikiUnitTestCase {

	public function testConstructorAndGetter(): void {
		$expected = 'E1';

		$entitySchemaId = new EntitySchemaId( $expected );
		$actual = $entitySchemaId->getId();

		$this->assertSame( $expected, $actual );
	}

	public function testSerializationRoundtripEquals(): void {
		$id = new EntitySchemaId( 'E' . Int32EntityId::MAX );
		$this->assertTrue(
			$id->equals( unserialize( serialize( $id ) ) ),
			'EntitySchemaId should equal itself after serialization round-trip'
		);
	}

	public static function provideInvalidIds(): iterable {
		yield 'missing prefix' => [ '1' ];
		yield 'missing number' => [ 'E' ];
		yield 'malformed number' => [ 'E01' ];
		yield 'too large number (2^31)' => [ 'E2147483648' ];
		yield 'too large number (2^65)' => [ 'E36893488147419103232' ];
		yield 'trailing newline' => [ "E1\n" ];
		yield 'extra whitespace' => [ ' E1 ' ];
		yield 'sub-ID' => [ 'E1-R1' ];
		yield 'local repository' => [ ':E1' ]; // this is not a Wikibase entity (ID),
		yield 'foreign repository' => [ 'other:E1' ]; // federation is not supported
	}

	/**
	 * @dataProvider provideInvalidIds
	 */
	public function testConstructorRejectsInvalidId( string $id ): void {
		$this->expectException( InvalidArgumentException::class );
		new EntitySchemaId( $id );
	}

}
