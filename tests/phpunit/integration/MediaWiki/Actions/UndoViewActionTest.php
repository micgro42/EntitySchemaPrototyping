<?php

namespace EntitySchema\Tests\Integration\MediaWiki\Actions;

use CommentStoreComment;
use FauxRequest;
use MediaWiki\Revision\SlotRecord;
use MediaWikiTestCase;
use RequestContext;
use TextSlotDiffRenderer;
use Title;
use EntitySchema\MediaWiki\Actions\UndoViewAction;
use EntitySchema\MediaWiki\Content\EntitySchemaContent;
use EntitySchema\MediaWiki\Content\EntitySchemaSlotDiffRenderer;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 *
 * @group Database
 *
 * @covers \EntitySchema\MediaWiki\Actions\UndoViewAction
 * @covers \EntitySchema\MediaWiki\Actions\AbstractUndoAction
 * @covers \EntitySchema\MediaWiki\UndoHandler
 * @covers \EntitySchema\Presentation\DiffRenderer
 * @covers \EntitySchema\Presentation\ConfirmationFormRenderer
 */
class UndoViewActionTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'recentchanges';
	}

	public function test_UndoView() {
		// arrange
		$schemaId = 'E123';
		$page = WikiPage::factory( Title::makeTitle( NS_ENTITYSCHEMA_JSON, $schemaId ) );

		$firstID = $this->saveSchemaPageContent( $page, [ 'schemaText' => 'abc', 'id' => $schemaId ] );
		$secondId = $this->saveSchemaPageContent( $page, [ 'schemaText' => 'def', 'id' => $schemaId ] );

		$context = RequestContext::getMain();
		$context->setWikiPage( $page );
		$context->setRequest(
			new FauxRequest(
				[
					'action' => 'edit',
					'undoafter' => $firstID,
					'undo' => $secondId,
					'title' => 'Schema:' . $schemaId
				],
				false
			)
		);

		$textSlotDiffRenderer = new TextSlotDiffRenderer();
		$textSlotDiffRenderer->setEngine( TextSlotDiffRenderer::ENGINE_PHP );
		$diffRenderer = new EntitySchemaSlotDiffRenderer(
			$context,
			$textSlotDiffRenderer
		);
		$undoViewAction = new UndoViewAction( $page, $diffRenderer, $context );

		// act
		$undoViewAction->show();

		// assert
		$actualHTML = $undoViewAction->getContext()->getOutput()->getHTML();
		$this->assertContains( '<ins class="diffchange diffchange-inline">abc</ins>', $actualHTML );
		$this->assertContains( '<del class="diffchange diffchange-inline">def</del>', $actualHTML );
	}

	private function saveSchemaPageContent( WikiPage $page, array $content ) {
		$content['serializationVersion'] = '3.0';
		$updater = $page->newPageUpdater( self::getTestUser()->getUser() );
		$updater->setContent( SlotRecord::MAIN, new EntitySchemaContent( json_encode( $content ) ) );
		$firstRevRecord = $updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				'test summary 1'
			)
		);

		return $firstRevRecord->getId();
	}

}