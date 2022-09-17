<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Form\Field;

use Austral\FormBundle\Field\TextField;

/**
 * Austral Field Path Input.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class PathField extends TextField
{

  /**
   * @param $fieldname
   * @param array $options
   *
   * @return $this
   */
  public static function create($fieldname, array $options = array()): PathField
  {
    return new self($fieldname, $options);
  }

  /**
   * PathField constructor.
   *
   * @param string $fieldname
   * @param array $options
   */
  public function __construct($fieldname, array $options = array())
  {
    parent::__construct($fieldname, $options);
    $this->isDefaultTemplate = false;
    $this->options["template"]["path"] = "@AustralSeo/Form/_Components/Field/path.html.twig";
  }

}