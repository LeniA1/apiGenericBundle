<?php

namespace LeniM\ApiGenericBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;


use LeniM\ApiGenericBundle\Controller\GenericApiTrait;


abstract class CrudAbstract extends FOSRestController
{
    use GenericApiTrait;


    public $view     = null;

    public function __construct()
    {
        $this->createView();
    }

    public function crudGet(Request $request, $id)
    {
        $this->apiGet($id);
        $this->view->setTemplate("SWSMApiBundle:Generic:data.html.twig");
        return $this->handleView($this->view);
    }

    public function crudList(Request $request)
    {
        $this->apiList();
        $this->view->setTemplate("SWSMApiBundle:Generic:data.html.twig");
        return $this->handleView($this->view);
    }

    public function crudCreate(Request $request)
    {
        $entity = $this->request2Entity($request);
        $this->apiCreate($entity);
        $this->view->setTemplate("SWSMApiBundle:Generic:data.html.twig");
        return $this->handleView($this->view);
    }

    public function crudDelete(Request $request, $id)
    {
        $this->apiDelete($id);
        $this->view->setTemplate("SWSMApiBundle:Generic:data.html.twig");
        return $this->handleView($this->view);
    }

    public function crudUpdate(Request $request, $id)
    {
        // will throw a 404 if doesnt exists
        $this->apiGet($id);
        $entity = $this->request2Entity($request, array('id' => $id));
        $this->apiUpdate($entity);
        $this->view->setTemplate("SWSMApiBundle:Generic:data.html.twig");
        return $this->handleView($this->view);
    }

    public function doctrineMethod(Request $request, $propertie, $value)
    {
        // will throw a 404 if the entity do not have the property
        $this->apiDoctrineMethod($propertie, $value);
        $this->view->setTemplate("SWSMApiBundle:Generic:data.html.twig");
        return $this->handleView($this->view);
    }


    /*****************/
    /***** Tools *****/
    /*****************/

    protected function createView()
    {
        $this->view = View::create();
    }
}
