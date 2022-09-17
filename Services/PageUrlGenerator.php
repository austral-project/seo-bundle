<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Services;

use Austral\SeoBundle\Configuration\SeoConfiguration;
use Austral\EntityBundle\Entity\Interfaces\SeoInterface;
use Austral\SeoBundle\Entity\Interfaces\RedirectionInterface;
use Austral\SeoBundle\EntityManager\RedirectionEntityManager;
use Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface;
use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;
use Austral\ToolsBundle\AustralTools;
use Austral\WebsiteBundle\Entity\Interfaces\PageInterface;

use Austral\EntityBundle\Entity\EntityInterface;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

/**
 * Austral Page url generator service.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @deprecated
 * @final
 */
class PageUrlGenerator
{

  /**
   * @var EntityManagerInterface
   */
  protected EntityManagerInterface $entityManager;

  /**
   * @var array
   */
  protected array $objects = array();

  /**
   * @var string|null
   */
  protected ?string $homepageId = null;

  /**
   * @var SeoConfiguration
   */
  protected SeoConfiguration $SeoConfiguration;

  /**
   * @var RedirectionEntityManager
   */
  protected RedirectionEntityManager $redirectionManager;

  /**
   * PageUrlGenerator constructor.
   *
   * @param EntityManagerInterface $entityManager
   * @param SeoConfiguration $SeoConfiguration
   * @param RedirectionEntityManager $redirectionManager
   */
  public function __construct(EntityManagerInterface $entityManager, SeoConfiguration $SeoConfiguration, RedirectionEntityManager $redirectionManager)
  {
    $this->entityManager = $entityManager;
    $this->SeoConfiguration = $SeoConfiguration;
    $this->redirectionManager = $redirectionManager;
  }

  /**
   * @param EntityInterface|object $object
   * @param EventArgs|null $eventArgs
   *
   * @return $this
   * @throws NonUniqueResultException
   */
  public function generateUrl(EntityInterface $object, EventArgs $eventArgs = null): PageUrlGenerator
  {
    if($object instanceof TranslateChildInterface)
    {
      $objectMaster = $object->getMaster();
    }
    else
    {
      $objectMaster = $object;
    }

    $generateUrl = false;
    if($objectMaster instanceof SeoInterface)
    {
      $generateUrl = true;
      if($objectMaster instanceof PageInterface && $objectMaster->getIsHomepage())
      {
        $objectMaster->setRefUrl(null);
        $objectMaster->setRefUrlLast(null);
        $generateUrl = false;
      }
    }

    if($generateUrl && (!$eventArgs || !method_exists($eventArgs, "hasChangedField") || (method_exists($eventArgs, "hasChangedField") && $eventArgs->hasChangedField('refUrlLast'))))
    {
      $oldRefUrl = $object->getRefUrl();
      if(!$objectMaster->getRefUrlLast())
      {
        $objectMaster->setRefUrlLast($object->__toString());
      }
      $this->generateUrlWithParent($objectMaster);
      if(method_exists($objectMaster, "getChildren"))
      {
        foreach($objectMaster->getChildren() as $child)
        {
          $this->generateUrl($child);
        }
      }
      if(method_exists($objectMaster, "getChildrenEntities"))
      {
        foreach($objectMaster->getChildrenEntities() as $child)
        {
          $this->generateUrl($child);
        }
      }
      $this->objects[] = array(
        "object"          =>  $objectMaster,
        "newRefUrl"       =>  $object->getRefUrl(),
        "oldRefUrl"       =>  $oldRefUrl,
        "redirections"    =>  $this->generateRedirectionAuto($objectMaster, $object->getRefUrl(), $oldRefUrl)
      );
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getObjects(): array
  {
    return $this->objects;
  }

  /**
   * @param SeoInterface $object
   *
   * @return SeoInterface
   */
  protected function generateUrlWithParent(SeoInterface $object): SeoInterface
  {
    if($parentPage = $object->getPageParent())
    {
      $url = trim("{$parentPage->getRefUrl()}/{$object->getRefUrlLast()}", "/");
    }
    else
    {
      $url = $object->getRefUrlLast();
    }
    $object->setRefUrl($url);
    return $object;
  }

  /**
   * @param SeoInterface $object
   * @param string|null $newRefUrl
   * @param string|null $oldRefUrl
   *
   * @return array
   * @throws NonUniqueResultException
   */
  public function generateRedirectionAuto(SeoInterface $object, string $newRefUrl = null, string $oldRefUrl = null): array
  {
    $redirections = array();
    if($this->SeoConfiguration->get('redirection.auto') == true)
    {
      if(($oldRefUrl !== $newRefUrl) && $oldRefUrl && $newRefUrl)
      {
        $currentLanguage = null;
        if($object instanceof TranslateMasterInterface)
        {
          $currentLanguage = $object->getLanguageCurrent();
        }
        /** @var RedirectionInterface|null $redirection */
        $redirection = $this->redirectionManager->retreiveByUrlSource($newRefUrl, $currentLanguage);
        if($redirection && ($redirection->getRelationEntityName() !== get_class($object) || $redirection->getRelationEntityId() == $object->getId()))
        {
          $redirectionOther = $redirection;
          $redirectionOther->setIsActive(false);
          $redirections[] = $redirectionOther;
          $redirection = null;
        }

        if(!$redirection)
        {
          $redirection = $this->redirectionManager->create();
          $redirection->setIsActive(true);
          $redirection->setIsAutoGenerate(true);
        }
        $redirection->setRelationEntityName(get_class($object));
        $redirection->setRelationEntityId($object->getId());
        $redirection->setUrlSource($oldRefUrl);
        $redirection->setUrlDestination($newRefUrl);
        if($object instanceof TranslateMasterInterface)
        {
          $redirection->setLanguage($currentLanguage);
        }
        $redirections[] = $redirection;
      }
    }
    return $redirections;
  }


}