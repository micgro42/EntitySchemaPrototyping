<?php

declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Rdf;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

/**
 * RDF mapping for EntitySchema values.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class EntitySchemaRdfBuilder implements ValueSnakRdfBuilder {

	private RdfVocabulary $vocabulary;

	private string $wikibaseConceptBaseUri;

	private bool $entitySchemaPrefixInitialized = false;

	private ?string $entitySchemaPrefix;

	public function __construct( RdfVocabulary $vocabulary, string $wikibaseConceptBaseUri ) {
		$this->vocabulary = $vocabulary;
		$this->wikibaseConceptBaseUri = $wikibaseConceptBaseUri;
	}

	/**
	 * Adds specific value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param string $snakNamespace
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		$entitySchemaPrefix = $this->getEntitySchemaPrefix();
		$dataValue = $snak->getDataValue();
		if ( $dataValue instanceof EntityIdValue ) {
			$serialization = $dataValue->getEntityId()->getSerialization();
		} elseif ( $dataValue instanceof StringValue ) {
			$serialization = $dataValue->getValue();
		} else {
			throw new InvalidArgumentException( 'Unknown EntitySchema data value type: ' . get_class( $dataValue ) ); // TODO?
		}
		if ( $entitySchemaPrefix ) {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is(
				$entitySchemaPrefix,
				$serialization
			);
		} else {
			$writer->say( $propertyValueNamespace, $propertyValueLName )->is(
				trim( $this->wikibaseConceptBaseUri . $serialization )
			);
		}
	}

	/**
	 * @return string|null Prefix to use for entity schemas or null if none found.
	 */
	private function getEntitySchemaPrefix(): ?string {
		if ( !$this->entitySchemaPrefixInitialized ) {
			$this->entitySchemaPrefixInitialized = true;
			$this->entitySchemaPrefix = null;

			foreach ( $this->vocabulary->getNamespaces() as $prefix => $uri ) {
				if ( $uri === $this->wikibaseConceptBaseUri ) {
					$this->entitySchemaPrefix = $prefix;
					break;
				}
			}
		}

		return $this->entitySchemaPrefix;
	}

}
