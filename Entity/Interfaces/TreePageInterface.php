<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Entity\Interfaces;

use Austral\EntityBundle\Entity\EntityInterface;

/**
 * Austral Entity TreePage Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface TreePageInterface
{

  /*
   * @var array
   * protected array $treePageParents = array();
   */

  /**
   * @return TreePageInterface|EntityInterface|null
   */
  public function addTreePageParent(TreePageInterface $treePageParent): ?TreePageInterface;

  /**
   * @return TreePageInterface|EntityInterface|null
   */
  public function getTreePageParent(string $domainId = "current"): ?TreePageInterface;

}
