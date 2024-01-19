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

/**
 * Austral Condition UrlParameterStatus.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class QueryConditionUrlParameterStatus extends QueryConditionUrlParameter
{
  /**
   * @param string $alias
   *
   * @return string
   */
  public function getQuery(string $alias): string
  {
    return is_array($this->value) ? "{$alias}.status IN (:{$alias}_urlParameterStatus)" : "{$alias}.status = :{$alias}_urlParameterStatus";
  }

  /**
   * @param string $alias
   *
   * @return string
   */
  public function getQueryTranslate(string $alias): string
  {
    return "{$alias}.language = :{$alias}_language";
  }
}

