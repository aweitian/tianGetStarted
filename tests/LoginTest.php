<?php

use Aw\Httpclient\Curl;

class LoginTest extends PHPUnit_Framework_TestCase
{
    private $api = "http://127.0.0.1/api/login";
    public function testCookie()
    {

        $test = new Curl();
//        $test->cookie = __DIR__."/cookie";
        //$test->addHeader('Content-Type', 'application/json');
        $ret = $test->post($this->api,array(
            'username' => 'admin',
            'password' => '1234'
        ))->send();
//        var_dump($ret);
        $data = json_decode($ret,true);
        $this->assertEquals(500,$data['code']);
//
        $test = new Curl();
//        $test->cookie = __DIR__."/cookie";
        //$test->addHeader('Content-Type', 'application/json');
        $ret = $test->post($this->api,array(
            'username' => 'admin',
            'password' => '123'
        ))->send();
//        var_dump($ret);
        $data = json_decode($ret,true);
        $this->assertEquals(302,$data['code']);
    }
}

