<?php
/*
 * This file is part of the Austral List Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Column;

use Austral\HttpBundle\Services\DomainsManagement;
use Austral\ListBundle\Column\Action;
use Austral\ListBundle\Column\Base\ColumnWithPath;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\ToolsBundle\AustralTools;

/**
 * Austral Column Choice.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterStatus extends ColumnWithPath
{

  /**
   * @var string
   */
  protected string $type = "choices";

  /**
   * @var array
   */
  protected array $choices = array();

  /**
   * @var bool
   */
  protected bool $isGranted = true;

  /**
   * @var string
   */
  protected string $domainId;

  /**
   * Choices constructor.
   *
   * @param string|null $entitled
   * @param array $choices
   * @param string|null $path
   * @param bool $isGranted
   * @param string $domainId
   * @param array $options
   */
  public function __construct(?string $entitled = null, array $choices = array(), string $path = null, bool $isGranted = true, string $domainId = DomainsManagement::DOMAIN_ID_MASTER, array $options = array())
  {
    parent::__construct("status", $entitled, $path, $options);
    $this->fieldname = UrlParameterInterface::CHOICE_VALUE_FIELDNAME;
    $this->domainId = $domainId;
    $this->choices = count($choices) ? $choices : array(
      UrlParameterInterface::STATUS_PUBLISHED       => array(
        "entitled" => "choices.status.".UrlParameterInterface::STATUS_PUBLISHED,
        "styles"  =>  array(
          "--element-choice-current-background:var(--color-green-20)",
          "--element-choice-current-color:var(--color-green-100)",
          "--element-choice-hover-color:var(--color-green-100)"
        )
      ),
      UrlParameterInterface::STATUS_DRAFT           => array(
        "entitled" => "choices.status.".UrlParameterInterface::STATUS_DRAFT,
        "styles"  =>  array(
          "--element-choice-current-background:var(--color-purple-20)",
          "--element-choice-current-color:var(--color-purple-100)",
          "--element-choice-hover-color:var(--color-purple-100)"
        )
      ),
      UrlParameterInterface::STATUS_UNPUBLISHED     => array(
        "entitled" => "choices.status.".UrlParameterInterface::STATUS_UNPUBLISHED,
        "styles"  =>  array(
          "--element-choice-current-background:var(--color-main-20)",
          "--element-choice-current-color:var(--color-main-90)",
          "--element-choice-hover-color:var(--color-main-90)"
        )
      )
    );
    $this->isGranted = $isGranted;
  }

  /**
   * Get choices
   * @return array
   */
  public function getChoices(): array
  {
    return $this->choices;
  }

  /**
   * Set choices
   *
   * @param array $choices
   *
   * @return UrlParameterStatus
   */
  public function setChoices(array $choices): UrlParameterStatus
  {
    $this->choices = $choices;
    return $this;
  }

  /**
   * @return bool
   */
  public function isGranted(): bool
  {
    return $this->isGranted;
  }

  /**
   * @param string $value
   *
   * @return false|int|string
   */
  public function getEntitledByChoiceValue(string $value)
  {
    $return = null;
    if(array_key_exists($value, $this->choices))
    {
      $choice = $this->choices[$value];
      if(is_array($choice))
      {
        $return = $choice["entitled"];
      }
      else
      {
        $return = $choice;
      }
    }

    return $return;
  }

  /**
   * @param $object
   *
   * @return mixed|null
   */
  public function getter($object)
  {
    $urlParameter = $object->getUrlParameter($this->domainId);
    return $urlParameter ? $urlParameter->getStatus() : null;
  }

  /**
   * @param array $pathAttribute
   *
   * @return array
   */
  public function actions(array $pathAttribute = array()): array
  {
    $actions = array();
    $this->pathAttributes = array_replace($this->pathAttributes, $pathAttribute);
    $this->pathAttributes['__fieldname__'] = $this->fieldname;
    foreach($this->choices as $value => $parameters)
    {
      if(is_array($parameters))
      {
        $entilted = AustralTools::getValueByKey($parameters, "entitled", null);
        $picto = AustralTools::getValueByKey($parameters, "picto", null);
        $styles = AustralTools::getValueByKey($parameters, "styles", null);
      }
      else
      {
        $entilted = $parameters;
        $picto = "";
        $styles = null;
      }
      $this->pathAttributes['__value__'] = $value;
      $action = new Action("change.{$value}", $entilted, $this->path(), $picto, array(
          "translateDomain"             => $this->translateDomain(),
          "data-url"                    =>  true,
          "class"                       =>  "{$value}-value",
          "attr"  =>  array(
            "data-click-actions"        =>  "refresh-element",
            "data-reload-elements"      =>  "#{$this->id}",
            "style"                     =>  $styles
          )
        )
      );
      $action->setValue($value);
      $actions[$value] = $action;
    }
    return $actions;
  }

}