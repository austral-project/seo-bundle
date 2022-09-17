<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Austral Website Configuration.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class Configuration implements ConfigurationInterface
{
  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder('austral_entity_seo');

    $rootNode = $treeBuilder->getRootNode();
    $rootNode->children()
      ->arrayNode('redirection')
        ->addDefaultsIfNotSet()
        ->children()
          ->booleanNode("auto")->defaultValue(true)->isRequired()->end()
        ->end()
      ->end()
      ->arrayNode('nb_characters')
        ->addDefaultsIfNotSet()
        ->children()
          ->scalarNode("ref_title")->cannotBeEmpty()->defaultValue(70)->isRequired()->end()
          ->scalarNode("ref_description")->cannotBeEmpty()->defaultValue(150)->isRequired()->end()
        ->end()
      ->end() // END Child
    ->end();
    return $treeBuilder;
  }

  public function getConfigDefault()
  {
    return array(
      "redirection" =>  array(
        "auto"        =>  true
      ),
      "nb_characters" =>  array(
        "ref_title"         =>  70,
        "ref_description"   =>  150
      )
    );
  }
}
