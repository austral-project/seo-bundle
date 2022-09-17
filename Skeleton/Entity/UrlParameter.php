##php##
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity\Austral\SeoBundle;
use Austral\SeoBundle\Entity\UrlParameter as BaseUrlParameter;

use Doctrine\ORM\Mapping as ORM;

/**
 * Austral UrlParameter Entity.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @ORM\Table(name="austral_seo_url_parameter")
 * @ORM\Entity(repositoryClass="Austral\SeoBundle\Repository\UrlParameterRepository")
 * @ORM\HasLifecycleCallbacks
 * @final
 */
class UrlParameter extends BaseUrlParameter
{
  public function __construct()
  {
    parent::__construct();
  }
}
