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

use App\Entity\Austral\SeoBundle\UrlParameter;
use Austral\EntityBundle\Event\QueryBuilderEvent;
use Austral\EntityBundle\ORM\AustralQueryBuilder;
use Austral\SeoBundle\ORM\QueryConditionUrlParameterDomain;
use Austral\SeoBundle\ORM\QueryConditionUrlParameterStatus;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Austral QueryBuilder Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterQueryBuilderListener
{

  /**
   * @var UrlParameterManagement
   */
  protected UrlParameterManagement $urlParametersManagement;

  /**
   * @var DomainsManagement
   */
  protected DomainsManagement $domainsManagement;

  /**
   * DoctrineListener constructor.
   */
  public function __construct(UrlParameterManagement $urlParametersManagement, DomainsManagement $domainsManagement)
  {
    $this->urlParametersManagement = $urlParametersManagement;
    $this->domainsManagement = $domainsManagement;
  }

  protected string $defaultUrlParameterAlias = "urlParameterInner";

  /**
   * @param QueryBuilderEvent $queryBuilderEvent
   *
   * @return void
   */
  public function conditionUrlParameterQuery(QueryBuilderEvent $queryBuilderEvent)
  {
    /** @var QueryConditionUrlParameterStatus|QueryConditionUrlParameterDomain $queryCondition */
    $queryCondition = $queryBuilderEvent->getQueryCondition();
    $urlParameterAlias = $queryCondition->getAlias() ?? $this->defaultUrlParameterAlias;

    if(!$this->checkJoinUrlParameter($queryBuilderEvent->getQueryBuilder(), $queryCondition, $urlParameterAlias))
    {
      $queryBuilderEvent->getQueryBuilder()
        ->join(UrlParameter::class,
          $urlParameterAlias,
          "WITH",
          "{$urlParameterAlias}.objectRelation = CONCAT('{$queryCondition->getEntityClassname()}', '::', {$queryCondition->getRelationAlias()}.id)"
        );
    }

    if($queryCondition instanceof QueryConditionUrlParameterStatus)
    {
      $queryBuilderEvent->getQueryBuilder()->{$queryCondition->getConditionType()}($queryCondition->getQuery($urlParameterAlias))
        ->setParameter("{$urlParameterAlias}_urlParameterStatus", $queryCondition->getValue(), is_array($queryCondition->getValue()) ? Connection::PARAM_STR_ARRAY : null)
      ;
      if($this->domainsManagement->getCurrentDomain()) {
        $queryBuilderEvent->getQueryBuilder()->andWhere($queryCondition->getQueryTranslate($urlParameterAlias))
          ->setParameter("{$urlParameterAlias}_language", $this->domainsManagement->getCurrentDomain()->getLanguage())
        ;
      }
    }
    if($queryCondition instanceof QueryConditionUrlParameterDomain)
    {
      $queryBuilderEvent->getQueryBuilder()->{$queryCondition->getConditionType()}($queryCondition->getQuery($urlParameterAlias))
        ->setParameter("{$urlParameterAlias}_urlParameterDomainId", $queryCondition->getValue(), is_array($queryCondition->getValue()) ? Connection::PARAM_STR_ARRAY : null)
      ;
    }
  }

  /**
   * @param AustralQueryBuilder $queryBuilder
   * @param QueryConditionUrlParameterStatus|QueryConditionUrlParameterDomain $queryCondition
   * @param string $urlParameterAlias
   *
   * @return bool
   */
  protected function checkJoinUrlParameter(AustralQueryBuilder $queryBuilder, $queryCondition, string $urlParameterAlias): bool
  {
    $isJoin = false;
    $joinByAlias  = $queryBuilder->getDQLPart("join");
    if(array_key_exists("root", $joinByAlias))
    {
      /** @var Join $join */
      foreach ($joinByAlias["root"] as $join)
      {
        if($join->getAlias() === $urlParameterAlias)
        {
          $isJoin = true;
        }
      }
    }
    return $isJoin;
  }

}