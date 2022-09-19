<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Austral UrlParameter Event.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class UrlParameterEvent extends Event
{

  const EVENT_START = "austral.seo.url_parameter.start";
  const EVENT_END = "austral.seo.url_parameter.finish";

  /**
   * UrlParameterEvent constructor.
   *
   */
  public function __construct()
  {
  }

}