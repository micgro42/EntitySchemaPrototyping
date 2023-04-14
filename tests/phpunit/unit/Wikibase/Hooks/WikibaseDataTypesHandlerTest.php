<?php

declare( strict_types = 1 );

namespace phpunit\unit\Wikibase\Hooks;

use DataValues\StringValue;
use EntitySchema\Wikibase\Formatters\EntitySchemaFormatter;
use EntitySchema\Wikibase\Hooks\WikibaseDataTypesHandler;
use EntitySchema\Wikibase\Validators\EntitySchemaExistsValidator;
use HashConfig;
use MediaWiki\Linker\LinkRenderer;
use MediaWikiUnitTestCase;
use ValueValidators\Result;
use Wikibase\Repo\ValidatorBuilders;
use Wikibase\Repo\Validators\CompositeValidator;

/**
 * @covers \EntitySchema\Wikibase\Hooks\WikibaseDataTypesHandler
 * @license GPL-2.0-or-later
 */
class WikibaseDataTypesHandlerTest extends MediaWikiUnitTestCase {

	public function testOnWikibaseRepoDataTypes(): void {
		$settings = new HashConfig( [
			'EntitySchemaEnableDatatype' => true,
		] );
		$stubLinkRenderer = $this->createStub( LinkRenderer::class );
		$stubValidatorBuilders = $this->createStub( ValidatorBuilders::class );
		$stubExistsValidator = $this->createStub( EntitySchemaExistsValidator::class );

		$sut = new WikibaseDataTypesHandler(
			$stubLinkRenderer,
			$settings,
			$stubValidatorBuilders,
			$stubExistsValidator
		);

		$dataTypeDefinitions = [ 'PT:wikibase-item' => [] ];
		$sut->onWikibaseRepoDataTypes( $dataTypeDefinitions );

		$this->assertArrayHasKey( 'PT:wikibase-item', $dataTypeDefinitions );
		$this->assertArrayHasKey( 'PT:entity-schema', $dataTypeDefinitions );
		$this->assertInstanceOf(
			EntitySchemaFormatter::class,
			$dataTypeDefinitions['PT:entity-schema']['formatter-factory-callback']( 'html' )
		);
	}

	public function testOnWikibaseRepoDataTypesDoesNothingWhenDisabled(): void {
		$settings = new HashConfig( [
			'EntitySchemaEnableDatatype' => false,
		] );
		$stubLinkRenderer = $this->createStub( LinkRenderer::class );
		$stubValidatorBuilders = $this->createStub( ValidatorBuilders::class );
		$stubExistsValidator = $this->createStub( EntitySchemaExistsValidator::class );

		$sut = new WikibaseDataTypesHandler(
			$stubLinkRenderer,
			$settings,
			$stubValidatorBuilders,
			$stubExistsValidator
		);

		$dataTypeDefinitions = [ 'PT:wikibase-item' => [] ];
		$sut->onWikibaseRepoDataTypes( $dataTypeDefinitions );

		$this->assertSame( [ 'PT:wikibase-item' => [] ], $dataTypeDefinitions );
	}

	/**
	 * Basic test for validating an EntitySchema ID value.
	 * Further test cases, especially invalid ones, require integration with Wikibase
	 * (instead of stubbing ValidatorBuilders) and are tested in {@link EntitySchemaDataValidatorTest}.
	 *
	 * @dataProvider provideValuesWithValidity
	 */
	public function testOnWikibaseRepoDataTypesValidator(
		string $value,
		Result $existenceResult,
		bool $isValid
	): void {
		$settings = new HashConfig( [
			'EntitySchemaEnableDatatype' => true,
		] );
		$stubLinkRenderer = $this->createStub( LinkRenderer::class );
		$validatorBuilders = $this->createConfiguredMock( ValidatorBuilders::class, [
			'buildStringValidators' => [],
		] );
		$stubExistsValidator = $this->createStub( EntitySchemaExistsValidator::class );
		$stubExistsValidator->method( 'validate' )
			->willReturn( $existenceResult );
		$handler = new WikibaseDataTypesHandler(
			$stubLinkRenderer,
			$settings,
			$validatorBuilders,
			$stubExistsValidator
		);
		$dataTypeDefinitions = [];
		$handler->onWikibaseRepoDataTypes( $dataTypeDefinitions );
		$validator = new CompositeValidator(
			$dataTypeDefinitions['PT:entity-schema']['validator-factory-callback']()
		);

		$result = $validator->validate( new StringValue( $value ) );

		$this->assertSame( $isValid, $result->isValid() );
	}

	public static function provideValuesWithValidity(): iterable {
		yield 'valid, existing' => [ 'E1', Result::newSuccess(), true ];
		yield 'invalid, no pattern match' => [ '', Result::newError( [] ), false ];
		yield 'invalid, does not exist' => [ 'E1', Result::newError( [] ), false ];
	}

}
