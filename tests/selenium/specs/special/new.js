'use strict';

const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api.js' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage.js' ),
	NewSchemaPage = require( '../../pageobjects/newschema.page' ),
	SchemaPage = require( '../../pageobjects/schema.page' );

describe( 'NewSchema:Page', () => {

	it( 'request with "createpage" right shows form', () => {
		NewSchemaPage.open();

		assert.ok( NewSchemaPage.showsForm() );
	} );

	describe( 'FIXME', () => {
		it( 'is possible to create a new schema', () => {
			NewSchemaPage.open();
			NewSchemaPage.setLabel( 'Testlabel' );
			NewSchemaPage.setDescription( 'A schema created with selenium browser tests' );
			NewSchemaPage.setAliases( 'Testschema |Schema created by test' );
			NewSchemaPage.setShExC( '<empty> {}' );
			NewSchemaPage.clickSubmit();

			// fixme ensure that created page is located in the Schema namespace!
			const actualLabel = SchemaPage.getLabel(),
				actualDescription = SchemaPage.getDescription(),
				actualAliases = SchemaPage.getAliases(),
				actualShExC = SchemaPage.getShExC();
			assert.equal( 'Testlabel', actualLabel );
			assert.equal( 'A schema created with selenium browser tests', actualDescription );
			assert.equal( 'Testschema | Schema created by test', actualAliases );
			assert.equal( '<empty> {}', actualShExC );
		} );
	} );

	describe( 'when blocked', () => {

		/** necessary to translate between regular promises and WebdriverIO's magic concurrency */
		function blockUser() {
			let blocked = false;
			Api.blockUser().then( () => {
				blocked = true;
			} );
			browser.waitUntil( () => blocked );
		}

		afterEach( () => Api.unblockUser() );

		it( 'cannot load form', () => {
			blockUser();

			LoginPage.loginAdmin();
			NewSchemaPage.open();

			$( '#mw-returnto' ).waitForVisible();
			assert.strictEqual( $( '#firstHeading' ).getText(), 'User is blocked' );
		} );

		it( 'cannot submit form', () => {
			LoginPage.loginAdmin();
			NewSchemaPage.open();
			NewSchemaPage.setLabel( 'evil schema' );
			NewSchemaPage.setDescription( 'should not be able to create this schema' );

			blockUser();

			NewSchemaPage.clickSubmit();

			$( '#mw-returnto' ).waitForVisible();
			assert.strictEqual( $( '#firstHeading' ).getText(), 'User is blocked' );
		} );
	} );

} );
