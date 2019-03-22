'use strict';

const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api.js' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage.js' ),
	NewSchemaPage = require( '../../pageobjects/newschema.page' ),
	SetSchemaLabelDescriptionAliasesPage = require( '../../pageobjects/setlabeldecriptionaliases.schema.page.js' ),
	ViewSchemaPage = require( '../../pageobjects/view.schema.page' );

describe( 'SetSchemaLabelDescriptionAliasesPage:Page', () => {

	beforeEach( 'create new schema page and open', () => {
		NewSchemaPage.open();
		NewSchemaPage.showsForm();
		NewSchemaPage.setLabel( 'Test Label' );
		NewSchemaPage.clickSubmit();
	} );

	it( 'request shows first form', () => {
		SetSchemaLabelDescriptionAliasesPage.open();
		assert.ok( SetSchemaLabelDescriptionAliasesPage.showsForm() );
	} );

	it( 'shows a schema set label, description aliases form and submit button', () => {
		let id = ViewSchemaPage.getId();
		SetSchemaLabelDescriptionAliasesPage.open();
		SetSchemaLabelDescriptionAliasesPage.schemaSubmitButton.waitForVisible();
	} );

	it( 'is possible to get Schema identifying information', () => {

		let id = ViewSchemaPage.getId();
		SetSchemaLabelDescriptionAliasesPage.open();
		SetSchemaLabelDescriptionAliasesPage.setIdField( id );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();

		assert.ok( SetSchemaLabelDescriptionAliasesPage.showsEditForm() );

	} );

	it( 'is possible to edit Schema identifying information', () => {

		let id = ViewSchemaPage.getId();
		SetSchemaLabelDescriptionAliasesPage.open();
		SetSchemaLabelDescriptionAliasesPage.setIdField( id );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();
		SetSchemaLabelDescriptionAliasesPage.setDescription( 'This is a test description' );
		SetSchemaLabelDescriptionAliasesPage.setAliases( 'Alias1 | Alias2' );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();

		assert.strictEqual( ViewSchemaPage.getDescription(), 'This is a test description' );
		assert.strictEqual( ViewSchemaPage.getAliases(), 'Alias1 | Alias2' );
	} );

	it( 'is possible to edit Schema in another language', () => {

		let id = ViewSchemaPage.getId(),
			langCode = 'de';
		SetSchemaLabelDescriptionAliasesPage.open();
		SetSchemaLabelDescriptionAliasesPage.setIdField( id );
		SetSchemaLabelDescriptionAliasesPage.setLanguageField( langCode );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();
		SetSchemaLabelDescriptionAliasesPage.setDescription( 'Dies ist eine deutsche Testbeschreibung' );
		SetSchemaLabelDescriptionAliasesPage.setAliases( 'Alias1 | Alias2' );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();

		assert.strictEqual( ViewSchemaPage.getDescription( langCode ), 'Dies ist eine deutsche Testbeschreibung' );
		assert.strictEqual( ViewSchemaPage.getAliases( langCode ), 'Alias1 | Alias2' );
		assert.strictEqual( ViewSchemaPage.getLabel(), 'Test Label' );
	} );

	it( 'detects an edit conflict when re-submitting the same form', () => {
		let id = ViewSchemaPage.getId();
		SetSchemaLabelDescriptionAliasesPage.open();
		SetSchemaLabelDescriptionAliasesPage.setIdField( id );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();
		SetSchemaLabelDescriptionAliasesPage.setLabel( 'edit conflict label 1' );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();

		browser.back();
		SetSchemaLabelDescriptionAliasesPage.setLabel( 'edit conflict label 2' );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();

		assert.ok( SetSchemaLabelDescriptionAliasesPage.showsEditForm() );

		ViewSchemaPage.open( id );
		assert.strictEqual( ViewSchemaPage.getLabel(), 'edit conflict label 1' );
	} );

	it( 'limits the input length', () => {
		let id = ViewSchemaPage.getId(),
			schemaNameBadgeMaxSizeChars, overlyLongString;

		SetSchemaLabelDescriptionAliasesPage.open();
		SetSchemaLabelDescriptionAliasesPage.setIdField( id );
		SetSchemaLabelDescriptionAliasesPage.clickSubmit();
		schemaNameBadgeMaxSizeChars = SetSchemaLabelDescriptionAliasesPage
			.getSchemaNameBadgeMaxSizeChars();
		overlyLongString = 'a'.repeat( schemaNameBadgeMaxSizeChars + 1 );

		SetSchemaLabelDescriptionAliasesPage.setLabel( overlyLongString );
		assert.strictEqual(
			SetSchemaLabelDescriptionAliasesPage.getLabel().length,
			schemaNameBadgeMaxSizeChars
		);

		SetSchemaLabelDescriptionAliasesPage.setDescription( overlyLongString );
		assert.strictEqual(
			SetSchemaLabelDescriptionAliasesPage.getDescription().length,
			schemaNameBadgeMaxSizeChars
		);

		SetSchemaLabelDescriptionAliasesPage.setAliases( overlyLongString );
		assert.strictEqual(
			SetSchemaLabelDescriptionAliasesPage.getAliases().length,
			schemaNameBadgeMaxSizeChars
		);

		SetSchemaLabelDescriptionAliasesPage.setAliases(
			'b' + '| '.repeat( schemaNameBadgeMaxSizeChars ) + 'c'
		);
		assert.strictEqual(
			SetSchemaLabelDescriptionAliasesPage.getAliases().length,
			schemaNameBadgeMaxSizeChars * 2 + 2,
			'Pipes and spaces will be trimmed from aliases before counting'
		);
	} );
} );
