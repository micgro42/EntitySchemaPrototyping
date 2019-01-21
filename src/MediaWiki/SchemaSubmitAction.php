<?php

namespace Wikibase\Schema\MediaWiki;

/**
 * Action to handle a submitted Wikibase Schema page
 */
class SchemaSubmitAction extends SchemaEditAction {

	public function getName() {
		return 'submit';
	}

}
