<?php

namespace Drupal\sitenow_microsite\Routing;

use Drupal\node\Entity\Node;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a dynamic path based off of the redirect uri variable.
 */
class DynamicRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   An array of route objects.
   */
  public function routes() {
    $nids = \Drupal::entityQuery('node')->condition('type', 'conference')->execute();
    $nodes = Node::loadMultiple($nids);

    $routes = new RouteCollection();

    foreach ($nodes as $node) {
      $nid = $node->id();
      $conferenceName = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
      $routes->add(
        'dynamic_pages.' . $nid,
        new Route(
          $conferenceName . '/{page}', [
            '_controller' => '\Drupal\sitenow_microsite\Controller\SitenowMicrositeController::content',
            '_title' => $node->getTitle(),
            'nid' => $nid,
          ],
          [
            '_permission' => 'access content',
          ]
        )
      );
    }

    return $routes;
  }

}
