<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Topi\MisskeyAuth\MiAuth;

class MiAuthTest extends TestCase
{
    protected $auth;

    protected function setUp():void {
        $this->auth = new MiAuth("misskey.io", TOKEN);
    }
    
    public function testGenerateAuthURI()
    {
        $result = MiAuth::GenerateAuthURI("misskey.io", "TestApp", "http://localhost/callback", []);
        $url = parse_url($result);

        $this->assertEquals("misskey.io", $url['host']);

        if (preg_match("/[a-z0-9]{7}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/", $url['path'])) {
            $uuid_check = true;
        } else {
            $uuid_check = false;
        }

        $this->assertEquals(true, $uuid_check);
    }

    public function testGetAccessToken(){

    }
}
