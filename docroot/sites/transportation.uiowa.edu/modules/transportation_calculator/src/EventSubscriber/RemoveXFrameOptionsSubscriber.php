<?php

namespace Drupal\transportation_calculator\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Set X-Frame options.
 */
class RemoveXFrameOptionsSubscriber implements EventSubscriberInterface {

  /**
   * Set header 'Content-Security-Policy' and 'X-Frame options'.
   *
   * @param ResponseEvent $event
   */
  public function RemoveXFrameOptions(ResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('X-Frame-Options', 'ALLOW-FROM https://parking.uiowa.edu/');
    $response->headers->set('Content-Security-Policy', 'frame-ancestors https://parking.uiowa.edu/');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['RemoveXFrameOptions', -10];
    return $events;
  }

}
