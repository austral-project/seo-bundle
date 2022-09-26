<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\SeoBundle\Entity\Traits;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Entity\Interfaces\TreePageInterface;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral Seo TreePageParent Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait TreePageParentTrait
{

  /**
   * @var array
   */
  protected array $treePageParents = array();

  /**
   * @return TreePageInterface|EntityInterface|TreePageParentTrait|null
   */
  public function addTreePageParent(TreePageInterface $treePageParent, string $domainId = DomainsManagement::DOMAIN_ID_MASTER): ?TreePageInterface
  {
    $this->treePageParents[$domainId] = $treePageParent;
    return $this;
  }

  /**
   * @return TreePageInterface|EntityInterface|null
   */
  public function getTreePageParent(string $domainId = DomainsManagement::DOMAIN_ID_MASTER): ?TreePageInterface
  {
    if(array_key_exists($domainId, $this->treePageParents))
    {
      return $this->treePageParents[$domainId];
    }
    return AustralTools::first($this->treePageParents, null);
  }

}
