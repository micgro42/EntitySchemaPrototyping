<?php

namespace Wikibase\Schema\MediaWiki;

use FormAction;
use RuntimeException;
use Status;
use Wikibase\Schema\DataAccess\MediaWikiPageUpdaterFactory;
use Wikibase\Schema\DataAccess\WatchlistUpdater;
use Wikibase\Schema\Domain\Model\Schema;
use Wikibase\Schema\Domain\Model\SchemaId;
use Wikibase\Schema\MediaWiki\Content\WikibaseSchemaContent;
use Wikibase\Schema\Services\SchemaDispatcher\SchemaDispatcher;
use Wikibase\Schema\DataAccess\MediaWikiRevisionSchemaWriter;

/**
 * Edit a Wikibase Schema via the mediawiki editing action
 */
class SchemaEditAction extends FormAction {

	/**
	 * Process the form on POST submission.
	 *
	 * If you don't want to do anything with the form, just return false here.
	 *
	 * This method will be passed to the HTMLForm as a submit callback (see
	 * HTMLForm::setSubmitCallback) and must return as documented for HTMLForm::trySubmit.
	 *
	 * @see HTMLForm::setSubmitCallback()
	 * @see HTMLForm::trySubmit()
	 *
	 * @param array $data
	 *
	 * @return bool|string|array|Status Must return as documented for HTMLForm::trySubmit
	 */
	public function onSubmit( $data ) {
		/**
		 * @var $content WikibaseSchemaContent
		 */
		$content = $this->getContext()->getWikiPage()->getContent();
		if ( !$content instanceof WikibaseSchemaContent ) {
			return Status::newFatal( $this->msg( 'wikibaseschema-error-schemadeleted' ) );
		}

		$user = $this->getUser();
		$updaterFactory = new MediaWikiPageUpdaterFactory( $user );
		$id = new SchemaId( $this->getTitle()->getText() );
		$aliases = array_filter( array_map( 'trim', explode( '|', $data['aliases'] ) ) );
		$watchListUpdater = new WatchlistUpdater( $user, NS_WBSCHEMA_JSON );
		$schemaWriter = new MediaWikiRevisionSchemaWriter( $updaterFactory, $this, $watchListUpdater );
		try {
			$schemaWriter->updateSchema(
				$id,
				'en',
				$data['label'],
				$data['description'],
				$aliases,
				$data['schema']
			);
		} catch ( RunTimeException $e ) {
			return Status::newFatal( 'wikibaseschema-error-schemaupdate-failed' );
		}

		return Status::newGood();
	}

	protected function getFormFields() {
		/** @var WikibaseSchemaContent $content */
		$content = $this->getContext()->getWikiPage()->getContent();
		if ( !$content ) {
			throw new RuntimeException( $this->msg( 'wikibaseschema-error-schemadeleted' ) );
		}

		$schemaData = ( new SchemaDispatcher() )->getMonolingualSchemaData( $content->getText(), 'en' );

		return [
			'label' => [
				'type' => 'text',
				'default' => $schemaData->nameBadge->label,
				'label-message' => 'wikibaseschema-editpage-label-inputlabel',
				'placeholder-message' => 'wikibaseschema-label-edit-placeholder',
			],
			'description' => [
				'type' => 'text',
				'default' => $schemaData->nameBadge->description,
				'label-message' => 'wikibaseschema-editpage-description-inputlabel',
				'placeholder-message' => 'wikibaseschema-description-edit-placeholder',
			],
			'aliases' => [
				'type' => 'text',
				'default' => implode( ' | ', $schemaData->nameBadge->aliases ),
				'label-message' => 'wikibaseschema-editpage-aliases-inputlabel',
				'placeholder-message' => 'wikibaseschema-aliases-edit-placeholder',
			],
			'schema' => [
				'type' => 'textarea',
				'default' => $schemaData->schema,
				'label-message' => 'wikibaseschema-editpage-schema-inputlabel',
			],
		];
	}

	protected function usesOOUI() {
		return true;
	}

	/**
	 * Do something exciting on successful processing of the form.  This might be to show
	 * a confirmation message (watch, rollback, etc) or to redirect somewhere else (edit,
	 * protect, etc).
	 */
	public function onSuccess() {
		$redirectParams = $this->getRequest()->getVal( 'redirectparams', '' );
		$this->getOutput()->redirect( $this->getTitle()->getFullURL( $redirectParams ) );
	}

	/**
	 * Return the name of the action this object responds to
	 *
	 * @since 1.17
	 *
	 * @return string Lowercase name
	 */
	public function getName() {
		return 'edit';
	}

	public function getRestriction() {
		return $this->getName();
	}

}
