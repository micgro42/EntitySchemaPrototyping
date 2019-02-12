<?php

namespace Wikibase\Schema\DataAccess;

use CommentStoreComment;
use InvalidArgumentException;
use MediaWiki\Revision\SlotRecord;
use RuntimeException;
use Wikibase\Schema\Domain\Model\SchemaId;
use Wikibase\Schema\Domain\Storage\IdGenerator;
use Wikibase\Schema\MediaWiki\Content\WikibaseSchemaContent;

/**
 * @license GPL-2.0-or-later
 */
class MediaWikiRevisionSchemaWriter implements SchemaWriter {

	private $pageUpdaterFactory;
	private $idGenerator;

	public function __construct(
		MediaWikiPageUpdaterFactory $pageUpdaterFactory,
		IdGenerator $idGenerator
	) {
		$this->idGenerator = $idGenerator;
		$this->pageUpdaterFactory = $pageUpdaterFactory;
	}

	/**
	 * @param string $language
	 * @param string $label
	 * @param string $description
	 * @param string[] $aliases
	 * @param string $schemaContent
	 *
	 * @return SchemaId id of the inserted Schema
	 */
	public function insertSchema(
		$language,
		$label = '',
		$description = '',
		array $aliases = [],
		$schemaContent = ''
	): SchemaId {
		$id = new SchemaId( 'O' . $this->idGenerator->getNewId() );

		$updater = $this->pageUpdaterFactory->getPageUpdater( $id->getId() );

		$updater->setContent(
			SlotRecord::MAIN,
			new WikibaseSchemaContent(
				json_encode(
					[
						'id' => $id->getId(),
						'serializationVersion' => '2.0',
						'labels' => [
							$language => $label
						],
						'descriptions' => [
							$language => $description
						],
						'aliases' => [
							$language => $aliases
						],
						'schema' => $schemaContent,
						'type' => 'ShExC'
					]
				)
			)
		);
		$updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				'FIXME: there should be a translatable comment here.'
			)
		);

		return $id;
	}

	/**
	 * @param SchemaId $id
	 * @param string $language
	 * @param string $label
	 * @param string $description
	 * @param string[] $aliases
	 * @param string $schemaContent
	 *
	 * @throws InvalidArgumentException if bad parameters are passed
	 * @throws RuntimeException if Schema to update does not exist or saving fails
	 *
	 * Update a Schema with new content. This will remove existing schema content.
	 */
	public function updateSchema( SchemaId $id,
		$language,
		$label,
		$description,
		array $aliases,
		$schemaContent ) {
		$this->validateParameters(
			$language,
			$label,
			$description,
			$aliases,
			$schemaContent
		);

		$updater = $this->pageUpdaterFactory->getPageUpdater( $id->getId() );
		if ( $updater->grabParentRevision() === null ) {
			throw new RuntimeException( 'Schema to update does not exist' );
		}

		$updater->setContent(
			SlotRecord::MAIN,
			new WikibaseSchemaContent(
				json_encode(
					[
						'id' => $id->getId(),
						'serializationVersion' => '2.0',
						'labels' => [
							$language => $label
						],
						'descriptions' => [
							$language => $description
						],
						'aliases' => [
							$language => $aliases
						],
						'schema' => $schemaContent,
						'type' => 'ShExC',
					]
				)
			)
		);
	}

	private function validateParameters(
		$language,
		$label,
		$description,
		array $aliases,
		$schemaContent
	) {
		if ( !( is_string( $language ) &&
			is_string( $label ) &&
			is_string( $description ) &&
			is_string( $schemaContent ) &&
			$this->isSequentialArrayOfStrings( $aliases )
		) ) {
			throw new RuntimeException(
				'language, label, description and schemaContent must be strings '
				. 'and aliases must be an array of strings'
			);
		}
	}

	private function isSequentialArrayOfStrings( array $array ) {
		$values = array_values( $array );
		if ( $array !== $values ) {
			return false; // array is associative - fast solution see: https://stackoverflow.com/questions/173400/how-to-check-if-php-array-is-associative-or-sequential
		}
		foreach ( $values as $value ) {
			if ( !is_string( $value ) ) {
				return false;
			}
		}
		return true;
	}

}
