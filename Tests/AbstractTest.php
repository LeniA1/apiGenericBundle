<?php

/*
 * This file is part of the lenim/api-generic-bundle package.
 *
 * (c) LeniM <https://github.com/lenim/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LeniM\ApiGenericBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

abstract class AbstractTest extends WebTestCase
{
    public $client;
    abstract function getWebPath();

    public function getClient(){
        return $this->client;
    }

    public function setClient($client){
        $this->client = $client;
        return $this;
    }

    /**
     * Testing update
     */

    public function _testUpdateSuccess(array $aDatas, $id){
        $this->assertEquals(200, $this->__testUpdate($aDatas, $id));
    }

    public function _testUpdateFail(array $aDatas, $id){
        // $this->markTestIncomplete(
        //     'This test has not been implemented yet.'
        // );
        // $this->assertEquals(500, $this->__testUpdate($aDatas, $id));
    }

    public function _testUpdateNotFound(array $aDatas, $id){
        $this->assertEquals(404, $this->__testUpdate($aDatas, $id));
    }

    public function __testUpdate($aDatas, $id){
        $this->client->insulate(true);
        $this->client->request('PUT', $this->getWebPath().'/'.$id.'/update.json', $aDatas);
        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * Testing Delete
     */

    public function _testDeleteSuccess($id) {
        $this->assertEquals(200, $this->__testDelete($id));
    }

    public function _testDeleteFail($id){
        $this->assertEquals(404, $this->__testDelete($id));
    }

    private function __testDelete($id){
        $this->client->insulate(true);
        $this->client->request('DELETE', $this->getWebPath().'/'.$id.'/delete.json');
        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * Testing create
     */
    public function _testCreateSuccess(array $aDatas){
        $this->client->insulate(true);
        $this->client->request('POST', $this->getWebPath().'/create.json', $aDatas);

        $response = $this->client->getResponse();

        // test if answer status code is 200
        $this->assertEquals(200, $this->_testCreate($aDatas));

        // test if response type is json
        $this->assertTrue(
            $response->headers->contains(
                'Content-Type',
                'application/json'
            )
        );

        $sContent = $response->getContent();
        $oContent = json_decode($sContent);

        // test if is valid json
        $this->assertTrue(
            is_object($oContent)
        );

        // test contains an id
        $this->assertTrue(
            isset($oContent->id)
        );

        return $oContent->id;
    }

    public function _testCreateFail(array $aDatas){
        // $this->markTestIncomplete(
        //     'This test has not been implemented yet.'
        // );
        // $this->assertEquals(500, $this->_testCreate($aDatas));
    }

    private function _testCreate($aDatas){
        $this->client->insulate(true);
        $this->client->request('POST', $this->getWebPath().'/create.json', $aDatas);
        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * Testing gets
     */
    public function _testGetSuccess($id)
    {
        $this->assertEquals(200, $this->__testGet($id));
    }

    public function _testGetFail($id)
    {
        $this->assertEquals(404, $this->__testGet($id));
    }

    private function __testGet($id)
    {
        $this->client->insulate(true);
        $this->client->request('GET', $this->getWebPath().'/'.$id.'.json');
        return $this->client->getResponse()->getStatusCode();
    }

    /**
     * Testing list
     */
    public function _testList()
    {
        $this->client->insulate(true);
        $this->client->request('GET', $this->getWebPath().'/list.json');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }


    /**
     * Test filtering
     */
    public function _testFilteringSuccess($id)
    {
        $this->assertEquals(200, $this->__testFiltering('id', $id));
    }

    public function _testFilteringFail()
    {
        $this->assertEquals(404, $this->__testFiltering('I_am_sure_noone_will_ever_creat_this_field_i_mean_come_on', 0));
    }

    public function __testFiltering($field, $value)
    {
        $this->client->insulate(true);
        $this->client->request('GET', $this->getWebPath().'/filter/'.$field.'/'.$value.'.json');
        return $this->client->getResponse()->getStatusCode();
    }

}
