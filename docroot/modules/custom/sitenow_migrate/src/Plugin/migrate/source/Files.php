<?php

namespace Drupal\sitenow_migrate\Plugin\migrate\source;

use Drupal\file\Plugin\migrate\source\d7\File;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use function PHPUnit\Framework\returnValue;
use function PHPUnit\Framework\throwException;

/**
 * Basic implementation of the source plugin.
 *
 * @MigrateSource(
 *  id = "files",
 *  source_module = "file"
 * )
 */
class Files extends File {
  /**
   * Prepare row used for altering source data prior to its insertion.
   */
  public function prepareRow(Row $row) {
    $scheme = parse_url($row->getSourceProperty('uri'), PHP_URL_SCHEME);

    // @todo: We probably need to break up this migration by file type to
    // process remote videos and other schemes differently.
    if ($scheme != 'public') {
      return FALSE;
    }

    $fileType = explode('/', $row->getSourceProperty('filemime'))[0];

    if ($fileType == 'image') {
      $row->setSourceProperty('meta', $this->fetchMeta($row));
    }

    return parent::prepareRow($row);
  }

  /**
   * If the migrated file is an image, grab the alt and title text values.
   */
  public function fetchMeta($row) {
    $query = $this->select('file_managed', 'f');
    $query->join('field_data_field_file_image_alt_text', 'a', 'a.entity_id = f.fid');
    $query->join('field_data_field_file_image_title_text', 't', 't.entity_id = f.fid');

    $result = $query->fields('a', [
      'field_file_image_alt_text_value',
    ])
      ->fields('t', [
        'field_file_image_title_text_value',
      ])
      ->condition('f.fid', $row->getSourceProperty('fid'))
      ->execute();

    return $result->fetchAssoc();
  }

}
