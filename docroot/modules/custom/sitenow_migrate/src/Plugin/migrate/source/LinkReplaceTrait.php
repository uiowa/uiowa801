<?php

namespace Drupal\sitenow_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;

/**
 * Provides functions for processing links in source plugins.
 */
trait LinkReplaceTrait {

  /**
   * Pre-migrate method for replacing links.
   */
  private function preLinkReplace($row, $field_name, $no_links_prior = 0) {
    $field = $row->getSourceProperty($field_name);
    if (!empty($field)) {
      // Search for D7 inline embeds and replace with D8 inline entities.
      $field[0]['value'] = $this->replaceInlineFiles($field[0]['value']);

      // Parse links.
      $doc = Html::load($field[0]['value']);
      $links = $doc->getElementsByTagName('a');
      $i = $links->length - 1;
      $created_year = date('Y', $row->getSourceProperty('created'));

      while ($i >= 0) {
        $link = $links->item($i);
        $href = $link->getAttribute('href');

        // Unlink anchors in body from articles before the given
        // no_links_prior value (default 0).
        if ($created_year < $no_links_prior) {
          $text = $doc->createTextNode($link->nodeValue);
          $link->parentNode->replaceChild($text, $link);
          $doc->saveHTML();
        }
        else {
          // @todo update this to get sitename in place of pharmacy.uiowa.edu.
          if (strpos($href, '/node/') === 0 || stristr($href, 'pharmacy.uiowa.edu/node/')) {
            $nid = explode('node/', $href)[1];

            // @todo update this to allow for easier 'manualLookup' implementation.
            if ($lookup = $this->manualLookup($nid)) {
              $link->setAttribute('href', $lookup);
              $link->parentNode->replaceChild($link, $link);
              $this->logger->info('Replaced internal link @link in article @article.', [
                '@link' => $href,
                '@article' => $row->getSourceProperty('title'),
              ]);

            }
            else {
              $this->logger->notice('Unable to replace internal link @link in article @article.', [
                '@link' => $href,
                '@article' => $row->getSourceProperty('title'),
              ]);
            }
          }
        }

        $i--;
      }

      $html = Html::serialize($doc);
      $field[0]['value'] = $html;

      $row->setSourceProperty($field_name, $field);
    }
  }

  /**
   * Post-migration method of replacing internal links.
   */
  private function postLinkReplace($row, $field_name, $no_links_prior = 0) {
    $field = $row->getSourceProperty($field_name);
    if (!empty($field)) {
      // Search for D7 inline embeds and replace with D8 inline entities.
      $field[0]['value'] = $this->replaceInlineFiles($field[0]['value']);

      // Parse links.
      $doc = Html::load($field[0]['value']);
      $links = $doc->getElementsByTagName('a');
      $i = $links->length - 1;
      $created_year = date('Y', $row->getSourceProperty('created'));

      while ($i >= 0) {
        $link = $links->item($i);
        $href = $link->getAttribute('href');

        // Unlink anchors in body from articles before the given
        // no_links_prior value (default 0).
        if ($created_year < $no_links_prior) {
          $text = $doc->createTextNode($link->nodeValue);
          $link->parentNode->replaceChild($text, $link);
          $doc->saveHTML();
        }
        else {
          // @todo update this to get sitename in place of pharmacy.uiowa.edu.
          if (strpos($href, '/node/') === 0 || stristr($href, 'pharmacy.uiowa.edu/node/')) {
            $nid = explode('node/', $href)[1];

            // @todo update this to allow for easier 'manualLookup' implementation.
            if ($lookup = $this->manualLookup($nid)) {
              $link->setAttribute('href', $lookup);
              $link->parentNode->replaceChild($link, $link);
              $this->logger->info('Replaced internal link @link in article @article.', [
                '@link' => $href,
                '@article' => $row->getSourceProperty('title'),
              ]);

            }
            else {
              $this->logger->notice('Unable to replace internal link @link in article @article.', [
                '@link' => $href,
                '@article' => $row->getSourceProperty('title'),
              ]);
            }
          }
        }

        $i--;
      }

      $html = Html::serialize($doc);
      $field[0]['value'] = $html;

      $row->setSourceProperty($field_name, $field);
    }

    $old_link = $match[1];
    $this->logger->notice($this->t('Old link found... @old_link', [
      '@old_link' => $old_link,
    ]));

    // Check if it's a mailto: link and return if it is.
    if (substr($old_link, 0, 7) == 'mailto:') {
      $this->logger->notice($this->t('Mailto link found...skipping.'));
      return $match[0];
    }

    // If it's an anchor link only, we can skip it.
    // Look only for # after the first position.
    if (strpos($old_link, '#', 1)) {
      $split_anchor = explode('#', $old_link);
      $suffix = '#' . $split_anchor[1];
      $old_link = $split_anchor[0];
    }
    else {
      $suffix = '';
    }

    // Check if it's a direct node path.
    if (substr($old_link, 0, 4) == 'node' || substr($old_link, 0, 5) == '/node') {
      // Split and grab the last part
      // which will be the node number.
      $link_parts = explode('/', $old_link);
      $old_nid = end($link_parts);

      // Check that there is a mapping and set it to the new id.
      if (isset($this->sourceToDestIds[$old_id])) {
        $new_nid = $this->sourceToDestIds[$old_id];
        // Display message in terminal.
        $this->logger->notice($this->t('Old nid... @old_nid', [
          '@old_nid' => $old_nid,
        ]));
        $this->logger->notice($this->t('New nid... @new_nid', [
          '@new_nid' => $new_nid,
        ]));
        $new_link = '<a href="/node/' . $new_id . $suffix . '"';
      }
      // No mapping found, so keep the old link.
      else {
        $new_link = $match[0];
        $this->logger->notice($this->t('No mapping found for nid... @old_nid', [
          '@old_nid' => $old_nid,
        ]));
      }
      return $new_link;
    }

    // We have an absolute link--need to check if it references this
    // site or is external site.
    elseif (substr($old_link, 0, 4) == 'http') {
      $pattern = '|"(https?://)?(www.)?(' . $this->basePath . ')/(.*?)"|';
      if (preg_match($pattern, $old_link, $absolute_path)) {
        $d7_nid = $this->d7Aliases[$absolute_path[4]];
        $new_link = (isset($this->sourceToDestIds[$d7_nid])) ?
          '<a href="/node/' . $this->sourceToDestIds[$d7_nid] . '"' :
          '<a href="/' . $absolute_path[4] . $suffix . '"';
        $this->logger->notice($this->t('New link found from absolute path... @new_link', [
          '@new_link' => $new_link,
        ]));

        return $new_link;
      }
    }

    // If we got here, we should have a relative link
    // that isn't in the /node/id format.
    else {
      $d7_nid = $this->d7Aliases[$old_link];
      $new_link = (isset($this->sourceToDestIds[$d7_nid])) ?
        '<a href="/node/' . $this->sourceToDestIds[$d7_nid] . $suffix . '"' :
        $match[0];

      $this->logger->notice($this->t('New link found from /node/ path... @new_link', [
        '@new_link' => $new_link,
      ]));

      return $new_link;
    }

    // No matches were found--return the unchanged original.
    return $match[0];
  }

  /**
   * Query for a list of nodes which may contain newly broken links.
   */
  private function reportPossibleLinkBreaks($fields) {
    foreach ($fields as $field => $columns) {
      $candidates = \Drupal::database()->select($field, 'f')
        ->fields('f', array_merge($columns, ['entity_id']))
        ->execute()
        ->fetchAllAssoc('entity_id');

      foreach ($candidates as $entity_id => $cols) {
        $oopsie_daisies = [];
        foreach ($cols as $key => $value) {
          if ($key === 'entity_id') {
            continue;
          }

          if (preg_match_all('|<a.*?>(.*?)<\/a>|i', $value, $matches)) {
            $oopsie_daisies[$entity_id] = implode(',', $matches[1]);
          }
        }

        foreach ($oopsie_daisies as $id => $links) {
          $this->logger->notice($this->t('Possible broken links found in node @candidate: @links', [
            '@candidate' => $id,
            '@links' => $links,
          ]));
        }
      }
    }
  }

  /**
   * Query for a list of fields which may contain newly broken links.
   */
  private function checkForPossibleLinkBreaks() {
    // @todo update to check for non-node-specific fields.
    // Check for possible link breaks in standard body fields.
    $query = $this->connection->select('node__body', 'nb')
      ->fields('nb', ['entity_id']);
    $query->condition($query->orConditionGroup()
      ->condition('nb.body_value', $this->basePath, 'LIKE')
      ->condition('nb.body_value', "%<a href%node/%", 'LIKE')
    );
    $result = $query->execute();
    $candidates = $result->fetchCol();

    // @todo If non-node fields, should report the field and the parent entity.
    foreach ($candidates as $candidate) {
      $this->logger->notice($this->t('Possible broken link found in node @candidate', [
        '@candidate' => $candidate,
      ]));
    }

    return $candidates;
  }

}
