<?php
/*
 * This file is part of the Austral Seo Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\SeoBundle\Admin;

use Austral\AdminBundle\Admin\Admin;
use Austral\AdminBundle\Admin\Event\ListAdminEvent;
use Austral\AdminBundle\Module\Modules;
use Austral\HttpBundle\Entity\Interfaces\FilterByDomainInterface;
use Austral\HttpBundle\Services\DomainsManagement;
use Austral\SeoBundle\Configuration\SeoConfiguration;
use Austral\SeoBundle\Entity\UrlParameter;
use Austral\SeoBundle\EntityManager\UrlParameterEntityManager;
use Austral\SeoBundle\Form\Field\PathField;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Austral\FormBundle\Form\Type\FormTypeInterface;
use Austral\FormBundle\Mapper\FormMapper;
use Doctrine\Common\Util\ClassUtils;
use ReflectionException;
use Austral\FormBundle\Field as Field;

/**
 * Seo Admin .
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class SeoAdmin extends Admin
{

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @throws ReflectionException
   */
  public function index(ListAdminEvent $listAdminEvent)
  {
    $this->createFormByType($listAdminEvent, "seo-title");
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @throws ReflectionException
   */
  public function url(ListAdminEvent $listAdminEvent)
  {
    $this->createFormByType($listAdminEvent, "seo-url");
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @throws ReflectionException
   */
  public function all(ListAdminEvent $listAdminEvent)
  {
    $this->createFormByType($listAdminEvent, "seo-all");
  }


  /**
   * @param ListAdminEvent $listAdminEvent
   * @param string $type
   *
   * @return void
   * @throws ReflectionException
   * @throws \Exception
   */
  protected function createFormByType(ListAdminEvent $listAdminEvent, string $type)
  {
    /** @var UrlParameterManagement $urlParameterManagement */
    $urlParameterManagement = $this->container->get('austral.seo.url_parameter.management');

    /** @var UrlParameterEntityManager $urlParameterEntityManager */
    $urlParameterEntityManager = $this->container->get('austral.entity_manager.url_parameter');

    $forms = array();
    /** @var SeoConfiguration $SeoConfiguration */
    $SeoConfiguration = $this->container->get('austral.seo.config');

    $formsIsValide = true;
    $request = $listAdminEvent->getRequest();


    if(!$domainId = $this->module->getParametersByKey("austral_filter_by_domain"))
    {
      $domainId =$this->container->get('austral.http.domains.management')->getCurrentDomain()->getId();
    }

    $formMapperMaster = new FormMapper($this->container->get('event_dispatcher'));
    $formMapperMaster->setTranslateDomain("austral")->setPathToTemplateDefault("@AustralAdmin/Form/Components/Fields");

    /** @var Modules $modules */
    $modules = $this->container->get('austral.admin.modules');

    $urlParametersByDomain = $urlParameterManagement->getUrlParametersByDomain($domainId);

    /** @var UrlParameter $urlParameter */
    foreach($urlParametersByDomain as $urlParameter)
    {
      if(!$urlParameter->getIsVirtual())
      {
        $object = $urlParameter->getObject();
        if($object instanceof FilterByDomainInterface && $domainId !== "current")
        {
          $moduleObject = $modules->getModuleByEntityClassname($urlParameter->getObjectClass(), $object->getDomainId() ?? DomainsManagement::DOMAIN_ID_FOR_ALL_DOMAINS);
        }
        else
        {
          $moduleObject = $modules->getModuleByEntityClassname($urlParameter->getObjectClass());
        }

        $formMapper = new FormMapper($this->container->get('event_dispatcher'));
        $formMapper->setObject($urlParameter)
          ->setName("form_{$urlParameter->getId()}")
          ->setFormTypeAction("edit")
          ->setTranslateDomain("austral");
        if($moduleObject)
        {
          $formMapper->setModule($moduleObject);
        }

        $formMapperMaster->addSubFormMapper("form_{$urlParameter->getId()}", $formMapper);

        /** @var FormTypeInterface $formType */
        $formType = clone $this->container->get('austral.form.type.master')
          ->setClass(ClassUtils::getClass($urlParameter))
          ->setFormMapper($formMapperMaster);


        if($type === "seo-all")
        {
          $formMapper->add(Field\TemplateField::create("googleVisualisation",
            "@AustralSeo/Form/_Components/Field/google-visualisation.html.twig",
          )
          );
        }
        if($type === "seo-title" || $type === "seo-all")
        {
          $formMapper->add(Field\TextField::create("seoTitle", array(
              "entitled"  =>  "fields.seoTitle.entitled",
              "attr"  => array(
                'data-characters-max'  =>  $SeoConfiguration->get("nb_characters.ref_title"),
                "autocomplete"  =>  "off"
              )
            )
          )
          )
            ->add(Field\TextareaField::create("seoDescription", Field\TextareaField::SIZE_AUTO, array(
                "entitled"  =>  "fields.seoDescription.entitled",
                "attr"  => array(
                  'data-characters-max'  =>  $SeoConfiguration->get("nb_characters.ref_description"),
                  "autocomplete"  =>  "off"
                )
              )
            )
            );
        }
        if($type === "seo-url" || $type === "seo-all")
        {
          $formMapper->add(new PathField("pathLast", array(
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
          );
        }

        $form = $this->container->get('form.factory')->createNamed("form_{$urlParameter->getId()}", get_class($formType), $formMapper->getObject());
        if($request->getMethod() == 'POST')
        {
          $form->handleRequest($request);
          if($form->isSubmitted()) {

            $formMapper->setObject($form->getData());
            if($form->isValid() && $this->module->getAdmin()->formIsValidate())
            {
              $urlParameterEntityManager->update($formMapper->getObject(), false);
            }
            else
            {
              $formsIsValide = false;
              $formMapper->setFormStatus("error");
            }
          }
        }
        $forms[] = array(
          "mapper"        =>  $formMapper,
          "form"          =>  $form,
          "view"          =>  $form->createView(),
          "groupElement"  =>  null
        );
      }
    }

    $formSend = false;
    $formStatus = null;
    if($request->getMethod() == 'POST')
    {
      $formSend = true;
      if($formsIsValide)
      {
        $urlParameterEntityManager->flush();
      }
      $formStatus = ($formsIsValide ? "success" : "error");
      $listAdminEvent->getAdminHandler()->addFlash(($formsIsValide ? "success" : "error"),
        $listAdminEvent->getAdminHandler()->getTranslate()->trans(
          "form.status.multi.".($formsIsValide ? "success" : "error"),
          array('%name%' => ""), "austral"
        )
      );
      $listAdminEvent->getAdminHandler()->setRedirectUrl($this->module->generateUrl($type));
    }

    if($this->module->getActionName() == "index")
    {
      $moduleMaster = $this->module;
    }
    else
    {
      $moduleMaster = $this->module->getParent();
    }
    $listAdminEvent->getTemplateParameters()->addParameters("moduleMaster", $moduleMaster);
    $listAdminEvent->getTemplateParameters()->setPath("@AustralSeo/Admin/Module/seo.html.twig");
    $listAdminEvent->getTemplateParameters()->addParameters("list", array(
      "forms"       =>  $forms,
      "formSend"    =>  $formSend,
      "formStatus"  =>  $formStatus,
      "type"        =>  $type
    ));
  }


}