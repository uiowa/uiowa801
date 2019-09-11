<?php

namespace Drupal\sitenow_microsite\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for SiteNow MicroSite routes.
 */
class SitenowMicrositeController extends ControllerBase {

  /**
   * Builds the response.
   *
   * @param int $nid
   *   The nid.
   * @param string $page
   *   The path of custom page.
   *
   * @return array
   *   A renderable array for the page.
   */
  public function content($nid, $page) {
    $node = Node::load($nid);
    $paths = [];
    $title = $this->t('This works!');
    foreach ($node->get('field_custom_pages') as $paragraph) {
      if ($paragraph->entity->getType() == 'custom_page') {
        $custom_page = $paragraph->entity;
        if (!empty($custom_page)) {
          $path = $custom_page->field_custom_page_path->value;
          if ($page == $path) {
            $title = $custom_page->field_custom_page_title->value;
          }
          $paths[] = $path;
        }
      }
    }
    if (in_array($page, $paths)) {
      $build['content'] = [
        '#type' => 'item',
        '#markup' => '<h2>' . $title . '</h2>',
      ];
      return $build;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

}
