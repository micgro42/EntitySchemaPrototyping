<?php

declare( strict_types = 1 );

namespace EntitySchema\Services\Diff;

use EntitySchema\Domain\Model\EntitySchemaId;
use EntitySchema\Services\Converter\EntitySchemaConverter;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\EntityDiffChangedAspectsFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class EntitySchemaEntityChangeFactory {

	private EntitySchemaDiffer $schemaDiffer;
	private EntitySchemaConverter $schemaConverter;
	private ?CentralIdLookup $centralIdLookup;
	private LoggerInterface $logger;

	public function __construct() {
		$this->schemaDiffer = new EntitySchemaDiffer();
		$this->schemaConverter = new EntitySchemaConverter();
		$this->centralIdLookup = null; // FIXME: think about whether we actually need this here
		$this->logger = WikibaseRepo::getLogger();
	}

	/**
	 * TODO: for test see \Wikibase\Repo\Tests\Notifications\WikiPageActionEntityChangeFactoryTest::testNewForPageModified
	 */
	public function newForEntitySchemaModified( RevisionRecord $newRevision, RevisionRecord $oldRevision ): EntityChange {
		// FIXME: should this clean up data first? See \EntitySchema\DataAccess\EntitySchemaUpdateGuard::guardSchemaUpdate
		$diff = $this->schemaDiffer->diffSchemas(
			$this->schemaConverter->getFullArraySchemaData(
				$oldRevision->getContent( SlotRecord::MAIN )->getText()
			),
			$this->schemaConverter->getFullArraySchemaData(
				$newRevision->getContent( SlotRecord::MAIN )->getText()
			)
		);


		$operations = $diff->getOperations();
		// Oh the joys of subtle errors introduced in duplicate implementations...
		if ( isset( $operations['labels'] ) ) {
			$operations['label'] = $operations['labels'];
		}
		if ( isset( $operations['descriptions'] ) ) {
			$operations['description'] = $operations['descriptions'];
		}

		$entityChange = new EntityChange( [] );
//		$entityChange = new EntityChange( $operations );
		$entityChange->setLogger( $this->logger );
		$entityIdSerialization = $newRevision->getPageAsLinkTarget()->getText();
		$entityChange->setEntityId( new EntitySchemaId( $entityIdSerialization ) );
		$type = 'wikibase-entityschema~' . EntityChange::UPDATE;
		$entityChange->setField( ChangeRow::TYPE, $type );

		$entityDiff = EntityDiff::newForType( 'entityschema', $operations );
		$diffAspectsFactory = new EntityDiffChangedAspectsFactory( $this->logger );
		$compactDiff = $diffAspectsFactory->newFromEntityDiff( $entityDiff );
		$info = $entityChange->getInfo();
		$info[ChangeRow::COMPACT_DIFF] = $compactDiff;
		$entityChange->setField( ChangeRow::INFO, $info);

		// these are usually the auto-increment IDs from wb_changes. We make up something human readable here
		$entityChange->setField( ChangeRow::ID, $entityIdSerialization . '-' . $newRevision->getId());

		$this->setEntityChangeRevisionInfo(
			$entityChange,
			$newRevision,
			$this->getCentralUserId( $newRevision->getUser() )
		);

		// FIXME: set recent changes info? @see \Wikibase\Repo\Hooks\RecentChangeSaveHookHandler::setChangeMetaData


		return $entityChange;
	}

	/**
	 * copied from @see \Wikibase\Repo\Notifications\WikiPageActionEntityChangeFactory::setEntityChangeRevisionInfo
	 * ! Must stay in sync!!!
	 */
	private function setEntityChangeRevisionInfo( EntityChange $change, RevisionRecord $revision, int $centralUserId ): void {
		$change->setFields( [
			ChangeRow::REVISION_ID => $revision->getId(),
			ChangeRow::TIME => $revision->getTimestamp(),
		] );

		if ( !$change->hasField( ChangeRow::OBJECT_ID ) ) {
			throw new \Exception(
				'EntityChange::setRevisionInfo() called without calling setEntityId() first!'
			);
		}

		$comment = $revision->getComment();
		$change->setMetadata( [
			'page_id' => $revision->getPageId(),
			'parent_id' => $revision->getParentId(),
			'comment' => $comment ? $comment->text : null,
			'rev_id' => $revision->getId(),
		] );

		$user = $revision->getUser();
		$change->addUserMetadata(
			$user ? $user->getId() : 0,
			$user ? $user->getName() : '',
			$centralUserId
		);
	}

	/**
	 * copied from @see \Wikibase\Repo\Notifications\WikiPageActionEntityChangeFactory::getCentralUserId
	 *
	 * @param UserIdentity $user Repository user
	 *
	 * @return int Central user ID, or 0
	 */
	private function getCentralUserId( UserIdentity $user ): int {
		if ( $this->centralIdLookup ) {
			return $this->centralIdLookup->centralIdFromLocalUser( $user );
		}

		return 0;
	}

}
