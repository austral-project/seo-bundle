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
 * Austral UrlParameterObject attribute.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UrlParameterObject extends AustralEntityAttribute
{

  /**
   * @param string|null $methodGenerateLastPath
   * @param string|null $keyForObjectLink
   * @param string|null $methodUrlName
   */
  public function __construct(
    public ?string $methodGenerateLastPath = null,
    public ?string $keyForObjectLink = null,
    public ?string $methodUrlName = null
  ) {
  }

}
