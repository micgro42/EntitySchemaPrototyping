<?php

declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use EntitySchema\DataAccess\EntitySchemaTermLookup;
use EntitySchema\Domain\Model\EntitySchemaId;
use EntitySchema\MediaWiki\Content\EntitySchemaContent;
use EntitySchema\Services\Converter\EntitySchemaConverter;
use EntitySchema\Services\Diff\EntitySchemaEntityChangeFactory;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use RecentChange;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\IndeterminateEntityId;
use Wikibase\DataModel\Entity\PseudoEntityId;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 */
class WikibasePseudoEntitiesHandler {

	public function onRevisionFromEditComplete(
		WikiPage $wikiPage,
		RevisionRecord $revisionRecord,
		int $baseID,
		UserIdentity $user
	) {
		if ( $wikiPage->getContent()->getModel() !== EntitySchemaContent::CONTENT_MODEL_ID ) {
			return;
		}
		$services = MediaWikiServices::getInstance();
		$settings = $services->getMainConfig();
		if ( !$settings->get( 'EntitySchemaEnableRepo' ) ) {
			return;
		}

		$parentId = $revisionRecord->getParentId();
		if ( !$parentId ) {
			// new EntitySchema, no need to notify
			return;
		}
		$parentRevision = MediaWikiServices::getInstance()
			->getRevisionLookup()
			->getRevisionById( $parentId );
		if ( !$parentRevision ) {
			wfLogWarning(
				__METHOD__ . ': Cannot notify on page modification: '
				. 'failed to load parent revision with ID ' . $parentId
			);
			return;
		}

		$entityChangeFactory = new EntitySchemaEntityChangeFactory();
		$entityChange = $entityChangeFactory->newForEntitySchemaModified(
			$revisionRecord,
			$parentRevision,
		);

		$changeHolder = WikibaseRepo::getChangeHolder();
		$changeHolder->transmitChange( $entityChange );

	}

	// TODO: on RecentChangeSave get change from ChangeHolder, add RC data and have it handled by changeHandler
	public function onRecentChange_save( RecentChange $recentChange ): void {
		$logType = $recentChange->getAttribute( 'rc_log_type' );
		$logAction = $recentChange->getAttribute( 'rc_log_action' );
		if ($recentChange->getPage()->getNamespace() !== NS_ENTITYSCHEMA_JSON) {
			return;
		}

		if (
			$logType === null ||
			( $logType === 'delete' && ( $logAction === 'restore' || $logAction === 'delete' ) )
		) {
			// Create a wikibase change either on edit or if the whole entity was (un)deleted.
			// Note: As entities can't be moved, we don't need to consider log action delete_redir/delete_redir2 here.
			$changeHolder = WikibaseRepo::getChangeHolder();
			$changes = $changeHolder->getChanges();
			// TODO: this should never be more than a single change from the current request
			if (count($changes) !== 1) {
				throw new \LogicException('Expected exactly one change, got ' . count($changes));
			}
			$change = $changes[0];

			$change->setFields( [
				ChangeRow::REVISION_ID => $recentChange->getAttribute( 'rc_this_oldid' ),
				ChangeRow::TIME => $recentChange->getAttribute( 'rc_timestamp' ),
			] );

			$change->setMetadata( [
				'bot' => $recentChange->getAttribute( 'rc_bot' ),
				'page_id' => $recentChange->getAttribute( 'rc_cur_id' ),
				'rev_id' => $recentChange->getAttribute( 'rc_this_oldid' ),
				'parent_id' => $recentChange->getAttribute( 'rc_last_oldid' ),
				'comment' => $recentChange->getAttribute( 'rc_comment' ),
			] );

			$centralIdLookup = MediaWikiServices::getInstance()->getCentralIdLookup();
			$centralUserId = $centralIdLookup->centralIdFromLocalUser(
				$recentChange->getPerformerIdentity()
			);

			$change->addUserMetadata(
				$recentChange->getAttribute( 'rc_user' ),
				$recentChange->getAttribute( 'rc_user_text' ),
				$centralUserId
			);

			$changeHandler = WikibaseClient::getChangeHandler();
			$changeHandler->handleChange( $change );
		}
	}

	public function onWikibasePseudoEntities_LoadPseudoEntityArray( &$entityArr, IndeterminateEntityId $entityId ): bool {
		if ($entityId->getEntityType() !== 'entityschema') {
			return true;
		}
		$services = MediaWikiServices::getInstance();
		$titleFactory = $services->getTitleFactory();
		$wikiPageFactory = $services->getWikiPageFactory();
		$schemaPageIdentity = $titleFactory->newFromText( $entityId->getSerialization(), NS_ENTITYSCHEMA_JSON );
		if ( $schemaPageIdentity === null ) {
			return false;
		}
		$schemaPage = $wikiPageFactory->newFromTitle( $schemaPageIdentity );
		$content = $schemaPage->getContent();
		if ( !( $content instanceof EntitySchemaContent ) ) {
			return false;
		}
		$schema = $content->getText();

		$converter = new EntitySchemaConverter();
		$schemaData = $converter->getFullWikibaseArraySchemaData( $schema );
		$entityArr = $schemaData;
		return false;
	}

	public function onWikibasePseudoEntities_EntityExists( bool &$entityExists, PseudoEntityId $entityId ): bool {
		if ($entityId->getEntityType() !== 'entityschema') {
			return true;
		}
		$services = MediaWikiServices::getInstance();
		$titleFactory = $services->getTitleFactory();
		$schemaPageIdentity = $titleFactory->newFromText( $entityId->getSerialization(), NS_ENTITYSCHEMA_JSON );
		if ( $schemaPageIdentity === null ) {
			$entityExists = false;
			return false;
		}
		$entityExists = $schemaPageIdentity->exists();
		return false;
	}

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public function onWikibasePseudoEntities_PseudoEntityIdParser( array &$pseudoEntityParsers ): void {
		$pseudoEntityParsers[EntitySchemaId::PATTERN] = static function ( $idSerialization ): EntitySchemaId {
			return new EntitySchemaId( $idSerialization );
		};
	}

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
	public function onWikibasePseudoEntities_SearchEntities_Handlers(
		array &$pseudoEntityTypeHandlers
	): void {
		$pseudoEntityTypeHandlers['entityschema'] = static function ( array $params ): array {
			try {
				$id = new EntitySchemaId( $params['search'] );
			} catch ( InvalidArgumentException $exception ) {
				return [];
			}
			$title = Title::makeTitleSafe( NS_ENTITYSCHEMA_JSON, $id ); // TODO use TitleFactory
			if ( $title->exists() ) {
				return [ [
					'id' => $id->getSerialization(),
					'title' => $title->getPrefixedText(),
					'pageid' => $title->getArticleID(),
					'display' => [], // TODO use EntitySchemaConverter::getMonolingualNameBadgeData()?
				] ];
			}
			return [];
		};
	}

	public function onWikibasePseudoEntities_GetPseudoTermLookup( array &$pseudoTermLookups ): void {
		$services = MediaWikiServices::getInstance();
		$pseudoTermLookups['entityschema'] = new EntitySchemaTermLookup(
			$services->getTitleFactory(),
			$services->getWikiPageFactory()
		);
	}

	// phpcs:enable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

}
