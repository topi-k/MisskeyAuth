<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Topi\MisskeyAuth\MiAuth;

require "vars.php";

class MiAuthTest extends TestCase
{
    protected $auth;

    protected function setUp(): void
    {
        $this->auth = new MiAuth;
        $this->auth->SetUserToken(TOKEN);
    }

    public function testGet()
    {
        $this->auth->get("i",[]);
        $this->assertEquals(200, $this->auth->getLastResultCode());
    }

    public function testPost()
    {
        $this->auth->post("notes/create",["text" => "This note was submitted by MisskeyAuth (for PHP).", "visibility" => "followers"]);
        $this->assertEquals(200, $this->auth->getLastResultCode());
    }

    public function testGenerateAuthURI(){
        $url = $this->auth->generateAuthURI("TestApp", "https://localhost/callback", ["write:drive, write:notes"]);
        $this->assertNotEmpty($url);
    }

}
