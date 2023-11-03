<?php

declare( strict_types = 1 );

namespace EntitySchema\Wikibase\Hooks;

use Config;
use EntitySchema\DataAccess\LabelLookup;
use EntitySchema\Domain\Model\EntitySchemaId;
use InvalidArgumentException;
use MediaWiki\Title\TitleFactory;
use ValueFormatters\FormatterOptions;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseClientDataTypesHandler {

	public Config $settings;
	private TitleFactory $titleFactory;
	private LabelLookup $labelLookup;

	public function __construct(
		Config $settings,
		TitleFactory $titleFactory,
		LabelLookup $labelLookup
	) {
		$this->settings = $settings;
		$this->titleFactory = $titleFactory;
		$this->labelLookup = $labelLookup;
	}

	public function onWikibaseClientDataTypes( array &$dataTypeDefinitions ): void {
		if ( !$this->settings->get( 'EntitySchemaEnableDatatype' ) ) {
			return;
		}
		$dataTypeDefinitions['PT:entity-schema'] = [
			'value-type' => 'wikibase-entityid',
			'snak-formatter-factory-callback' => function ( string $format, FormatterOptions $options ) {
				return new class (
					$this->settings,
					$format,
					$options,
					$this->labelLookup,
					$this->titleFactory,
				) implements SnakFormatter {

					public Config $settings;
					private string $format;
					private FormatterOptions $options;
					private LabelLookup $labelLookup;
					private TitleFactory $titleFactory;

					public function __construct(
						Config $settings,
						string $format,
						FormatterOptions $options,
						LabelLookup $labelLookup,
						TitleFactory $titleFactory,
					) {
						$this->settings = $settings;
						$this->format = $format;
						$this->options = $options;
						$this->labelLookup = $labelLookup;
						$this->titleFactory = $titleFactory;
					}

					public function formatSnak( Snak $snak ): string {
						if ( !( $snak instanceof PropertyValueSnak ) ) {
							throw new InvalidArgumentException( 'Must be PropertyValueSnak!' );
						}
						$entitySchemaId = $snak->getDataValue()->getValue()->getEntityId();
						if ( !($entitySchemaId instanceof EntitySchemaId ) ) {
							return 'not entity schema id! ' . __METHOD__;
						}
						if ( !$this->settings->get( 'EntitySchemaEnableRepo' ) ) {
							return $entitySchemaId->getSerialization();
						}
						$schemaPageIdentity = $this->titleFactory->newFromText(
							$entitySchemaId->getSerialization(),
							NS_ENTITYSCHEMA_JSON
						);
						if ( $schemaPageIdentity === null ) {
							return $entitySchemaId->getSerialization() . ' does not exist! ' . __METHOD__;
						}

						$langCode = $this->options->getOption( 'lang' );
						$term = $this->labelLookup->getLabelForTitle( $schemaPageIdentity, $langCode );

						if ( !$term ) {
							return 'no label for ' . $schemaPageIdentity->getFullURL() . ' in ' . $langCode;
						}
						return $term->getText();
					}

					public function getFormat(): string {
						return $this->format;
					}
				};
			},
		];
	}
}
