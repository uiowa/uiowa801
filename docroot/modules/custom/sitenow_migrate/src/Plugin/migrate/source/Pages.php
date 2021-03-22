<?php

namespace Drupal\sitenow_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Basic implementation of the source plugin.
 *
 * @MigrateSource(
 *  id = "pages",
 *  source_module = "node"
 * )
 */
class Pages extends BaseNodeSource {

  use ProcessMediaTrait;
  use LinkReplaceTrait;

  /**
   * Prepare row used for altering source data prior to its insertion.
   */
  public function prepareRow(Row $row) {
    // Search for D7 inline embeds and replace with D8 inline entities.
    $content = $row->getSourceProperty('body_value');
    $content = preg_replace_callback("|\[\[\{.*?\"fid\":\"(.*?)\".*?\]\]|", [
      $this,
      'entityReplace',
    ], $content);
    $row->setSourceProperty('body_value', $content);

    // Check summary, and create one if none exists.
    if (!$row->getSourceProperty('body_summary')) {
      $new_summary = $this->extractSummaryFromText($content);
      $row->setSourceProperty('body_summary', $new_summary);
    }
    // Call the parent prepareRow.
    return parent::prepareRow($row);
  }

}
