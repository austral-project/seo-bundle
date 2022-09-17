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
use Austral\EntityBundle\Entity\Interfaces\FilterByDomainInterface;
use Austral\SeoBundle\Entity\UrlParameter;
use Austral\SeoBundle\EntityManager\UrlParameterEntityManager;
use Austral\SeoBundle\Form\Field\PathField;
use Austral\SeoBundle\Services\UrlParameterManagement;
use Austral\FormBundle\Form\Type\FormTypeInterface;
use Austral\FormBundle\Mapper\FormMapper;
use Doctrine\Common\Util\ClassUtils;
use ReflectionException;

/**
 * UrlConflict Admin .
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class UrlConflictAdmin extends Admin
{

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @throws ReflectionException
   */
  public function index(ListAdminEvent $listAdminEvent)
  {
    $this->createFormByType($listAdminEvent);
  }

  /**
   * @param ListAdminEvent $listAdminEvent
   *
   * @return void
   * @throws ReflectionException
   * @throws \Exception
   */
  protected function createFormByType(ListAdminEvent $listAdminEvent)
  {
    /** @var UrlParameterManagement $urlParameterManagement */
    $urlParameterManagement = $this->container->get('austral.seo.url_parameter.management');

    /** @var UrlParameterEntityManager $urlParameterEntityManager */
    $urlParameterEntityManager = $this->container->get('austral.entity_manager.url_parameter');

    $forms = array();

    $formsIsValide = true;
    $request = $listAdminEvent->getRequest();

    $formMapperMaster = new FormMapper($this->container->get('event_dispatcher'));
    $formMapperMaster->setTranslateDomain("austral")->setPathToTemplateDefault("@AustralAdmin/Form/Components/Fields");

    /** @var Modules $modules */
    $modules = $this->container->get('austral.admin.modules');

    foreach($urlParameterManagement->getUrlsConflictsByDomains() as $domainId => $urlsParametersByDomain)
    {
      /** @var UrlParameter $urlParameter */
      foreach($urlsParametersByDomain as $urlConflict => $urlParametersByDomain)
      {
        foreach($urlParametersByDomain as $urlParameter)
        {
          $object = $urlParameter->getObject();
          if($object instanceof FilterByDomainInterface)
          {
            $moduleObject = $modules->getModuleByEntityClassname($urlParameter->getObjectClass(), $object->getDomainId() ?? "for-all-domains");
          }
          else
          {
            $moduleObject = $modules->getModuleByEntityClassname($urlParameter->getObjectClass(), $domainId);
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
            "groupElement"  =>  "{$domainId}.{$urlConflict}"
          );
        }
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
      $listAdminEvent->getAdminHandler()->setRedirectUrl($this->module->generateUrl("index"));
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
      "type"        =>  "seo-url",
      "viewDomain"  =>  true,
      "editObject"  =>  true
    ));
  }


}