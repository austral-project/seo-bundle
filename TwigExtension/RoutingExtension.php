<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\TwigExtension;

use Austral\EntityBundle\Entity\EntityInterface;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Routing\AustralRouting;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\TwigFunction;

/**
 * Austral Seo Bundle.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
final class RoutingExtension extends AbstractExtension
{

  /**
   * @var AustralRouting
   */
  private AustralRouting $australRouting;

  public function __construct(AustralRouting $australRouting)
  {
    $this->australRouting = $australRouting;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array
  {
    return [
      new TwigFunction('austral_url', [$this, 'getUrl'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
      new TwigFunction('austral_path', [$this, 'getPath'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
    ];
  }

  /**
   * @param string $name
   * @param EntityInterface|null $object
   * @param array $parameters
   * @param string|null $domainId
   * @param bool $relative
   *
   * @return string|null
   */
  public function getPath(string $name, ?EntityInterface $object = null, array $parameters = [], ?string $domainId = "current", bool $relative = false): ?string
  {
    return $this->australRouting->getPath($name, $object, $parameters, $domainId, $relative);
  }

  /**
   * @param string $name
   * @param EntityInterface|null $object
   * @param array $parameters
   * @param string|null $domainId
   * @param bool $schemeRelative
   *
   * @return string|null
   */
  public function getUrl(string $name, ?EntityInterface $object = null, array $parameters = [], ?string $domainId = "current", bool $schemeRelative = false): ?string
  {
    return $this->australRouting->getUrl($name, $object, $parameters, $domainId, $schemeRelative);
  }

  /**
   * Determines at compile time whether the generated URL will be safe and thus
   * saving the unneeded automatic escaping for performance reasons.
   *
   * The URL generation process percent encodes non-alphanumeric characters. So there is no risk
   * that malicious/invalid characters are part of the URL. The only character within an URL that
   * must be escaped in html is the ampersand ("&") which separates query params. So we cannot mark
   * the URL generation as always safe, but only when we are sure there won't be multiple query
   * params. This is the case when there are none or only one constant parameter given.
   * E.g. we know beforehand this will be safe:
   * - path('route')
   * - path('route', {'param': 'value'})
   * But the following may not:
   * - path('route', var)
   * - path('route', {'param': ['val1', 'val2'] }) // a sub-array
   * - path('route', {'param1': 'value1', 'param2': 'value2'})
   * If param1 and param2 reference placeholder in the route, it would still be safe. But we don't know.
   *
   * @param Node $argsNode The arguments of the path/url function
   *
   * @return array An array with the contexts the URL is safe
   */
  public function isUrlGenerationSafe(Node $argsNode): array
  {
    // support named arguments
    $paramsNode = $argsNode->hasNode('parameters') ? $argsNode->getNode('parameters') : (
    $argsNode->hasNode(1) ? $argsNode->getNode(1) : null
    );

    if (null === $paramsNode || $paramsNode instanceof ArrayExpression && \count($paramsNode) <= 2 &&
      (!$paramsNode->hasNode(1) || $paramsNode->getNode(1) instanceof ConstantExpression)
    ) {
      return ['html'];
    }

    return [];
  }

}
