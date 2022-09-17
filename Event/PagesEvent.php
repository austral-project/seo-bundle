<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Event;

use Austral\EntityBundle\Entity\Interfaces\SeoInterface;
use Austral\SeoBundle\Services\Pages;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral Pages Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class PagesEvent extends Event
{

  const EVENT_PAGE_INIT = "austral.seo.page.init";
  const EVENT_PAGE_OBJECT_PUSH = "austral.seo.page.object.push";
  const EVENT_PAGE_FINISH = "austral.seo.page.finish";

  /**
   * @var Pages
   */
  private Pages $pages;

  /**
   * @var SeoInterface|null
   */
  private ?SeoInterface $object = null;

  /**
   * PagesEvent constructor.
   *
   * @param Pages $pages
   * @param SeoInterface|null $object
   */
  public function __construct(Pages $pages, ?SeoInterface $object = null)
  {
    $this->pages = $pages;
    $this->object = $object;
  }

  /**
   * @return Pages
   */
  public function getPages(): Pages
  {
    return $this->pages;
  }

  /**
   * @param Pages $pages
   *
   * @return $this
   */
  public function setPages(Pages $pages): PagesEvent
  {
    $this->pages = $pages;
    return $this;
  }

  /**
   * @return SeoInterface|null
   */
  public function getObject(): ?SeoInterface
  {
    return $this->object;
  }

  /**
   * @param SeoInterface|null $object
   *
   * @return $this
   */
  public function setObject(?SeoInterface $object): PagesEvent
  {
    $this->object = $object;
    return $this;
  }

}