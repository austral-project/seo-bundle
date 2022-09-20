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
   * @var DomainsManagement
   */
  protected DomainsManagement $domains;

  /**
   * @param ?DomainsManagement $domains
   */
  public function __construct(?DomainsManagement $domains)
  {
    $this->domains = $domains;
  }

  /**
   * @param ModuleEvent $moduleEvent
   *
   * @throws \Exception
   */
  public function moduleAdd(ModuleEvent $moduleEvent)
  {
    if($moduleEvent->getModule()->getModuleKey() === "seo")
    {
      if($this->domains->getEnabledDomainWithoutVirtual() > 1) {

        /** @var Module $subModule */
        foreach($moduleEvent->getModule()->getChildren() as $subModule)
        {
          $moduleEvent->getModules()->removeModule($subModule);
        }
        $moduleChange = false;
        /** @var DomainInterface $domain */
        foreach($this->domains->getDomainsWithoutVirtual() as $domain)
        {
          $moduleChange = true;
          $moduleEvent->getModules()->generateModuleByDomain(
            $moduleEvent->getModule()->getModuleKey(),
            $moduleEvent->getModule()->getModuleParameters(),
            $domain,
            $moduleEvent->getModule()
          );
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