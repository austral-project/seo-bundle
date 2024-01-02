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


use Austral\ElasticSearchBundle\Event\ElasticSearchSelectObjectsEvent;
use Austral\ElasticSearchBundle\Model\Result;
use Austral\EntityBundle\Mapping\EntityMapping;
use Austral\EntityBundle\Mapping\Mapping;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\ElasticSearchBundle\Model\ObjectToHydrate;
use Austral\SeoBundle\Entity\UrlParameter;
use Austral\SeoBundle\Mapping\UrlParameterMapping;
use Austral\SeoBundle\Routing\AustralRouting;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Doctrine\ORM\Query\QueryException;

/**
 * Austral ElasticSearch Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ElasticSearchListener
{

  /**
   * @var Mapping
   */
  protected Mapping $mapping;

  /**
   * @var DomainsManagement
   */
  protected DomainsManagement $domainsManagement;

  /**
   * @var UrlParameterManagement
   */
  protected UrlParameterManagement $urlParameterManagement;

  /**
   * @var AustralRouting
   */
  protected AustralRouting $australRouting;

  /**
   * @param Mapping $mapping
   * @param DomainsManagement $domainsManagement
   * @param UrlParameterManagement $urlParameterManagement
   * @param AustralRouting $australRouting
   */
  public function __construct(Mapping $mapping, DomainsManagement $domainsManagement, UrlParameterManagement $urlParameterManagement, AustralRouting $australRouting)
  {
    $this->mapping = $mapping;
    $this->domainsManagement = $domainsManagement;
    $this->urlParameterManagement = $urlParameterManagement;
    $this->australRouting = $australRouting;
  }

  /**
   * @param ElasticSearchSelectObjectsEvent $elasticSearchSelectObjectsEvent
   *
   * @return void
   * @throws QueryException
   * @throws \Exception
   */
  public function objects(ElasticSearchSelectObjectsEvent $elasticSearchSelectObjectsEvent)
  {
    $this->domainsManagement->initialize();
    $this->urlParameterManagement->initialize()->hydrateObjects();

    /** @var EntityMapping $entityMapping */
    $entityMapping = $this->mapping->getEntityMapping($elasticSearchSelectObjectsEvent->getEntityClass());
    if($entityMapping && $entityMapping->getEntityClassMapping(UrlParameterMapping::class))
    {
      $objectsToHydrate = array();
      /** @var ObjectToHydrate $objectToHydrate */
      foreach ($elasticSearchSelectObjectsEvent->getObjectsToHydrate() as $key => $objectToHydrate)
      {
        /** @var UrlParameter $urlParameter */
        foreach($this->urlParameterManagement->getUrlParametersByObject($objectToHydrate->getObject()) as $urlParameter)
        {
          $objectToHydrateClone = clone $objectToHydrate;
          $objectToHydrateClone->addValuesParameters(Result::VALUE_REF_TITLE, $urlParameter->getSeoTitle());
          $objectToHydrateClone->addValuesParameters(Result::VALUE_REF_DESCRIPTION, $urlParameter->getSeoDescription());
          $objectToHydrateClone->addValuesParameters(Result::VALUE_STATUS, $urlParameter->getStatus());
          $objectToHydrateClone->addValuesParameters(Result::VALUE_REF_URL, $this->australRouting->getPath("austral_website_page", $objectToHydrate->getObject(), array(), $urlParameter->getDomainId()));
          $objectToHydrateClone->addValuesParameters("domain_id", $urlParameter->getDomainId());
          $objectToHydrateClone->setElasticSearchId(sprintf("%s_%s", $objectToHydrate->getElasticSearchId(), $urlParameter->getId()));
          $objectsToHydrate[$objectToHydrateClone->getElasticSearchId()] = $objectToHydrateClone;
        }
      }
      $elasticSearchSelectObjectsEvent->setObjectsToHydrate($objectsToHydrate);
    }
  }


}
