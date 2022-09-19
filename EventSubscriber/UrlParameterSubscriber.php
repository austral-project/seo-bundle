<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\SeoBundle\EventSubscriber;

use Austral\SeoBundle\Event\UrlParameterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral Pages Subscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterSubscriber implements EventSubscriberInterface
{

  /**
   * @return array
   */
  public static function getSubscribedEvents()
  {
    return [
      UrlParameterEvent::EVENT_START                    =>  ["start", 1024],
      UrlParameterEvent::EVENT_END                      =>  ["end", 1024],
    ];
  }

  /**
   * @param UrlParameterEvent $urlParameterEvent
   */
  public function start(UrlParameterEvent $urlParameterEvent)
  {
  }

  /**
   * @param UrlParameterEvent $urlParameterEvent
   */
  public function end(UrlParameterEvent $urlParameterEvent)
  {
  }

}