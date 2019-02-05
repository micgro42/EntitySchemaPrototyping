<?php

namespace Wikibase\Schema\Tests\MediaWiki\Specials;

use PermissionsError;
use ReadOnlyError;
use ReadOnlyMode;
use Block;
use FauxRequest;
use SpecialPageTestBase;
use UserBlockedError;

use Wikibase\Schema\MediaWiki\Specials\NewSchema;

/**
 * @covers \Wikibase\Schema\MediaWiki\Specials\NewSchema
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class NewSchemaTest extends SpecialPageTestBase {

	private $block;

	/**
	 * @expectedException ReadOnlyError
	 */
	public function testReadOnly() {
		$readOnlyMode = $this->getMockBuilder( ReadOnlyMode::class )
			->disableOriginalConstructor()
			->getMock();
		$readOnlyMode->method( 'isReadOnly' )->willReturn( true );
		$this->setService( 'ReadOnlyMode', $readOnlyMode );
		$this->executeSpecialPage( null );
	}

	/**
	 * @expectedException PermissionsError
	 */
	public function testNoRights() {
		global $wgGroupPermissions;

		$groupPermissions = $wgGroupPermissions;
		$groupPermissions['*']['createpage'] = false;
		$this->setMwGlobals( 'wgGroupPermissions', $groupPermissions );
		$this->executeSpecialPage( null );
	}

	/**
	 * @group slow
	 */
	public function testNewSchemaIsCreatedWithMinData() {
		$testLabel = uniqid( 'testLabel_' . __FUNCTION__ . '_' );
		$this->executeSpecialPage(
			null,
			new FauxRequest(
				[
					'label' => $testLabel,
				],
				true
			)
		);
		$textOfNewestPage = $this->getLastCreatedPageText();
		$this->assertContains( $testLabel, $textOfNewestPage );
	}

	public function testNewSchemaIsNotCreatedWhenBlocked() {
		$testuser = self::getTestUser()->getUser();
		$this->block = new Block(
			[
				'address' => $testuser,
				'reason' => 'testing in ' . __CLASS__,
				'by' => $testuser->getId(),
			]
		);
		$this->block->insert();

		$testLabel = uniqid( 'testLabel_' . __FUNCTION__ . '_' );

		try {
			$this->executeSpecialPage(
				null,
				new FauxRequest(
					[
						'label' => $testLabel,
					],
					true
				),
				'en',
				$testuser
			);

			$this->fail( 'a blocked user must cause an exception!' );
		} catch ( UserBlockedError $e ) {
			// we expect that
		}

		$textOfNewestPage = $this->getLastCreatedPageText();
		$this->assertNotContains(
			$testLabel,
			$textOfNewestPage,
			'Blocked User was able to create new Schema!'
		);
	}

	public function testNewSchemaIsNotCreatedWithInvalidData() {
		list( $html, ) = $this->executeSpecialPage(
			null,
			new FauxRequest(
				[

				], true
			)
		);

		$this->assertContains(
			'There are problems with some of your input.',
			$html,
			'error status message is missing'
		);
		$this->assertContains(
			'This value is required.',
			$html,
			'message about required value is missing'
		);
	}

	protected function tearDown() {
		if ( isset( $this->block ) ) {
			$this->block->delete();
		}

		parent::tearDown();
	}

	protected function getLastCreatedPageText() {
		$row = $this->db->select(
			[ 'page', 'revision', 'text' ],
			[
				'page_namespace',
				'page_title',
				'old_text',
			],
			[],
			__METHOD__,
			[
				'ORDER BY' => [
					'page_id DESC',
				],
				'LIMIT' => 1,
			],
			[
				'revision' => [
					'INNER JOIN',
					'page_latest=rev_id',
				],
				'text' => [
					'INNER JOIN',
					'rev_text_id=old_id',
				],
			]

		);
		$data = $row->fetchRow();

		return $data['old_text'];
	}

	protected function newSpecialPage() {
		return new NewSchema();
	}

}