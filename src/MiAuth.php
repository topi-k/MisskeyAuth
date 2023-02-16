<?php

declare(strict_types=1);

namespace Topi\MisskeyAuth;

use Exception;
use CURLFile;

class MiAuth
{

    public $instance = "misskey.io";
    
    private $last_result_code = null;
    private $attempts = 0;
    private $max_attempts = 5;
    private $token = null;

    private function makeRequest($method, $instance, $endpoint, $param) {
        $options = array(
            CURLOPT_URL => "https://" . $this->instance . "/api/" . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $param,
        );

        switch($method){
            case "POST":
                $options .= array(CURLOPT_HTTPHEADER => array('Content-Type: multipart/form-data'));
                break;
            case "GET":
                $options .= array(CURLOPT_HTTPHEADER => array('Content-Type: application/json'));  
                break;
        }

        $this->resetLastResultCode();
        $this->resetAttemptsCount();

        return request($method, $instance, $endpoint, $options)
    }
    
    private function request($method, $instance, $endpoint, $options) {
        do {
            $this->connection = curl_init();
            curl_setopt_array($this->connection, $options);
            $results = curl_exec($this->connection);
            $results = json_decode($results);
        } while ($this->requestAvailable());
    }

    private function requestAvailable() {
        return getHttpCode() > 500 || getHttpCode() == 200;
    }



    private function getHttpCode() {
        $code = curl_getinfo($this->connection, CURLINFO_RESPONSE_CODE);
        if ($code) {
            return $code;
        } else {
            return null;
        }
    }

    public function getLastResultCode(): int {
        return $this->last_result_code;
    }

    private function resetLastResultCode(): void {
        $this->last_result_code = null;
        return;
    }

    private function resetAttemptsCount(): void {
        $this->attempts = 0;
        return;
    }

    public function setUserToken($token): void {
        $this->token = $token;
        return;
    }
}
