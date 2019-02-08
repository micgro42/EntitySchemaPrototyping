'use strict';

const Page = require( 'wdio-mediawiki/Page' );

class NewSchemaPage extends Page {
	static get NEW_SCHEMA_SELECTORS() {
		return {
			LABEL: '#wbschema-newschema-label',
			DESCRIPTION: '#wbschema-newschema-description',
			ALIASES: '#wbschema-newschema-aliases',
			SCHEMA_SHEXC: '#wbschema-newschema-schema-shexc',
			SUBMIT_BUTTON: '#wbschema-newschema-submit'
		};
	}

	open() {
		super.openTitle( 'Special:NewSchema' );
	}
	get schemaSubmitButton() {
		return browser.element( this.constructor.NEW_SCHEMA_SELECTORS.SUBMIT_BUTTON );
	}

	showsForm() {
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.LABEL ).waitForVisible();
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.DESCRIPTION ).waitForVisible();
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.ALIASES ).waitForVisible();
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.SCHEMA_SHEXC ).waitForVisible();

		return true;
	}

	setLabel( label ) {
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.LABEL + ' input' ).setValue( label );
	}

	setDescription( description ) {
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.DESCRIPTION + ' input' ).setValue( description );
	}

	setAliases( aliases ) {
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.ALIASES + ' input' ).setValue( aliases );
	}

	setShExC( ShExC ) {
		browser.$( this.constructor.NEW_SCHEMA_SELECTORS.SCHEMA_SHEXC + ' textarea' ).setValue( ShExC );
	}

	/**
	 * Inserts the ShExC via javascript/jQuery instead of "typing" it
	 *
	 * This method enables inserting the tab character
	 *
	 * @param {string} ShExC
	 */
	pasteShExC( ShExC ) {
		browser.executeAsync( ( query, ShExC, done ) => {
			done( window.$( query ).val( ShExC ) );
		}, this.constructor.NEW_SCHEMA_SELECTORS.SCHEMA_SHEXC + ' textarea', ShExC );
	}

	clickSubmit() {
		this.schemaSubmitButton.click();
	}
}

module.exports = new NewSchemaPage();
