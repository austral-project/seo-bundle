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

use Austral\SeoBundle\Event\PagesEvent;
use Austral\SeoBundle\Event\PagesSelectObjectsEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Austral Pages Subscriber.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class PagesSubscriber implements EventSubscriberInterface
{

  /**
   * @return array
   */
  public static function getSubscribedEvents()
  {
    return [
      PagesEvent::EVENT_PAGE_INIT                     =>  ["pageInit", 1024],
      PagesSelectObjectsEvent::EVENT_SELECT_OBJECTS   =>  ["selectObjects", 1024],
      PagesEvent::EVENT_PAGE_OBJECT_PUSH              =>  ["objectPush", 1024],
      PagesEvent::EVENT_PAGE_FINISH                   =>  ["pageFinish", 1024],
    ];
  }

  /**
   * @param PagesEvent $pagesEvent
   */
  public function pageInit(PagesEvent $pagesEvent)
  {
  }

  /**
   * @param PagesEvent $pagesEvent
   */
  public function pageFinish(PagesEvent $pagesEvent)
  {
  }

  /**
   * @param PagesEvent $pagesEvent
   */
  public function objectPush(PagesEvent $pagesEvent)
  {
  }

  /**
   * @param PagesSelectObjectsEvent $pagesSelectObjectsEvent
   */
  public function selectObjects(PagesSelectObjectsEvent $pagesSelectObjectsEvent)
  {
  }

}