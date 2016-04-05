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

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\Common\Util\Inflector as Inflector;

/**
 * Toolkit that allows you to store data in the viiew layer
 *
 * @author Martin Leni based on Wouter J <wouter@wouterj.nl> work for Symfony package
 */
trait GenericApiTrait
{
    /**
     * Returns an entity
     */
    public function apiGet($id, $bNoReturn = false)
    {
        $rep = $this->getRepository();
        $data = $rep->find($id);
        if(!$data)
        {
            throw new NotFoundHttpException("The element requested does not exists");
        }
        if(!$bNoReturn)
        {
            $this->view->setTemplateVar('data')
                ->setData($data);
            ;
        }
        else
        {
            return $data;
        }
    }

    /**
     * gets a ordered list of entities
     */
    public function apiList(array $params)
    {
        $rep = $this->getRepository();
        $data = $rep->findAll($params['order'], $params['limit'], $params['offset']);
        $this->view->setTemplateVar('data')
            ->setData($data);
        ;
    }

    /**
     * Saves an entity
     */
    public function apiCreate($entity)
    {
        $this->saveEntity($entity);
        $this->view->setTemplateVar('data')
            ->setData($entity);
        ;
    }

    /**
     * Deletes an entity
     */
    public function apiDelete($id)
    {
        $entity = $this->getRepository()->find($id);
        if(!$entity)
        {
            throw $this->createNotFoundException('This business does not exist');
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();
        $this->view->setTemplateVar('data')
            ->setData(array('message' => 'entity successfully removed'));
        ;
    }

    /**
     * Updates an entity
     */
    public function apiUpdate($entity)
    {
        if(!$this->getRepository()->find($entity->getId()))
        {
            throw $this->createNotFoundException('This element does not exist');
        }
        $this->saveEntity($entity);
        $this->view->setTemplateVar('data')
            ->setData($entity);
        ;
    }

    /**
     * Returns a list of entities who as the value in mArgs foir the propertie $propertie
     * You can order the results using the parameter $params wich is an array that has a key order, limit and offset
     */
    public function apiDoctrineMethod($propertie, $mArgs, array $params)
    {
        $sMethod = 'findBy'.ucfirst(Inflector::camelize($propertie));
        $rep = $this->getRepository();
        try {
            $data = $rep->$sMethod($mArgs, $params['order'], $params['limit'], $params['offset']);
            $this->view->setTemplateVar('data')
                ->setData($data);
        } catch (\Exception $e) {
            if(get_class($e) == "Doctrine\ORM\ORMException")
            {
                throw new NotFoundHttpException('This page does not exist');
            }
        }
    }

    /******************/
    /***** Entity *****/
    /******************/

    /**
     * Saves an entity
     */
    private function saveEntity($entity)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();
    }

    /*****************/
    /***** Tools *****/
    /*****************/

    /**
     * Returns the current repository
     */
    private function getRepository()
    {
        if (!defined('static::repository'))
        {
            throw new \Symfony\Component\Locale\Exception\NotImplementedException("The constant repository must be defined", 1);
        }
        return $this->getDoctrine()->getManager()->getRepository(static::repository);
    }

    /**
     * Turns a request into an entity
     */
    protected function request2Entity(\Symfony\Component\HttpFoundation\Request $request, array $aForced = array())
    {
        if (!defined('static::entity'))
        {
            throw new \Symfony\Component\Locale\Exception\NotImplementedException("The constant entity must be defined", 1);
        }
        $data = $request->request->all();
        foreach ($aForced as $key => $value) {
            $data[$key] = $value;
        }
        return $this->get('serializer')->deserialize(json_encode($data), static::entity, 'json');
    }

    /**
     * Tests if the posted values are fitting into the requested entity
     */
    protected function entityValidation(\Symfony\Component\HttpFoundation\Request $request, $entity = false)
    {
        if (!defined('static::formType'))
        {
            throw new \Symfony\Component\Locale\Exception\NotImplementedException("The constant formType must be defined", 1);
        }
        if (!defined('static::entity'))
        {
            throw new \Symfony\Component\Locale\Exception\NotImplementedException("The constant entity must be defined", 1);
        }
        $sFormClassName = static::formType;
        $sEntity = static::entity;

        if(!$entity)
        {
            $entity = new $sEntity();
        }
        $form = $this->createForm(static::formType, $entity);

        $aParams = array();
        foreach ($request->request->all() as $key => $value) {
            $aParams[Inflector::camelize($key)] = $value;
        }

        $form->submit($aParams);

        if($form->isValid())
        {
            return $entity;
        }
        else
        {
            foreach ($form->getErrors() as $key => $oError) {
                throw new \Symfony\Component\Process\Exception\RuntimeException($oError->getMessage(), 1);
            }
            throw new \Symfony\Component\Process\Exception\RuntimeException($form->getErrorsAsString(), 1);
        }
    }

}
