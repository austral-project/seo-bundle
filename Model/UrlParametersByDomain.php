<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Model;

use App\Entity\Austral\SeoBundle\UrlParameter;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\EntityBundle\EntityManager\EntityManagerORMInterface;
use Austral\HttpBundle\Mapping\DomainFilterMapping;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Entity\Interfaces\TreePageInterface;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\HttpBundle\Entity\Interfaces\DomainInterface;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\EntityManager\UrlParameterEntityManager;
use Austral\SeoBundle\Mapping\UrlParameterMapping;
use Austral\SeoBundle\Services\UrlParameterMigrate;
use Austral\ToolsBundle\AustralTools;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Austral UrlParameters By Domain.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParametersByDomain
{

  /**
   * @var string
   */
  protected string $debugContainer = "";

  /**
   * @var EventDispatcherInterface
   */
  protected EventDispatcherInterface $dispatcher;

  /**
   * @var UrlParameterMigrate
   */
  protected UrlParameterMigrate $urlParameterMigrate;

  /**
   * @var DomainInterface
   */
  protected DomainInterface $domain;

  /**
   * @var EntityManagerORMInterface
   */
  protected EntityManagerORMInterface $entityManager;

  /**
   * @var UrlParameterEntityManager
   */
  protected UrlParameterEntityManager $urlParameterEntityManager;

  /**
   * @var array
   */
  protected array $entitiesMapping = array();

  /**
   * @var array
   */
  protected array $nbUrlParametersStatus = array(
    UrlParameterInterface::STATUS_PUBLISHED =>  0,
    UrlParameterInterface::STATUS_DRAFT =>  0,
    UrlParameterInterface::STATUS_UNPUBLISHED =>  0
  );

  /**
   * @var array
   */
  protected array $urlParameters = array();

  /**
   * @var array
   */
  protected array $urlParametersWithoutObject = array();

  /**
   * @var array
   */
  protected array $urlParametersByPath = array();

  /**
   * @var array
   */
  protected array $urlParametersByObjectKey = array();

  /**
   * @var array
   */
  protected array $urlParametersByObjectClassname = array();

  /**
   * @var array
   */
  protected array $urlParametersConflict = array();

  /**
   * @var array
   */
  protected array $keysForObjectLink = array();

  /**
   * @var array
   */
  protected array $nameByKeyLinks = array();

  /**
   * @var array
   */
  protected array $pathByKeyLinks = array();

  /**
   * @var array
   */
  protected array $objectsMapping = array();

  /**
   * @var array
   */
  protected array $domainIdByUrlParameterId = array();

  /**
   * @var bool
   */
  protected bool $isVirtual = false;

  /**
   * @var string|null
   */
  protected ?string $currentLanguage = null;

  /**
   * @param EventDispatcherInterface $dispatcher
   * @param DomainInterface $domain
   * @param EntityManagerORMInterface $entityManager
   * @param UrlParameterEntityManager $urlParameterEntityManager
   * @param UrlParameterMigrate $urlParameterMigrate
   * @param string $currentLanguage
   * @param array $entitiesMapping
   * @param array $keysForObjectLink
   */
  public function __construct(EventDispatcherInterface $dispatcher,
    DomainInterface $domain,
    EntityManagerORMInterface $entityManager,
    UrlParameterEntityManager $urlParameterEntityManager,
    UrlParameterMigrate $urlParameterMigrate,
    string $currentLanguage,
    array $entitiesMapping = array(),
    array $keysForObjectLink = array()
  )
  {
    $this->dispatcher = $dispatcher;
    $this->domain = $domain;
    $this->entityManager = $entityManager;
    $this->urlParameterEntityManager = $urlParameterEntityManager;
    $this->urlParameterMigrate = $urlParameterMigrate;
    $this->entitiesMapping = $entitiesMapping;
    $this->keysForObjectLink = $keysForObjectLink;
    $this->debugContainer = self::class;
    $this->currentLanguage = $currentLanguage;
    $this->isVirtual = $this->domain->getIsVirtual();
  }

  /**
   * @return DomainInterface
   */
  public function getDomain(): DomainInterface
  {
    return $this->domain;
  }

  /**
   * @return bool
   */
  public function getIsVirtual(): bool
  {
    return $this->isVirtual;
  }

  /**
   * @param array $urlParameters
   *
   * @return $this
   */
  public function build(array $urlParameters): UrlParametersByDomain
  {
    $this->urlParameters = $urlParameters;
    /** @var UrlParameterInterface $urlParameter */
    foreach($this->urlParameters as $urlParameter)
    {
      $this->hydrate($urlParameter);
    }
    return $this;
  }

  /**
   * @return array
   */
  public function getTreeUrlParameters(): array
  {
    $treeUrlParameters = $this->treeParse(AustralTools::arrayByFlatten($this->urlParametersByPath, "/"));
    ksort($treeUrlParameters);
    return $treeUrlParameters;
  }

  /**
   * @return array
   */
  public function getUrlParametersPath(): array
  {
    $urlParametersPath = $this->urlParametersByPath;
    ksort($urlParametersPath);
    return $urlParametersPath;
  }

  /**
   * @return array
   */
  public function getUrlParametersPathIndexed(): array
  {
    $urlParametersPathForSitemap = array();
    /** @var UrlParameterInterface $urlParameter */
    foreach ($this->urlParameters as $urlParameter)
    {
      if($urlParameter->getInSitemap() && $urlParameter->getIsIndex() && $urlParameter->isPublished())
      {
        $urlParametersPathForSitemap[$urlParameter->getPath()] = $urlParameter->getId();
      }
    }
    ksort($urlParametersPathForSitemap);
    return $urlParametersPathForSitemap;
  }

  /**
   * @return array
   */
  public function getUrlParameters(): array
  {
    return $this->urlParameters;
  }

  /**
   * @return array
   */
  public function getUrlParametersConflict(): array
  {
    return $this->urlParametersConflict;
  }

  /**
   * @param $values
   * @param array $urlsByDomainWithTree
   *
   * @return array
   */
  protected function treeParse($values, array $urlsByDomainWithTree = array()): array
  {
    foreach($values as $key => $value)
    {
      $urlParameter = $this->getUrlParameterById($value["element"]);
      if($urlParameter && $urlParameter->getIsTreeView())
      {
        $urlsByDomainWithTree[$key]["urlParameter"] = $urlParameter;
        unset($value["element"]);
        $urlsByDomainWithTree[$key]["children"] = $this->treeParse($value);
      }
    }
    return $urlsByDomainWithTree;
  }

  /**
   * @param string $status
   *
   * @return int
   */
  public function getNbUrlParametersByStatus(string $status): int
  {
    return AustralTools::getValueByKey($this->nbUrlParametersStatus, $status, 0);
  }

  /**
   * @param string $classname
   *
   * @return EntityMapping|null
   */
  public function getEntityMappingByObjectClassname(string $classname): ?EntityMapping
  {
    return $this->hasEntityMappingByObjectClassname($classname) ? $this->entitiesMapping[$classname] : null;
  }

  /**
   * @param string $classname
   *
   * @return bool
   */
  public function hasEntityMappingByObjectClassname(string $classname): bool
  {
    return array_key_exists($classname, $this->entitiesMapping);
  }

  /**
   * @param EntityInterface $object
   *
   * @return bool
   */
  public function objectIsMapping(EntityInterface $object): bool
  {
    $objectKey = "{$object->getClassnameForMapping()}::{$object->getId()}";
    return array_key_exists($objectKey, $this->objectsMapping);
  }


  /**
   * @param string $urlParameterId
   * @param bool $objectInit
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParameterById(string $urlParameterId, bool $objectInit = false): ?UrlParameterInterface
  {
    /** @var ?UrlParameter $urlParameter */
    $urlParameter = array_key_exists($urlParameterId, $this->urlParameters) ? $this->urlParameters[$urlParameterId] : null;
    if($urlParameter && $objectInit)
    {
      if($entityMapping = $this->getEntityMappingByObjectClassname($urlParameter->getObjectClass()))
      {
        try {
          if($object = $this->entityManager->getRepository($entityMapping->entityClass)->retreiveById($urlParameter->getObjectId()))
          {
            $urlParameter->setObject($object);
          }
        }
        catch (\Exception $e) {

        }
      }
    }
    return $urlParameter;
  }

  /**
   * @param string $path
   * @param bool $objectInit
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParameterByPath(string $path, bool $objectInit = false): ?UrlParameterInterface
  {
    $urlParameterId = array_key_exists($path, $this->urlParametersByPath) ? $this->urlParametersByPath[$path] : null;
    return $urlParameterId ? $this->getUrlParameterById($urlParameterId, $objectInit) : null;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParameterByObject(EntityInterface $object): ?UrlParameterInterface
  {
    return $this->getUrlParameterByObjectClassnameAndId($object->getClassnameForMapping(), $object->getId());
  }

  /**
   * @param string $classname
   *
   * @return string
   */
  protected function getObjectReelClassname(string $classname): string
  {
    return array_key_exists($classname, $this->keysForObjectLink) ? $this->keysForObjectLink[$classname] : $classname;
  }

  /**
   * @return array
   */
  public function getDomainIdByUrlParameterId(): array
  {
    return $this->domainIdByUrlParameterId;
  }

  /**
   * @return array
   */
  public function getUrlParametersWithoutObject(): array
  {
    return $this->urlParametersWithoutObject;
  }

  /**
   * @param string $classname
   * @param $objectId
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParametersWithoutObjectByClassnameAndId(string $classname, $objectId): ?UrlParameterInterface
  {
    return array_key_exists("{$classname}::{$objectId}", $this->urlParametersWithoutObject) ? $this->urlParametersWithoutObject["{$classname}::{$objectId}"] : null;
  }

  /**
   * @param EntityInterface $object
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParametersWithoutObjectByObject(EntityInterface $object): ?UrlParameterInterface
  {
    return $this->getUrlParametersWithoutObjectByClassnameAndId($object->getClassnameForMapping(), $object->getId());
  }

  /**
   * @return array
   */
  public function getUrlParametersByObjectKey(): array
  {
    return $this->urlParametersByObjectKey;
  }

  /**
   * @return array
   */
  public function getNameByKeyLinks(): array
  {
    return $this->nameByKeyLinks;
  }

  /**
   * @param string $classname
   * @param $objectId
   *
   * @return ?UrlParameterInterface
   */
  public function getUrlParameterByObjectClassnameAndId(string $classname, $objectId): ?UrlParameterInterface
  {
    $classname = $this->getObjectReelClassname($classname);
    $objectKey = "{$classname}::{$objectId}";
    $urlParameterId = array_key_exists($objectKey, $this->urlParametersByObjectKey) ? $this->urlParametersByObjectKey[$objectKey] : null;
    return $urlParameterId && array_key_exists($urlParameterId, $this->urlParameters) ? $this->urlParameters[$urlParameterId] : null;
  }

  /**
   * @param string $classname
   *
   * @return array
   */
  public function getUrlParametersByObjectClassname(string $classname): array
  {
    $classname = $this->getObjectReelClassname($classname);
    return array_key_exists($classname, $this->urlParametersByObjectClassname) ? $this->urlParametersByObjectClassname[$classname] : array();
  }

  /**
   * @param string $classname
   * @param string|int $objectId
   *
   * @return EntityInterface|null
   */
  public function getObjectByClassnameAndId(string $classname, $objectId): ?EntityInterface
  {
    $classname = $this->getObjectReelClassname($classname);
    return AustralTools::getValueByKey($this->objectsMapping, "{$classname}::{$objectId}", null);
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return EntityInterface|null
   */
  public function getObjectByUrlParameter(UrlParameterInterface $urlParameter): ?EntityInterface
  {
    return $this->getObjectByClassnameAndId($urlParameter->getObjectClass(), $urlParameter->getObjectId());
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return UrlParametersByDomain
   */
  public function hydrate(UrlParameterInterface $urlParameter): UrlParametersByDomain
  {
    if(!array_key_exists($urlParameter->getId(), $this->urlParameters))
    {
      $this->urlParameters[$urlParameter->getId()] = $urlParameter;
    }
    $urlParameter->setDomainId($this->domain->getId());
    $urlParameter->setDomain($this->domain);
    if($this->isVirtual) {
      $urlParameter->setKeyLink("{$urlParameter->getObjectClassShort()}::{$urlParameter->getObjectId()}");
    }
    else {
      $urlParameter->setKeyLink("{$urlParameter->getClassname()}::{$urlParameter->getId()}");
    }
    $this->pathByKeyLinks[$urlParameter->getKeyLink()] = $urlParameter->getPath();
    $this->domainIdByUrlParameterId[$urlParameter->getId()] = $this->domain->getId();
    $this->nameByKeyLinks[$urlParameter->getKeyLink()] = $urlParameter->getName();

    if($urlParameter->getObjectRelation())
    {
      $this->objectsMapping["{$urlParameter->getObjectRelation()}"] = $urlParameter->getObject();
      $this->pathByKeyLinks[$urlParameter->getObjectRelation()] = $urlParameter->getPath();
      $this->urlParametersByObjectKey[$urlParameter->getObjectRelation()] = $urlParameter->getId();
      $this->urlParametersByObjectClassname[$urlParameter->getObjectClass()][$urlParameter->getObjectId()] = $urlParameter->getId();
    }

    /** @var string $urlCompare */
    if($urlCompare = AustralTools::getValueByKey($this->urlParametersByPath, $urlParameter->getPath()))
    {
      if($urlCompare !== $urlParameter->getId())
      {
        if(!array_key_exists($urlParameter->getPath(), $this->urlParametersConflict))
        {
          $this->urlParametersConflict[$urlParameter->getPath()] = array(
            $urlCompare
          );
        }
        $this->urlParametersConflict[$urlParameter->getPath()][] = $urlParameter->getId();
      }
    }
    else
    {
      $this->urlParametersByPath[$urlParameter->getPath()] = $urlParameter->getId();
    }
    $this->nbUrlParametersStatus[$urlParameter->getStatus()]++;
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return $this
   */
  public function remove(UrlParameterInterface $urlParameter): UrlParametersByDomain
  {
    if(array_key_exists($urlParameter->getId(), $this->urlParameters))
    {
      $this->nbUrlParametersStatus[$urlParameter->getStatus()]--;
      unset($this->urlParameters[$urlParameter->getId()]);
      unset($this->urlParametersByPath[$urlParameter->getPath()]);
      unset($this->pathByKeyLinks[$urlParameter->getKeyLink()]);
      unset($this->nameByKeyLinks[$urlParameter->getKeyLink()]);
    }
    return $this;
  }

  /**
   * @param EntityInterface $objectSource
   * @param EntityInterface $object
   *
   * @return UrlParameterInterface|null
   * @throws \Exception
   */
  public function duplicateUrlParameterByObject(EntityInterface $objectSource, EntityInterface $object): ?UrlParameterInterface
  {
    /** @var UrlParameterInterface $urlParameterSource */
    if($urlParameterSource = $this->getUrlParameterByObject($objectSource))
    {
      $this->objectsMapping["{$object->getClassnameForMapping()}::{$object->getId()}"] = $object;

      /** @var UrlParameterInterface|EntityInterface $urlParameter */
      $urlParameter = $this->urlParameterEntityManager->duplicate($urlParameterSource);
      $urlParameter->setLanguage($this->currentLanguage);

      $uniqueKey = null;
      if($urlParameter->getDomainId() === $urlParameterSource->getDomainId())
      {
        $uniqueKey = "-copy-".AustralTools::random(4);
      }
      $urlParameter->setPathLast("{$urlParameter->getPathLast()}{$uniqueKey}")
        ->setPath("{$urlParameter->getPath()}-copy-{$uniqueKey}")
        ->setObjectRelation("{$object->getClassnameForMapping()}::{$object->getId()}")
        ->setObject($object)
        ->setStatus(UrlParameterInterface::STATUS_UNPUBLISHED)
        ->setKeyLink("{$urlParameter->getClassname()}::{$urlParameter->getId()}");
      if(!$this->isVirtual) {
        $this->urlParameterEntityManager->update($urlParameter, false);
      }
      return $urlParameter;
    }
    return null;
  }

  /**
   * @param EntityInterface|object $object
   *
   * @return $this
   */
  public function deleteUrlParameterByObject(EntityInterface $object): UrlParametersByDomain
  {
    /** @var UrlParameterInterface|EntityInterface $urlParameter */
    if($urlParameter = $this->getUrlParameterByObject($object))
    {
      $this->deleteUrlParameter($urlParameter);
    }
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return $this
   */
  public function deleteUrlParameter(UrlParameterInterface $urlParameter): UrlParametersByDomain
  {
    $this->remove($urlParameter);
    $this->urlParameterEntityManager->delete($urlParameter, false);
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return UrlParameterInterface|null
   */
  public function updateUrlParameter(UrlParameterInterface $urlParameter): ?UrlParameterInterface
  {
    if($urlParameter->getObjectRelation() && (!$urlParameter->getObject()))
    {
      $urlParameter->setObject($this->getObjectByUrlParameter($urlParameter));
    }
    /** @var EntityInterface $object */
    if($object = $urlParameter->getObject())
    {
      $this->generatePathLast($urlParameter);
      $this->generatePathUrlParameter($urlParameter, $object);
    }
    return $urlParameter;
  }

  /**
   * @param EntityInterface $object
   *
   * @return UrlParameterInterface
   */
  public function recoveryOrCreateUrlParameterByObject(EntityInterface $object): UrlParameterInterface
  {
    if(!$urlParameter = $this->getUrlParameterByObject($object))
    {
      $urlParameter = $this->createUrlParameter($object);
    }
    $urlParameter->setObject($object);
    return $urlParameter;
  }

  /**
   * @param EntityInterface $object
   *
   * @return UrlParameterInterface
   */
  protected function createUrlParameter(EntityInterface $object): UrlParameterInterface
  {
    $urlParameter = $this->urlParameterEntityManager->create();
    $urlParameter->setObjectRelation("{$object->getClassnameForMapping()}::{$object->getId()}");
    $urlParameter->setObject($object);
    $urlParameter->setDomainId($this->domain->getId());
    $urlParameter->setDomain($this->domain);
    $this->urlParameters[$urlParameter->getId()] = $urlParameter;
    $this->objectsMapping["{$object->getClassnameForMapping()}::{$object->getId()}"] = $object;
    return $urlParameter;
  }

  /**
   * @param array $objectsByEntityClass
   *
   * @return $this
   */
  public function hydrateObjects(array $objectsByEntityClass = array()): UrlParametersByDomain
  {
    /** @var EntityMapping $entityMapping */
    foreach($this->entitiesMapping as $entityMapping)
    {
      /** @var EntityInterface $object */
      foreach(AustralTools::getValueByKey($objectsByEntityClass, $entityMapping->entityClass) as $object)
      {
        /** @var DomainFilterMapping $domainFilterMapping */
        $domainFilterMapping = $entityMapping->getEntityClassMapping(DomainFilterMapping::class);

        if($domainFilterMapping)
        {
          if($domainFilterMapping->getAutoDomainId())
          {
            if($object->getDomainId() === $this->domain->getId())
            {
              $this->objectsMapping["{$entityMapping->entityClass}::{$object->getId()}"] = $object;
            }
          }
          elseif($domainFilterMapping->getForAllDomainEnabled() || $this->checkDomainIds($object))
          {
            $this->objectsMapping["{$entityMapping->entityClass}::{$object->getId()}"] = $object;
          }

        }
        else
        {
          $this->objectsMapping["{$entityMapping->entityClass}::{$object->getId()}"] = $object;
        }
      }
    }
    return $this;
  }

  /**
   * @return $this
   * @throws \Exception
   */
  public function generateAllUrlParameters(): UrlParametersByDomain
  {
    /** @var EntityInterface $object */
    foreach ($this->objectsMapping as $object)
    {
      if($object instanceof EntityInterface && $this->objectIsMapping($object))
      {
        $this->generateUrlParameter($object);
      }
    }
    return $this;
  }

  /**
   * @param EntityInterface|object $object
   *
   * @return $this
   * @throws \Exception
   */
  public function generateUrlParameter(EntityInterface $object): UrlParametersByDomain
  {
    $this->generatePathWithParent($object);
    $this->generateChildrenUrlParameters($object);
    return $this;
  }

  /**
   * @param EntityInterface $object
   *
   * @return UrlParametersByDomain
   */
  protected function generatePathWithParent(EntityInterface $object): UrlParametersByDomain
  {
    $urlParameter = $this->recoveryOrCreateUrlParameterByObject($object);
    if(!$urlParameter->getIsVirtual())
    {
      // TODO Check for 1 domain with multi language
      $urlParameter->setLanguage($this->currentLanguage);

      $this->generatePathLast($urlParameter);
      $this->generatePathUrlParameter($urlParameter, $object);

      $this->recoveryValuesAustral30($urlParameter, $object);

      $this->hydrate($urlParameter);
      if(!$this->isVirtual) {
        $this->urlParameterEntityManager->update($urlParameter, false);
      }
    }
    return $this;
  }

  /**
   * @param $object
   *
   * @return bool
   */
  public function checkDomainIds($object): bool
  {
    $checkIsMaster = false;
    if($this->domain->getIsMaster())
    {
      $checkIsMaster = in_array(DomainsManagement::DOMAIN_ID_MASTER, $object->getDomainIds());
    }
    if(!$checkIsMaster)
    {
      if(!in_array($this->domain->getKeyname(), $object->getDomainIds()) && !in_array($this->domain->getId(), $object->getDomainIds()))
      {
        return false;
      }
    }
    return true;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   * @param EntityInterface $object
   *
   * @return $this
   */
  protected function generatePathUrlParameter(UrlParameterInterface $urlParameter, EntityInterface $object): UrlParametersByDomain
  {
    if($object instanceof TreePageInterface && ($treePageParent = $object->getTreePageParent($this->domain->getId(), true)))
    {
      if($urlParameterParent = $this->getUrlParameterByObject($treePageParent))
      {
        $urlParameter->setPath(($urlParameterParent->getPath() ? "{$urlParameterParent->getPath()}/" : null )."{$urlParameter->getPathLast()}");
      }
    }
    else
    {
      $urlParameter->setPath("{$urlParameter->getPathLast()}");
    }
    return $this;
  }

  /**
   * @param UrlParameterInterface $urlParameter
   *
   * @return UrlParametersByDomain
   */
  public function generatePathLast(UrlParameterInterface $urlParameter): UrlParametersByDomain
  {
    if($urlParameter->getObjectRelation())
    {
      $object = $urlParameter->getObject();
      $entityMapping = $this->getEntityMappingByObjectClassname($object->getClassnameForMapping());
      $methodToGeneratePath = "__toString";
      $methodUrlName = "__toString";
      /** @var UrlParameterMapping $urlParameterMapping */
      $urlParameterMapping = $entityMapping->getEntityClassMapping(UrlParameterMapping::class);
      if(method_exists($object, $urlParameterMapping->getMethodGenerateLastPath()))
      {
        $methodToGeneratePath = $urlParameterMapping->getMethodGenerateLastPath();
      }
      if(method_exists($object, $urlParameterMapping->getMethodUrlName()))
      {
        $methodUrlName = $urlParameterMapping->getMethodUrlName();
      }
      $pathLastGenerate = $object->$methodToGeneratePath();
      $urlParameter->setName($object->$methodUrlName());
    }
    else
    {
      $pathLastGenerate = AustralTools::random();
    }
    if(!$urlParameter->getPathLast())
    {
      $urlParameter->setPathLast($pathLastGenerate);
    }
    return $this;
  }

  /**
   * @param EntityInterface $object
   *
   * @return $this
   * @throws \Exception
   */
  protected function generateChildrenUrlParameters(EntityInterface $object): UrlParametersByDomain
  {
    if(method_exists($object, "getChildren"))
    {
      foreach($object->getChildren() as $child)
      {
        if($this->objectIsMapping($child))
        {
          $this->generateUrlParameter($child);
        }
      }
    }
    if(method_exists($object, "getChildrenEntities") && $object->getChildrenEntities())
    {
      foreach($object->getChildrenEntities() as $child)
      {
        if($this->objectIsMapping($child))
        {
          $this->generateUrlParameter($child);
        }
      }
    }
    return $this;
  }


  /**
   * @return $this
   */
  protected function recoveryValuesAustral30(UrlParameter $urlParameter, EntityInterface $object): UrlParametersByDomain
  {
    $this->urlParameterMigrate->recoveryRefUrlPathValue($urlParameter, $object);
    $this->urlParameterMigrate->recoverySeoValues($urlParameter, $object);
    $this->urlParameterMigrate->recoveryRobotValues($urlParameter, $object);
    $this->urlParameterMigrate->recoverySocialValues($urlParameter, $object);
    return $this;
  }

}