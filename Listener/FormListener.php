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

use App\Entity\Austral\SeoBundle\UrlParameter;
use Austral\EntityBundle\Entity\EntityInterface;
use Austral\SeoBundle\Configuration\SeoConfiguration;
use Austral\SeoBundle\Entity\Interfaces\UrlParameterInterface;
use Austral\SeoBundle\Form\Field\PathField;
use Austral\SeoBundle\Form\Type\UrlParameterFormType;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Austral\FormBundle\Event\FormEvent;
use Austral\FormBundle\Field as Field;
use Austral\FormBundle\Mapper\Fieldset;
use Austral\FormBundle\Mapper\FormMapper;
use Austral\FormBundle\Mapper\GroupFields;
use Austral\HttpBundle\Services\DomainsManagement;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Austral FormListener Listener.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class FormListener
{

  /**
   * @var UrlParameterManagement
   */
  protected UrlParameterManagement $urlParameterManagement;

  /**
   * @var UrlParameterFormType
   */
  protected UrlParameterFormType $urlParameterFormType;

  /**
   * @var DomainsManagement
   */
  protected DomainsManagement $domains;

  /**
   * @var Router
   */
  protected Router $router;

  /**
   * @var SeoConfiguration
   */
  protected SeoConfiguration $SeoConfiguration;

  /**
   * @var AuthorizationCheckerInterface
   */
  protected AuthorizationCheckerInterface $authorizationChecker;

  /**
   * FormListener constructor.
   *
   * @param UrlParameterManagement $urlParameterManagement
   * @param UrlParameterFormType $urlParameterFormType
   * @param DomainsManagement $domains
   * @param SeoConfiguration $SeoConfiguration
   * @param Router $router
   * @param AuthorizationCheckerInterface $authorizationChecker
   *
   * @throws QueryException
   */
  public function __construct(UrlParameterManagement $urlParameterManagement,
    UrlParameterFormType $urlParameterFormType,
    DomainsManagement $domains,
    SeoConfiguration $SeoConfiguration,
    Router $router,
    AuthorizationCheckerInterface $authorizationChecker
  )
  {
    $this->urlParameterManagement = $urlParameterManagement;
    $this->urlParameterFormType = $urlParameterFormType;
    $this->domains = $domains->initialize();
    $this->SeoConfiguration = $SeoConfiguration;
    $this->authorizationChecker = $authorizationChecker;
    $this->router = $router;
  }

  /**
   * @param FormEvent $formEvent
   * @param string|null $type
   *
   * @throws Exception
   */
  public function formAddAutoFields(FormEvent $formEvent, ?string $type = null)
  {
    /** @var EntityInterface $object */
    $object = $formEvent->getFormMapper()->getObject();
    if($this->urlParameterManagement->getEntityMappingByObject($object))
    {
      $urlParameters = array();
      if($this->domains->getFilterDomainId())
      {
        $urlParameters[] = $this->urlParameterManagement->getOrCreateUrlParameterByObject($object, $this->domains->getFilterDomainId());
      }
      else
      {
        if(count($this->domains->getDomains()) > 1)
        {
          foreach($this->domains->getDomains() as $domain)
          {
            $urlParameters[] = $this->urlParameterManagement->getOrCreateUrlParameterByObject($object, $domain->getId());
          }
        }
        else
        {
          $urlParameters[] = $this->urlParameterManagement->getOrCreateUrlParameterByObject($object, $this->domains->getCurrentDomain()->getId());
        }
      }
      $statusMaster = null;
      /** @var UrlParameter $urlParameter */
      foreach ($urlParameters as $urlParameter)
      {
        if($statusMaster && $statusMaster !== $urlParameter->getStatus())
        {
          $statusMaster = "no-sync";
        }
        elseif(!$statusMaster)
        {
          $statusMaster = $urlParameter->getStatus();
        }
      }

      if($type === FormEvent::EVENT_AUSTRAL_FORM_ADD_AUTO_FIELDS_BEFORE)
      {
        $formEvent->getFormMapper()
          ->addFieldset("fieldset.dev.config")
          ->setCollapse(true)
          ->setIsView($this->authorizationChecker->isGranted("ROLE_ROOT"))
          ->add(Field\TemplateField::create("internalLink",
              "@AustralSeo/Form/_Components/Field/internal-link.html.twig",
              array('isView' => function() {
                  return $this->authorizationChecker->isGranted('ROLE_ROOT');
                }
              ), array(
                "urlParameters" =>  $urlParameters
              )
            )
          )
          ->end();

        $formEvent->getFormMapper()
          ->addFieldset("fieldset.right")
            ->add(Field\ChoiceField::create("status",
                array(
                  "choices.status.".UrlParameterInterface::STATUS_UNPUBLISHED   =>  array(
                    "value"   =>  UrlParameterInterface::STATUS_UNPUBLISHED,
                    "styles"  =>  array(
                      "--element-choice-current-background:var(--color-main-20)",
                      "--element-choice-current-color:var(--color-main-90)",
                      "--element-choice-hover-color:var(--color-main-90)"
                    )
                  ),
                  "choices.status.".UrlParameterInterface::STATUS_DRAFT         =>  array(
                    "value"   =>  UrlParameterInterface::STATUS_DRAFT,
                    "styles"  =>  array(
                      "--element-choice-current-background:var(--color-purple-20)",
                      "--element-choice-current-color:var(--color-purple-100)",
                      "--element-choice-hover-color:var(--color-purple-100)"
                    )
                  ),
                  "choices.status.".UrlParameterInterface::STATUS_PUBLISHED     =>  array(
                    "value"   =>  UrlParameterInterface::STATUS_PUBLISHED,
                    "styles"  =>  array(
                      "--element-choice-current-background:var(--color-green-20)",
                      "--element-choice-current-color:var(--color-green-100)",
                      "--element-choice-hover-color:var(--color-green-100)"
                    )
                  )
                ),
                array(
                  "entitled"  =>  count($urlParameters) <= 1 ? "fields.status.entitled" : "fields.statusGlobal.entitled",
                  "required"  =>  count($urlParameters) <= 1,
                  "getter"  =>  function($object) use($statusMaster) {
                    return $statusMaster !== "no-sync" ? $statusMaster : null;
                  },
                  "container" =>  array(
                    "class"   =>  $statusMaster == "no-sync" ? "disable-choice" : ""
                  ),
                  "setter"  =>  function($object, $value) use($urlParameters, $statusMaster) {
                    if($value && $value !== $statusMaster) {
                      /** @var UrlParameter $urlParameter */
                      foreach($urlParameters as $urlParameter)
                      {
                        $urlParameter->setStatus($value);
                      }
                    }
                  }
                )
              ), 1000
            )
            ->add(Field\TemplateField::create("statusMultiDomains",
              "@AustralSeo/Form/_Components/Field/statusMultiDomains.html.twig",
                array(
                  "isView"        =>    count($urlParameters) > 1
                ),
                array(
                  "urlParameters"  =>   $urlParameters,
                )
              ), 1001
            )
          ->end();
      }
      elseif($type === FormEvent::EVENT_AUSTRAL_FORM_ADD_AUTO_FIELDS_AFTER)
      {
        $this->generateParametersFields($formEvent->getFormMapper(), $urlParameters);
      }
    }

  }

  /**
   * @param FormMapper $formMapper
   * @param array $urlParameters
   *
   * @return void
   * @throws Exception
   */
  protected function generateParametersFields(FormMapper $formMapper, array $urlParameters = array())
  {
    $isMultiDomain = count($urlParameters) > 1;
    $urlParameter = new UrlParameter();
    $urlParameterFormMapper = new FormMapper();
    $urlParameterFormMapper->setObject($urlParameter);

    $formMapper->addSubFormMapper("urlParameters", $urlParameterFormMapper);
    $this->urlParameterFormType->setFormMapper($urlParameterFormMapper);


    $fieldsetEntitled = $isMultiDomain ? "fieldset.urlParametersConfigurationByDomain" : "fieldset.urlParametersConfiguration";
    $urlParameterFieldset = $urlParameterFormMapper->addFieldset("fieldset.seoParameters", $fieldsetEntitled)
      ->setClosureTranslateArgument(function(Fieldset $fieldset, $object) {
        $fieldset->addTranslateArguments("%domainName%", $object->getDomain() ? $object->getDomain()->getName() : "");
      })
      ->setAttr(array("class" =>  "fieldset-content-parent"))
      ->setCollapse($isMultiDomain);


    $urlParameterFieldset
      ->addGroup("seo", "groups.seo")
      ->setDirection(GroupFields::DIRECTION_COLUMN)
      ->add(Field\ChoiceField::create("status",
          array(
            "choices.status.".UrlParameterInterface::STATUS_UNPUBLISHED   =>  array(
              "value"   =>  UrlParameterInterface::STATUS_UNPUBLISHED,
              "styles"  =>  array(
                "--element-choice-current-background:var(--color-main-20)",
                "--element-choice-current-color:var(--color-main-90)",
                "--element-choice-hover-color:var(--color-main-90)"
              )
            ),
            "choices.status.".UrlParameterInterface::STATUS_DRAFT         =>  array(
              "value"   =>  UrlParameterInterface::STATUS_DRAFT,
              "styles"  =>  array(
                "--element-choice-current-background:var(--color-purple-20)",
                "--element-choice-current-color:var(--color-purple-100)",
                "--element-choice-hover-color:var(--color-purple-100)"
              )
            ),
            "choices.status.".UrlParameterInterface::STATUS_PUBLISHED     =>  array(
              "value"   =>  UrlParameterInterface::STATUS_PUBLISHED,
              "styles"  =>  array(
                "--element-choice-current-background:var(--color-green-20)",
                "--element-choice-current-color:var(--color-green-100)",
                "--element-choice-hover-color:var(--color-green-100)"
              )
            )
          ), array(
            "choice_style"  =>  null,
            "isView"        =>  $isMultiDomain
          )
        )
      )


      ->add(Field\TemplateField::create("googleVisualisation",
          "@AustralSeo/Form/_Components/Field/google-visualisation.html.twig",
        )
      )
      ->add(new PathField("pathLast", array(
            "entitled"  =>  "fields.pathLast.entitled",
            "attr"      =>  array(
              "autocomplete"  =>  "off"
            ),
            "template"  =>  array(
              "vars"  =>  array(
                "urlParameter"  =>  $urlParameter
              )
            )
          )
        )
      )
      ->add(Field\TextField::create("seoTitle", array(
            "entitled"  =>  "fields.seoTitle.entitled",
            "attr"  => array(
              'data-characters-max'  =>  $this->SeoConfiguration->get("nb_characters.ref_title"),
              "autocomplete"  =>  "off"
            )
          )
        )
      )
      ->add(Field\TextareaField::create("seoDescription", Field\TextareaField::SIZE_AUTO, array(
            "entitled"  =>  "fields.seoDescription.entitled",
            "attr"  => array(
              'data-characters-max'  =>  $this->SeoConfiguration->get("nb_characters.ref_description"),
              "autocomplete"  =>  "off"
            )
          )
        )
      )
    ->end();

    $urlParameterFieldset
      ->addGroup("robots", "groups.robots")
      ->setDirection(GroupFields::DIRECTION_COLUMN)
        ->addGroup("robots")
          ->setStyle(GroupFields::STYLE_BOOLEAN)
          ->add(Field\SwitchField::create("isIndex", array(
                "entitled"  =>  "fields.isIndex.entitled",
                "helper"    =>  "fields.isIndex.information"
              )
            )
          )
          ->add(Field\SwitchField::create("isFollow", array(
                "entitled"  =>  "fields.isFollow.entitled",
                "helper"    =>  "fields.isFollow.information"
              )
            )
          )
          ->add(Field\SwitchField::create("inSitemap", array(
                "entitled"  =>  "fields.inSitemap.entitled",
                "helper"    =>  "fields.inSitemap.information"
              )
            )
          )
        ->end()
        ->add(Field\TextField::create("seoCanonical", array(
              "entitled"  =>  "fields.seoCanonical.entitled"
            )
          )
        )
      ->end();



    $urlParameterFieldset
      ->addGroup("social_network", "groups.social_network")
        ->setDirection(GroupFields::DIRECTION_COLUMN)
        ->add(Field\TextField::create("socialTitle",
          array(
            "attr"      =>  array(
              "autocomplete"  =>  "off"
            ),
            "entitled"  =>  "fields.socialTitle.entitled"
          )
        ))
        ->addGroup("social_network_content")
          ->addGroup("social_network_content_image")
          ->setDirection(GroupFields::DIRECTION_COLUMN)
          ->setStyle(GroupFields::STYLE_NONE)
          ->setSize(GroupFields::SIZE_COL_6)
            ->add(Field\UploadField::create("socialImage",
              array(
                "attr"      =>  array(
                  "autocomplete"  =>  "off"
                ),
                "entitled"  =>  "fields.socialImage.entitled"
              )
            ))
          ->end()
          ->addGroup("social_network_content_description")
            ->setDirection(GroupFields::DIRECTION_COLUMN)
            ->setStyle(GroupFields::STYLE_NONE)
            ->setSize(GroupFields::SIZE_COL_6)
            ->add(Field\TextareaField::create("socialDescription", null,
              array(
                "attr"      =>  array(
                  "autocomplete"  =>  "off"
                ),
                "entitled"  =>  "fields.socialDescription.entitled",
                "group" =>  array(
                  'class' =>  "full"
                )
              )))
            ->end()
        ->end()
      ->end();

    $formMapper->addFieldset("urlParameter", false)
      ->setPositionName(Fieldset::POSITION_NONE)
      ->add(Field\CollectionEmbedField::create("urlParameters", array(
          "button"              =>  "button.new.emailAddress",
          "allow"               =>  array(
            "child"               =>  false,
            "add"                 =>  false,
            "delete"              =>  false,
          ),
          "entry"               =>  array("type"  => get_class($this->urlParameterFormType)),
          "sortable"            =>  array(
            "value"               =>  "id",
          ),
          "getter"              =>  function($object) use($urlParameters) {
            return $urlParameters;
          },
          "setter"              =>  function($object) use($urlParameter) {
          }
        )
      ));
  }

}