<?php

/*
 * This file is part of the lenim/api-generic-bundle package.
 *
 * (c) LeniM <https://github.com/lenim/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeniM\ApiGenericBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;


use LeniM\ApiGenericBundle\Controller\GenericApiTrait;

/**
 * Abstract class that is able to read a request and turn it into action
 * Stores data in the view layer using \LeniM\ApiGenericBundle\Controller\GenericApiTrait
 *
 * @author Martin Leni based on Wouter J <wouter@wouterj.nl> work for Symfony package
 *
 */
abstract class CrudAbstract extends FOSRestController
{
    use GenericApiTrait;


    public $view     = null;

    public function __construct()
    {
        $this->createView();
    }

    /**
     * Get an entity
     */
    public function crudGet(Request $request, $id)
    {
        return $this->apiGet($id);
    }

    /**
     * List entities
     */
    public function crudList(Request $request)
    {
        $aParams = $this->request2RestrictionsArray($request);
        return $this->apiList($aParams);
    }

    /**
     * Create an entity reading a request then saves it
     */
    public function crudCreate(Request $request)
    {
        $entity = $this->entityValidation($request);
        return $this->apiCreate($entity);
    }

    /**
     * Delete an entity
     */
    public function crudDelete(Request $request, $id)
    {
        return $this->apiDelete($id);
    }

    /**
     * Updates an entity reading the request then saves it
     */
    public function crudUpdate(Request $request, $id)
    {
        // will throw a 404 if doesnt exists
        $entity = $this->apiGet($id, true);
        $entity = $this->entityValidation($request, $entity);
        return $this->apiUpdate($entity);
    }

    /**
     * Calls a method of doctrine findBy{$propertie} giving it the parameter value.
     * it ll deduce fron the request the pagination
     */
    public function doctrineMethod(Request $request, $propertie, $value)
    {
        $params = $this->request2RestrictionsArray($request);
        // will throw a 404 if the entity do not have the property
        return $this->apiDoctrineMethod($propertie, $value, $params);
    }


    /*****************/
    /***** Tools *****/
    /*****************/

    protected function createView()
    {
        $this->view = View::create();
    }

    /**
     * Turns the request into the array used for the pagination
     */
    protected function request2RestrictionsArray(Request $request)
    {
        $order = array('id' => 'ASC');
        $requestOrder = $request->query->get('order', false);
        if($requestOrder)
        {
            if(is_array($requestOrder))
            {
                $order = $requestOrder;
            }
            elseif(is_string($requestOrder))
            {
                $order = array($requestOrder => 'ASC');
            }
        }

        $limit = ($request->query->get('limit', false) ? $request->query->getInt('limit') : 10);

        $offset = ($request->query->get('offset', false) ? $request->query->getInt('offset') : 0);
        $offset = ($request->query->get('page', false) ? ($request->query->getInt('page') - 1 ) * $limit : $offset);

        return array(
            'order'  => $order,
            'offset' => $offset,
            'limit'  => $limit
        );
    }
}
