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


use Austral\AdminBundle\Configuration\ConfigurationChecker;
use Austral\AdminBundle\Configuration\ConfigurationCheckerValue;
use Austral\AdminBundle\Event\ConfigurationCheckerEvent;
use Austral\SeoBundle\Configuration\SeoConfiguration;

/**
 * Austral ConfigurationChecker Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ConfigurationCheckerListener
{

  /**
   * @var SeoConfiguration
   */
  protected SeoConfiguration $SeoConfiguration;

  /**
   * @param SeoConfiguration $SeoConfiguration
   */
  public function __construct(SeoConfiguration $SeoConfiguration)
  {
    $this->SeoConfiguration = $SeoConfiguration;
  }

  /**
   * @param ConfigurationCheckerEvent $configurationCheckerEvent
   *
   * @throws \Exception
   */
  public function configurationChecker(ConfigurationCheckerEvent $configurationCheckerEvent)
  {
    $configurationCheckModules = $configurationCheckerEvent->getConfigurationChecker()->getChild("modules");

    $configurationCheckerNotify = new ConfigurationChecker("Seo");
    $configurationCheckerNotify->setName("configuration.check.modules.Seo.title")
      ->setIsTranslatable(true)
      ->setParent($configurationCheckModules);

    $configurationCheckerValue = new ConfigurationCheckerValue("redirection", $configurationCheckerNotify);
    $configurationCheckerValue->setName("configuration.check.modules.Seo.redirection.entitled")
      ->setIsTranslatable(true)
      ->setIsTranslatableValue(true)
      ->setType("checked")
      ->setStatus($this->SeoConfiguration->get('redirection.auto') ? "success" : "")
      ->setValue($this->SeoConfiguration->get('redirection.auto') ? "configuration.check.choices.enabled" : "configuration.check.choices.disabled");

    $configurationCheckerValue = new ConfigurationCheckerValue("ref_title", $configurationCheckerNotify);
    $configurationCheckerValue->setName("configuration.check.modules.Seo.refTitle.entitled")
      ->setIsTranslatable(true)
      ->setIsTranslatableValue(false)
      ->setType("string")
      ->setStatus("")
      ->setValue($this->SeoConfiguration->get('nb_characters.ref_title'));

    $configurationCheckerValue = new ConfigurationCheckerValue("ref_description", $configurationCheckerNotify);
    $configurationCheckerValue->setName("configuration.check.modules.Seo.refDescription.entitled")
      ->setIsTranslatable(true)
      ->setIsTranslatableValue(false)
      ->setType("string")
      ->setStatus("")
      ->setValue($this->SeoConfiguration->get('nb_characters.ref_description'));

  }
}