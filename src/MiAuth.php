<?php

declare(strict_types=1);

namespace Topi\MisskeyAuth;

use Exception;
use CURLFile;

class MiAuth
{

    private $instance = "misskey.io";
    private $uuid = null;

    private $attempts = 0;
    private $max_attempts = 5;
    private $token = null;

    private $last_result_code = null;

    private $connection = null;

    public function get($endpoint, $param = [])
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($param, $token);
        }
        $results = $this->makeRequest("GET", $endpoint, $param);
        return $results;
    }

    public function post($endpoint, $param = [], $file = null)
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($param, $token);
        }

        $results = $this->makeRequest("POST", $endpoint, $param, isset($file));
        return $results;
    }

    public function UploadtoDrive($param = [], $file)
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($param, $token);
        }
        if (!file_exists($file)) throw new Exception("File not found.");
        
        $results = $this->makeRequest("POST","drive/files/create",[],$file);
        return $results;
    }

    public function generateAuthURI($name, $callback, $permission): string
    {
        if (!$this->isSetInstance()) throw new Exception("Instance cannot be empty.");
        $query =  http_build_query(
            array(
                "name" => $name,
                'callback' => $callback,
                'permission' => $permission
            ),
        );
        $query = preg_replace('/%5B(\d+?)%5D/', '', $query);
        return "https://" . $this->instance . "/miauth/" . Util::GenerateUUID() . "?" . $query;
    }

    public function getAccessToken()
    {
        if (!$this->isSetInstance()) throw new Exception("Instance cannot be empty.");
        if (!$this->isSetUUID()) throw new Exception("UUID cannot be empty.");
        $results = $this->get("/miauth/" . $this->uuid . "/check", []);
        if (isset($results->token)) {
            return $results->token;
        } else {
            return null;
        }
    }

    private function makeRequest($method, $endpoint, $param, $file = null)
    {
        if (!$this->isSetInstance()) throw new Exception("Instance cannot be empty.");
        $options = array(
            CURLOPT_URL => "https://" . $this->instance . "/api/" . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
        );
        switch ($method) {
            case "POST":
                if ($file) {
                    $cfile = new CURLFile($file,'image/jpeg','test_name');
                    $param += ['file' => $cfile];
                    $options += [CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']];
                } else {
                    $options += [CURLOPT_HTTPHEADER => ['Content-Type: application/json']];
                }
                break;
            case "GET":
                $options += [CURLOPT_HTTPHEADER => ['Content-Type: application/json']];
                break;
        }
        $options += [CURLOPT_POSTFIELDS => json_encode($param)];

        var_dump($options);
        $this->resetLastResultCode();
        $this->resetAttemptsCount();

        return $this->request($options);
    }

    private function request($options)
    {
        if (!$this->isSetInstance()) throw new Exception("Instance cannot be empty.");
        do {
            $this->connection = curl_init();
            var_dump($options);
            curl_setopt_array($this->connection, $options);
            $results = curl_exec($this->connection);
            $results = json_decode($results);
            $this->attempts++;
            $this->last_result_code = curl_getinfo($this->connection, CURLINFO_RESPONSE_CODE);
        } while ($this->requestAvailable());

        return $results;
    }

    private function requestAvailable()
    {
        return $this->attempts < $this->max_attempts && $this->getLastResultCode() >= 500;
    }

    public function getLastResultCode(): int
    {
        return $this->last_result_code;
    }

    private function resetLastResultCode(): void
    {
        $this->last_result_code = null;
        return;
    }

    private function resetAttemptsCount(): void
    {
        $this->attempts = 0;
        return;
    }

    public function setUserToken($token): void
    {
        $this->token = $token;
        return;
    }

    public function setInstance($instance): void
    {
        $this->instance = $instance;
        return;
    }

    public function setUUID($uuid): void
    {
        $this->uuid = $uuid;
        return;
    }

    private function isSetToken(): bool
    {
        return isset($this->token);
    }

    private function isSetInstance(): bool
    {
        return isset($this->instance);
    }

    private function isSetUUID(): bool
    {
        return isset($this->uuid);
    }
}
