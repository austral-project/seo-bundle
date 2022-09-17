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

use Austral\AdminBundle\Event\ModuleEvent;
use Austral\AdminBundle\Module\Module;
use Austral\EntityFileBundle\File\Link\Generator;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\HttpBundle\Services\DomainsManagement;

/**
 * Austral ModuleListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ModuleListener
{
  /**
   * @var DomainsManagement|null
   */
  protected ?DomainsManagement $domains = null;

  /**
   * @var Generator|null
   */
  protected ?Generator $fileLinkGenerator;

  /**
   * @param ?DomainsManagement $domains
   * @param Generator|null $fileLinkGenerator
   */
  public function __construct(?DomainsManagement $domains = null, ?Generator $fileLinkGenerator = null)
  {
    $this->domains = $domains;
    $this->fileLinkGenerator = $fileLinkGenerator;
  }


  /**
   * @param ModuleEvent $moduleEvent
   *
   * @throws \Exception
   */
  public function moduleAdd(ModuleEvent $moduleEvent)
  {
    if($moduleEvent->getModule()->getModuleKey() === "seo" && $this->domains)
    {

      /** @var Module $subModule */
      foreach($moduleEvent->getModule()->getChildren() as $subModule)
      {
        $moduleEvent->getModules()->removeModule($subModule);
      }

      if($this->domains->getEnabledDomainWithoutVirtual() > 1) {
        $moduleChange = false;
        $domains = $this->domains->getDomains();
        /** @var DomainInterface $domain */
        foreach($domains as $domain)
        {
          if(!$domain->getIsVirtual())
          {
            $moduleChange = true;
            $moduleEvent->getModules()->generateModuleByDomain(
              $moduleEvent->getModule()->getModuleKey(),
              $moduleEvent->getModule()->getModuleParameters(),
              $domain,
              $moduleEvent->getModule()
            );
          }
        }
        if($moduleChange)
        {
          $moduleEvent->getModule()->setActionName("listChildrenModules");
          $moduleEvent->getModule()->setPathActions(array());
        }
      }
    }

  }

}