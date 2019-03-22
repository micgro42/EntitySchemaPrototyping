<?php

namespace Wikibase\Schema\MediaWiki;

use Article;
use DatabaseUpdater;
use HistoryPager;
use Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use SkinTemplate;
use Title;
use Wikibase\Schema\DataAccess\MediaWikiRevisionSchemaWriter;
use Wikibase\Schema\MediaWiki\Content\WikibaseSchemaContent;

/**
 * Hooks utilized by the WikibaseSchema extension
 */
final class WikibaseSchemaHooks {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function onCreateDBSchema( DatabaseUpdater $updater ) {
		$updater->addExtensionTable(
			'wbschema_id_counter',
			__DIR__ . '/../../sql/WikibaseSchema.sql'
		);

		$updater->modifyExtensionField(
			'page',
			'page_namespace',
			__DIR__ . '/../../sql/patch-move-page-namespace.sql'
		);
	}

	public static function onExtensionTypes( array &$extTypes ) {
		$extTypes['wikibase'] = 'Wikibase';
	}

	public static function onSkinTemplateNavigation( SkinTemplate $skinTemplate, array &$links ) {
		$title = $skinTemplate->getRelevantTitle();
		if ( !$title->inNamespace( NS_WBSCHEMA_JSON ) ) {
			return;
		}

		unset( $links['views']['edit'] );
	}

	/**
	 * Handler for the BeforeDisplayNoArticleText called by Article.
	 * We implement this solely to replace the standard message that
	 * is shown when a Schema does not exists.
	 *
	 * @param Article $article
	 *
	 * @return bool
	 */
	public static function onBeforeDisplayNoArticleText( Article $article ) {
		if ( $article->getTitle()->getNamespace() !== NS_WBSCHEMA_JSON ) {
			return true;
		}

		$context = $article->getContext();
		$dir = $context->getLanguage()->getDir();
		$lang = $context->getLanguage()->getHtmlCode();

		$outputPage = $context->getOutput();
		$outputPage->wrapWikiMsg(
			Html::element( 'div', [
				'class' => "noarticletext mw-content-$dir",
				'dir' => $dir,
				'lang' => $lang,
			], '$1' ),
			'wikibaseschema-noschema'
		);

		return false;
	}

	/**
	 * Modify line endings on history page.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryLineEnding
	 *
	 * @param HistoryPager $history
	 * @param object &$row
	 * @param string &$html
	 * @param array &$classes
	 */
	public static function onPageHistoryLineEnding(
		HistoryPager $history,
		&$row,
		&$html,
		array &$classes
	) {
		$rev = MediaWikiServices::getInstance()->getRevisionStore()->newRevisionFromRow( $row );

		$wikiPage = $history->getWikiPage();

		if ( $wikiPage->getContentModel() === WikibaseSchemaContent::CONTENT_MODEL_ID
			&& $wikiPage->getLatest() !== $rev->getId()
			&& $wikiPage->getTitle()->quickUserCan( 'edit', $history->getUser() )
			&& !$rev->isDeleted( RevisionRecord::DELETED_TEXT )
		) {
			$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
			$link = $linkRenderer->makeKnownLink(
				$wikiPage->getTitle(),
				$history->msg( 'wikibaseschema-restoreold' )->text(),
				[],
				[
					'action' => 'edit',
					'restore' => $rev->getId(),
				]
			);

			$html .= ' ' . $history->msg( 'parentheses' )->rawParams( $link )->escaped();
		}
	}

	/**
	 * Handler for the FormatAutocomments hook, used to translate parts of edit summaries
	 * into the user language. Only supports a fixed set of autocomments.
	 *
	 * @param string|null &$comment The comment HTML. Initially null; if set to a string,
	 * Linker::formatAutocomments() will skip the default formatting. In that case,
	 * the actual autocomment should be wrapped in <span dir="auto"><span class="autocomment">.
	 * @param bool $pre Whether any text appears in the summary before this autocomment.
	 * If true, we insert the autocomment-prefix before the autocomment
	 * (outside the two <span>s) to separate it from that.
	 * @param string $auto The autocomment content (without the surrounding comment marks)
	 * @param bool $post Whether any text appears in the summary after this autocomment.
	 * If true, we append the colon-separator after the autocomment (still inside the two <span>s)
	 * to separate it from that.
	 * @param Title|null $title The title to which the comment applies. A null $title is taken to
	 * refer to the current page ($wgTitle), though that’s not quite clear from Linker’s documentation.
	 * @param bool $local If true, don’t actually use the $title for links, e. g. generate
	 * <a href="#foo"> instead of <a href="/wiki/Namespace:Title#foo">. Unused here.
	 *
	 * @return null|false
	 */
	public static function onFormatAutocomments( &$comment, $pre, $auto, $post, $title, $local ) {
		// phpcs:ignore MediaWiki.VariableAnalysis.ForbiddenGlobalVariables.ForbiddenGlobal$wgTitle
		global $wgTitle;

		if ( !( $title instanceof Title ) ) {
			$title = $wgTitle;
		}

		if ( !( $title instanceof Title ) ) {
			return null;
		}

		if ( $title->getNamespace() !== NS_WBSCHEMA_JSON ) {
			return null;
		}

		if ( $auto === MediaWikiRevisionSchemaWriter::AUTOCOMMENT_NEWSCHEMA ) {
			$comment = wfMessage( 'wikibaseschema-summary-newschema-nolabel' )->escaped();
		} else {
			return null;
		}

		if ( $post ) {
			$comment .= wfMessage( 'colon-separator' )->escaped();
		}

		$comment = '<span dir="auto"><span class="autocomment">' . $comment . '</span></span>';

		if ( $pre ) {
			$comment = wfMessage( 'autocomment-prefix' )->escaped() . $comment;
		}

		return false; // do not run further hooks
	}

}
