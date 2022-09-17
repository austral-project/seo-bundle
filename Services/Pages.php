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
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\Entity\Interfaces\FilterByDomainInterface;
use Austral\EntityBundle\Entity\Interfaces\RobotInterface;
use Austral\EntityBundle\Entity\Interfaces\SeoInterface;
use Austral\SeoBundle\Entity\Traits\EntityRobotTrait;
use Austral\SeoBundle\Event\PagesEvent;
use Austral\SeoBundle\Event\PagesSelectObjectsEvent;
use Austral\ToolsBundle\AustralTools;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Austral Pages service.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @deprecated
 */
Class Pages
{

  /**
   * @var EntityManagerInterface
   */
  protected EntityManagerInterface $entityManager;

  /**
   * @var Request|null
   */
  protected ?Request $request;

  /**
   * @var EventDispatcherInterface|null
   */
  protected ?EventDispatcherInterface $dispatcher;
  
  /**
   * @var string|null
   */
  protected ?string $filterDomainId = null;

  /**
   * @var string|null
   */
  protected ?string $currentLanguage;

  /**
   * @var array
   */
  protected array $entities = array();

  /**
   * @var array
   */
  protected array $objects = array();

  /**
   * @var array
   */
  protected array $objectsByEntity = array();

  /**
   * @var array
   */
  protected array $objectsByCode = array();

  /**
   * @var array
   */
  protected array $urls = array();

  /**
   * @var array
   */
  protected array $urlsByEntity = array();

  /**
   * @var array
   */
  protected array $conflictUrls = array();

  /**
   * @var mixed|null
   */
  protected $queryToSelectObjectSeo = null;

  /**
   * @var bool
   */
  protected bool $byStatus = true;

  /**
   * @var AuthorizationCheckerInterface
   */
  protected AuthorizationCheckerInterface $authorizationChecker;

  /**
   * Page constructor.
   *
   * @param RequestStack $request
   * @param EntityManagerInterface $entityManager
   * @param EventDispatcherInterface $dispatcher
   * @param AuthorizationCheckerInterface $authorizationChecker
   */
  public function __construct(RequestStack $request, EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher, AuthorizationCheckerInterface $authorizationChecker)
  {
    $this->entityManager = $entityManager;
    $this->dispatcher = $dispatcher;
    $this->request = $request->getCurrentRequest();
    $this->currentLanguage = $this->request ? $this->request->attributes->get('language', $this->request->getLocale()) : null;
    $this->authorizationChecker = $authorizationChecker;
    $this->initEntity();
  }

  /**
   * @param bool $refresh
   *
   * @return $this
   */
  public function initEntity(bool $refresh = false): Pages
  {
    $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
    if((count($this->entities) == 0) || $refresh)
    {
      $this->entities = array();
      foreach($metadata as $classMeta)
      {
        $className = $classMeta->getName();
        if(strpos($className,"Entity\Base") === false && strpos($className,"Translate") === false)
        {
          if(AustralTools::usedImplements($className, SeoInterface::class))
          {
            $entityName = trim(str_replace($classMeta->namespace, "", $className), "\\");
            if(!array_key_exists($entityName, $this->entities))
            {
              $this->entities[$entityName] = $className;
            }
          }
        }
      }
    }
    return $this;
  }

  /**
   * @return string|null
   */
  public function getFilterDomainId(): ?string
  {
    return $this->filterDomainId;
  }

  /**
   * @param string|null $filterDomainId
   *
   * @return $this
   */
  public function setFilterDomainId(?string $filterDomainId = null): Pages
  {
    $this->filterDomainId = $filterDomainId;
    return $this;
  }

  /**
   * @return bool
   */
  public function getByStatus(): bool
  {
    return $this->byStatus;
  }

  /**
   * @param bool $byStatus
   *
   * @return $this
   */
  public function setByStatus(bool $byStatus): Pages
  {
    $this->byStatus = $byStatus;
    return $this;
  }

  /**
   * @param $currentLanguage
   *
   * @return $this
   */
  public function setCurrentLanguage($currentLanguage): Pages
  {
    $this->currentLanguage = $currentLanguage;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getCurrentLanguage(): ?string
  {
    return $this->currentLanguage;
  }

  /**
   * @return array
   */
  public function getEntities(): array
  {
    return $this->entities;
  }

  /**
   * @return $this
   */
  public function reinitObject(): Pages
  {
    $this->objects = array();
    $this->urls = array();
    $this->conflictUrls = array();
    $this->objectsByCode = array();
    $this->objectsByEntity = array();
    $this->initObjects();
    return $this;
  }

  /**
   * @return $this
   */
  protected function initObjects(): Pages
  {
    if(!$this->objects)
    {
      $pagesEvent = new PagesEvent($this);
      $this->dispatcher->dispatch($pagesEvent, PagesEvent::EVENT_PAGE_INIT);
      $this->objectsByEntity = array();
      foreach($this->entities as $className)
      {
        $objects = $this->selectObjectsSeo($className);
        $this->objects = array_merge($this->objects, $objects);
      }

      /** @var EntityInterface|SeoInterface $object */
      foreach($this->objects as $object)
      {
        $this->pushObject($object);
        $pagesEvent->setObject($object);
        $pagesEvent = new PagesEvent($this, $object);
        $this->dispatcher->dispatch($pagesEvent, PagesEvent::EVENT_PAGE_OBJECT_PUSH);
      }
      $pagesEvent = new PagesEvent($this);
      $this->dispatcher->dispatch($pagesEvent, PagesEvent::EVENT_PAGE_FINISH);
    }
    ksort($this->urls);
    ksort($this->urlsByEntity);
    ksort($this->objectsByCode);
    return $this;
  }

  /**
   * @param SeoInterface|EntityInterface $object
   *
   * @return $this
   */
  public function pushObject(SeoInterface $object): Pages
  {
    if(AustralTools::usedImplements(get_class($object), SeoInterface::class))
    {
      $entityName = $object->getClassnameForMapping();
      if(!array_key_exists($entityName, $this->objectsByEntity))
      {
        $this->objectsByEntity[$entityName] = array();
      }
      if(!array_key_exists($entityName, $this->urlsByEntity))
      {
        $this->urlsByEntity[$entityName] = array();
      }
      $this->objectsByEntity[$entityName][$object->getId()] = $object;
      $this->objectsByCode["{$object->getClassnameForMapping()}_{$object->getKeyname()}"] = $object;
      $this->addObjectUrl($object, $object->getRefUrl());
    }
    return $this;
  }

  /**
   * @param SeoInterface $object
   * @param string|null $url
   *
   * @return $this
   */
  public function addObjectUrl(SeoInterface $object, ?string $url = ""): Pages
  {
    $this->urlsByEntity[$object->getClassnameForMapping()][$url] = $object;
    if(array_key_exists($url, $this->urls))
    {
      if(array_key_exists($url, $this->conflictUrls))
      {
        $this->conflictUrls[$url] = array();
      }
      $this->conflictUrls[$url][$object->getId()] = $object;
    }
    else
    {
      $this->urls[$url] = $object;
    }
    ksort($this->urlsByEntity[$object->getClassnameForMapping()]);
    return $this;
  }

  /**
   * @param string $url
   *
   * @return $this
   */
  public function removeUrl(string $url): Pages
  {
    if($object = $this->retreiveByRefUrl($url))
    {
      unset($this->urls[$url]);
      unset($this->urlsByEntity[$object->getClassname()][$url]);
    }
    return $this;
  }

  /**
   * @param $className
   *
   * @return array
   */
  protected function selectObjectsSeo($className): array
  {
    $pagesEvent = new PagesSelectObjectsEvent($this->entityManager, $className, $this->byStatus);
    $this->dispatcher->dispatch($pagesEvent, PagesSelectObjectsEvent::EVENT_SELECT_OBJECTS);
    if(!$query = $pagesEvent->getQuery())
    {
      $queryBuilder = $this->entityManager->getRepository($className)->createQueryBuilder("pages");
      if($hasTranslate = method_exists($className, "getTranslateCurrent"))
      {
        $queryBuilder->leftJoin('pages.translates', "translates")->addSelect('translates');
      }

      if($this->byStatus)
      {
        if(!$this->authorizationChecker->isGranted("ROLE_ADMIN_ACCESS"))
        {
          if(AustralTools::usedImplements($className, RobotInterface::class) && $hasTranslate)
          {
            $queryBuilder->andWhere("translates.status = :status")
              ->setParameter("status", "published");
          }
          elseif(AustralTools::usedImplements($className, RobotInterface::class))
          {
            $queryBuilder->andWhere("pages.status = :status")
              ->setParameter("status", "published");
          }
        }
        else
        {
          if(AustralTools::usedImplements($className, RobotInterface::class) && $hasTranslate)
          {
            $queryBuilder->andWhere("translates.status = :status OR translates.status = :statusDraft")
              ->setParameter("status", "published")
              ->setParameter("statusDraft", "draft");
          }
          elseif(AustralTools::usedImplements($className, RobotInterface::class))
          {
            $queryBuilder->andWhere("pages.status = :status OR pages.status = :statusDraft")
              ->setParameter("status", "published")
              ->setParameter("statusDraft", "draft");
          }
        }
      }

      if(AustralTools::usedImplements($className, FilterByDomainInterface::class) && $this->getFilterDomainId())
      {
        $queryBuilder->andWhere("pages.domainId = :domainId OR pages.domainId IS NULL")
          ->setParameter("domainId", $this->getFilterDomainId());
      }

      $query = $queryBuilder->getQuery();
    }

    try {
      $objects = $query->execute();
    } catch (\Doctrine\Orm\NoResultException $e) {
      $objects = array();
    }
    return $objects;
  }

  /**
   * @return array
   */
  public function getObjects(): array
  {
    $this->initObjects();
    return $this->objects;
  }

  /**
   * @param string|null $entityName
   *
   * @return array
   */
  public function getObjectsByEntity(string $entityName = null): array
  {
    $this->initObjects();
    return $entityName ? AustralTools::getValueByKey($this->objectsByEntity, $entityName, array()) : $this->objectsByEntity;
  }

  /**
   * @return array
   */
  public function getUrls(): array
  {
    $this->initObjects();
    return $this->urls;
  }

  /**
   * @return array
   */
  public function getUrlsByEntity(): array
  {
    $this->initObjects();
    return $this->urlsByEntity;
  }

  /**
   * @param $url
   *
   * @return SeoInterface|EntityInterface|null
   */
  public function retreiveByRefUrl($url): ?SeoInterface
  {
    return AustralTools::getValueByKey($this->getUrls(), $url, null);
  }

  /**
   * @param string $code
   *
   * @return SeoInterface|null
   */
  public function retreiveByCode(string $code): ?SeoInterface
  {
    $this->initObjects();
    return AustralTools::getValueByKey($this->objectsByCode, $code, null);
  }

  /**
   * @param string $entitName
   * @param $id
   *
   * @return SeoInterface|null
   */
  public function retreiveByEntityAndId(string $entitName, $id): ?SeoInterface
  {
    $this->initObjects();
    if($objectsByEntity = AustralTools::getValueByKey($this->objectsByEntity, $entitName, null))
    {
      return AustralTools::getValueByKey($objectsByEntity, $id, null);
    }
    return null;
  }

  /**
   * @param string $entitName
   *
   * @return string|null
   */
  public function retreiveEntityName(string $entitName): ?string
  {
    return AustralTools::getValueByKey($this->entities, $entitName, null);
  }

  /**
   * @return array
   */
  public function getConflictUrls(): array
  {
    return $this->conflictUrls;
  }

  /**
   * @param string $refUrl
   *
   * @return bool
   */
  public function isConflictUrl(string $refUrl): bool
  {
    return array_key_exists($refUrl, $this->conflictUrls);
  }

}