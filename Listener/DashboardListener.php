<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Listener;

use Austral\AdminBundle\Event\DashboardEvent;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Services\UrlParameterManagement;

use Austral\AdminBundle\Dashboard\Values as DashboardValues;

/**
 * Austral DashboardListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DashboardListener
{

  /**
   * @var UrlParameterManagement
   */
  protected UrlParameterManagement $urlParameterManagement;

  /**
   * @param UrlParameterManagement $urlParameterManagement
   */
  public function __construct(UrlParameterManagement $urlParameterManagement)
  {
    $this->urlParameterManagement = $urlParameterManagement;
  }


  /**
   * @param DashboardEvent $dashboardEvent
   *
   * @throws \Exception
   */
  public function dashboard(DashboardEvent $dashboardEvent)
  {
    $dashboardTilePagesPublished = new DashboardValues\Tile("urls_published");
    $dashboardTilePagesPublished->setEntitled("dashboard.tiles.urls.published.entitled")
      ->setIsTranslatableText(true)
      ->setColorNum(4)
      ->setPicto("link")
      ->setValue($this->urlParameterManagement->getTotalUrlsByStatus(UrlParameterInterface::STATUS_PUBLISHED));


    $nbDraftAndUnPublished = $this->urlParameterManagement->getTotalUrlsByStatus(UrlParameterInterface::STATUS_DRAFT);
    $nbDraftAndUnPublished += $this->urlParameterManagement->getTotalUrlsByStatus(UrlParameterInterface::STATUS_UNPUBLISHED);
    $dashboardTilePagesDraft = new DashboardValues\Tile("urls_draft");
    $dashboardTilePagesDraft->setEntitled("dashboard.tiles.urls.draft.entitled")
      ->setIsTranslatableText(true)
      ->setPicto("link")
      ->setColorNum(5)
      ->setValue($nbDraftAndUnPublished);


    $dashboardTileUrlConflict = new DashboardValues\Tile("urls_conflicts");
    $dashboardTileUrlConflict->setEntitled("dashboard.tiles.urls.conflicts.entitled")
      ->setIsTranslatableText(true)
      ->setPicto("link")
      ->setColorNum(3)
      ->setValue($this->urlParameterManagement->countUrlParametersConflict());

    $dashboardEvent->getDashboardBlock()->getChild("austral_tiles_values")
      ->addValue($dashboardTilePagesPublished)
      ->addValue($dashboardTilePagesDraft)
      ->addValue($dashboardTileUrlConflict);


  }

}