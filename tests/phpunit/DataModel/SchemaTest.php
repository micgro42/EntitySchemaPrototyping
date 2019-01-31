<?php

namespace Wikibase\Schema\Tests\DataModel;

use MediaWikiTestCase;
use Wikibase\Schema\DataModel\Schema;

/**
 * Class SchemaTest
 *
 * @covers \Wikibase\Schema\DataModel\Schema
 */
class SchemaTest extends MediaWikiTestCase {

	public function testEmptySchemaHasDefaults() {
		$schema = new Schema();

		$this->assertSame( '', $schema->getLabel( 'en' )->getText() );
		$this->assertSame( '', $schema->getDescription( 'en' )->getText() );
		$this->assertSame( [], $schema->getAliasGroup( 'en' )->getAliases() );
		$this->assertSame( '', $schema->getSchema() );
	}

	public function testSettingAndRetrieving() {
		$testShEx = <<<'SCHEMA'
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
PREFIX wd: <http://www.wikidata.org/entity/>

:human {
  wdt:P31 [wd:Q5]
}
SCHEMA;

		$schema = new Schema();

		$schema->setLabel( 'en', 'testlabel' );
		$schema->setDescription( 'en', 'testDescription' );
		$schema->setAliases( 'en', [ 'testlabel', 'foobar' ] );
		$schema->setSchema( $testShEx );

		$this->assertSame( 'testlabel', $schema->getLabel( 'en' )->getText() );
		$this->assertSame( 'testDescription', $schema->getDescription( 'en' )->getText() );
		$this->assertSame( [ 'testlabel', 'foobar' ], $schema->getAliasGroup( 'en' )->getAliases() );
		$this->assertSame( $testShEx, $schema->getSchema() );
	}

}
