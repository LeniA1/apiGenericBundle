<?php

namespace LeniM\ApiGenericBundle\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait GenericApiTrait
{
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
    }

    public function apiList()
    {
        $rep = $this->getRepository();
        $data = $rep->findAll();
        $this->view->setTemplateVar('data')
            ->setData($data);
        ;
    }

    public function apiCreate($entity)
    {
        $this->saveEntity($entity);
        $this->view->setTemplateVar('data')
            ->setData($entity);
        ;
    }

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


    public function apiUpdate($entity)
    {
        if(!$this->getRepository()->find($entity->getId()))
        {
            throw $this->createNotFoundException('This business does not exist');
        }
        $this->saveEntity($entity);
        $this->view->setTemplateVar('data')
            ->setData($entity);
        ;
    }

    /******************/
    /***** Entity *****/
    /******************/

    private function saveEntity($entity)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();
    }

    /*****************/
    /***** Tools *****/
    /*****************/


    private function getRepository()
    {
        if (!defined('static::repository'))
        {
            throw new \Symfony\Component\Locale\Exception\NotImplementedException("The constant repository must be defined", 1);
        }
        return $this->getDoctrine()->getManager()->getRepository(static::repository);
    }

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

}
