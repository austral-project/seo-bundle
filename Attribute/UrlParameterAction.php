<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\SeoBundle\Attribute;

use Austral\EntityBundle\Attribute\AustralEntityAttribute;

/**
 * Austral UrlParameterAction attribute.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UrlParameterAction extends AustralEntityAttribute
{

  /**
   * @param string|null $name
   */
  public function __construct(public ?string $name = null)
  {
  }

}
