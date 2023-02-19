<?php

declare(strict_types=1);

namespace Topi\MisskeyAuth;

use CURLFile;

class MisskeyAuthException extends \Exception
{
}

class MiAuth
{

    private $instance = "misskey.io";
    private $uuid = null;

    private $attempts = 0;
    private $max_attempts = 5;
    private $token = null;
    private $timeout_ms = 10000;

    private $last_result_code = null;
    private $connection = null;

    public function get($endpoint, $param = [])
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($token,$param);
        }
        $results = $this->makeRequest("GET", $endpoint, $param);
        return $results;
    }

    public function post($endpoint, $param = [], $file = null)
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($token,$param);
        }

        $results = $this->makeRequest("POST", $endpoint, $param, isset($file));
        return $results;
    }

    public function UploadtoDrive($param = [], $file)
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($token,$param);
        }
        if (!file_exists($file)) throw new MisskeyAuthException("File not found.");

        $results = $this->makeRequest("POST", "drive/files/create", $param, $file);
        return $results;
    }

    public function generateAuthURI($name, $callback, $permission): string
    {
        if (!$this->isSetInstance()) throw new MisskeyAuthException("Instance cannot be empty.");
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
        if (!$this->isSetInstance()) throw new MisskeyAuthException("Instance cannot be empty.");
        if (!$this->isSetUUID()) throw new MisskeyAuthException("UUID cannot be empty.");
        $results = $this->get("/miauth/" . $this->uuid . "/check", []);
        if (isset($results->token)) {
            return $results->token;
        } else {
            return null;
        }
    }

    private function makeRequest($method, $endpoint, $param, $file = null)
    {
        if (!$this->isSetInstance()) throw new MisskeyAuthException("Instance cannot be empty.");
        $options = array(
            CURLOPT_URL => "https://" . $this->instance . "/api/" . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $this->timeout_ms
        );
        
        switch ($method) {
            case "POST":
                if ($file) {
                    if (!is_readable($file)) throw new MisskeyAuthException("File is not readable.");
                    $file = new CURLFile($file,'image/jpeg','file');
                    $param += ['file' => $file];
                    $options += [CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']];
                } else {
                    $options += [CURLOPT_HTTPHEADER => ['Content-Type: application/json']];
                }
                break;
            case "GET":
                $options += [CURLOPT_HTTPHEADER => ['Content-Type: application/json']];
                break;
        }
        $options += [CURLOPT_POSTFIELDS => $param];

        $this->resetLastResultCode();
        $this->resetAttemptsCount();

        return $this->request($options);
    }

    private function request($options)
    {
        if (!$this->isSetInstance()) throw new MisskeyAuthException("Instance cannot be empty.");
        do {
            $this->connection = curl_init();
            curl_setopt_array($this->connection, $options);
            $results = curl_exec($this->connection);
            if (curl_errno($this->connection) > 0) {
                $error = curl_error($this->connection);
                $errorNo = curl_errno($this->connection);
                curl_close($this->connection);
                throw new MisskeyAuthException($error, $errorNo);
            }
            $this->attempts++;
            $this->last_result_code = curl_getinfo($this->connection, CURLINFO_RESPONSE_CODE);
        } while ($this->requestAvailable());

        $results = json_decode($results);
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

    public function setTimeout($timeout_ms): void
    {
        $this->timeout_ms = $timeout_ms;
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
