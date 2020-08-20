<?php

namespace Drupal\layout_builder_custom\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Provides a field type of uiowaBlockHeadline.
 * 
 * @FieldType(
 *   id = "field_uiowa_block_headline",
 *   label = @Translation("Block Headline"),
 *   description = @Translation("Parent headline for collections of content."),
 *   default_formatter = "uiowa_block_headline_formatter",
 *   default_widget = "uiowa_block_headline_widget",
 * )
 */
class UiowaBlockHeadline extends FieldItemBase {

/**
 * {@inheritdoc}
 */
public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'headline' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'heading_size' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'hide_headline' => [
            'type' => 'int',
            'not null' => TRUE,
            'default' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $item = $this->getValue();
    return $item['headline'] === NULL || $item['headline'] === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['headline'] = DataDefinition::create('string')
      ->setLabel(t('Block Headline'))
      ->setDescription(t('Parent headline over collections of content.'));

    $properties['heading_size'] = DataDefinition::create('string')
      ->setLabel(t('Block Headline Size'))
      ->setDescription(t('Heading size for the parent headline.'));

    $properties['hide_headline'] = DataDefinition::create('string')
      ->setLabel(t('Hide Headline'))
      ->setDescription(t('Visually hide block headline'));

    return $properties;
  }

}
