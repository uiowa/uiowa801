<?php

namespace Drupal\sitenow_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides base node source abstract class.
 */
abstract class BaseNodeSource extends SqlBase {
  use ProcessMediaTrait;

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Determine if the content should be published or not.
    switch ($row->getSourceProperty('status')) {

      case 1:
        $row->setSourceProperty('moderation_state', 'published');
        break;

      default:
        $row->setSourceProperty('moderation_state', 'draft');
    }

    // TODO: Change the autogenerated stub.
    return parent::prepareRow($row);
  }

  /**
   * Extract a summary from a block of text.
   */
  public function extractSummaryFromText($text) {
    $new_summary = substr($text, 0, 200);
    $looper = TRUE;
    // Shorten the string until we reach a natural(ish) breaking point.
    while ($looper && strlen($new_summary) > 0) {
      switch (substr($new_summary, -1)) {

        case '.':
        case '!':
        case '?':
          $looper = FALSE;
          break;

        case ';':
        case ':':
        case '"':
          $looper = FALSE;
          $new_summary = $new_summary . '...';
          break;

        default:
          $new_summary = substr($new_summary, 0, -1);
      }
    }
    // Strip out any HTML, and set the new summary.
    $new_summary = preg_replace("|<.*?>|", '', $new_summary);

    return $new_summary;
  }

}
