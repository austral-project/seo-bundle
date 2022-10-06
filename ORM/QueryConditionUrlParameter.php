<?php
/*
 * This file is part of the Austral Entity Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\ORM;

use Austral\EntityBundle\ORM\QueryCondition;
use Austral\EntityBundle\ORM\QueryConditionInterface;

/**
 * Austral Condition UrlParameter.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
abstract class QueryConditionUrlParameter extends QueryCondition
{

  /**
   * @var string
   */
  protected string $entityClassname;

  /**
   * @var string
   */
  protected string $relationAlias = "root";

  /** @var array|string */
  protected $value;

  /**
   * @param string $entityClassname
   * @param string $relationAlias
   * @param null $value
   * @param string $conditionType
   * @param string|null $alias
   *
   * @throws \Exception
   */
  public function __construct(string $entityClassname, string $relationAlias = "root", $value = null, string $conditionType = QueryConditionInterface::WHERE, ?string $alias = null)
  {
    parent::__construct($conditionType, $alias);
    $this->entityClassname = $entityClassname;
    $this->relationAlias = $relationAlias;
    $this->value = $value;
  }

  /**
   * @return string
   */
  public function getEntityClassname(): string
  {
    return $this->entityClassname;
  }

  /**
   * @param string $entityClassname
   *
   * @return QueryConditionUrlParameter
   */
  public function setEntityClassname(string $entityClassname): QueryConditionUrlParameter
  {
    $this->entityClassname = $entityClassname;
    return $this;
  }

  /**
   * @return string
   */
  public function getRelationAlias(): string
  {
    return $this->relationAlias;
  }

  /**
   * @param string $relationAlias
   *
   * @return QueryConditionUrlParameter
   */
  public function setRelationAlias(string $relationAlias): QueryConditionUrlParameter
  {
    $this->relationAlias = $relationAlias;
    return $this;
  }

  /**
   * @return array|string
   */
  public function getValue()
  {
    return $this->value;
  }

  /**
   * @param array|string $value
   *
   * @return QueryConditionUrlParameter
   */
  public function setValue($value): QueryConditionUrlParameter
  {
    $this->value = $value;
    return $this;
  }

}
