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


    /**
     * Functions that use the API without parameters.
     * Note: For sending an API key, the function name is "GET" but it is sent as POST.
     * 
     * @param string $endpoint
     * @param array  $param  (optional)
     * 
     * @return object
     */

    public function get(string $endpoint, array $param = []): object
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($token, $param);
        }
        $results = $this->makeRequest("GET", $endpoint, $param);
        return $results;
    }

    /**
     * Functions that use the API with parameters.
     * Note: To send a file, assign a directory to $file.
     * 
     * @param string $endpoint
     * @param array  $param (optional)
     * @param string $file  (optional)
     * 
     * @return object
     */

    public function post(string $endpoint, array $param = [], string $file = null): object
    {
        if ($this->isSetToken()) {
            $token = ["i" => $this->token];
            $param = array_merge($token, $param);
        }

        if ($file) {
            if (!file_exists($file)) throw new MisskeyAuthException("File not found.");
        }

        $results = $this->makeRequest("POST", $endpoint, $param, $file);
        return $results;
    }

    /**
     * Function to obtain the URL to be used during authentication.
     * The UUID is automatically generated.
     * 
     * @param string $name
     * @param string $callback
     * @param array  $permission
     * 
     * @return string
     */

    public function generateAuthURI(string $name, string $callback, array $permission): string
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

    /**
     * Function to issue an access token from a UUID.
     * Note: Use the SetUUUID function to set the UUID before use.
     * 
     * @return string
     */
    public function getAccessToken(): string
    {
        if (!$this->isSetInstance()) throw new MisskeyAuthException("Instance cannot be empty.");
        if (!$this->isSetUUID()) throw new MisskeyAuthException("UUID cannot be empty.");
        $results = $this->get("/miauth/" . $this->uuid . "/check", []);
        if (isset($results->token)) {
            return $results->token;
        } else {
            return "";
        }
    }

    /**
     * Function to generate a request, used from the Post/Get function to generate CURL parameters.
     * The generated parameters are passed to the request function.
     * 
     * @param string $method
     * @param string $endpoint
     * @param array  $param (optional)
     * @param string $file  (optional)
     * 
     * @return object
     */
    private function makeRequest(string $method, string $endpoint, array $param = [], string $file = null): object
    {
        if (!$this->isSetInstance()) throw new MisskeyAuthException("Instance cannot be empty.");
        $options = array(
            CURLOPT_URL => "https://" . $this->instance . "/api/" . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => $this->timeout_ms
        );

        if ($method == "POST" && $file) {
            if (!is_readable($file)) throw new MisskeyAuthException("File is not readable.");
            $file = new CURLFile($file, 'image/jpeg', 'file');
            $param += ['file' => $file];
            $options += [CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data']];
            $options += [CURLOPT_POSTFIELDS => $param];
        } else {
            $options += [CURLOPT_HTTPHEADER => ['Content-Type: application/json']];
            $options += [CURLOPT_POSTFIELDS => json_encode($param)];
        }

        $this->resetLastResultCode();
        $this->resetAttemptsCount();

        return $this->request($options);
    }

    /**
     * Function to issue an HTTP request based on the parameters passed.
     * If HTTP code 500 or more is returned, the request is automatically retried within max_attempts.
     * 
     * @param array $option
     * 
     * @return object
     */
    private function request(array $options): object
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

    /**
     * Function used in the Request function to determine if a request can be made.
     * (Less than or equal to the maximum number of attempts and the last request returned over 500.)
     * 
     * @return bool
     */
    private function requestAvailable(): bool
    {
        return $this->attempts < $this->max_attempts && $this->getLastResultCode() >= 500;
    }

    /**
     * Function to return the HTTP code obtained from the last CURL.
     * 
     * @return int
     */
    public function getLastResultCode(): int
    {
        return $this->last_result_code;
    }

    /**
     * Function to erase the last stored HTTP code.
     */
    private function resetLastResultCode(): void
    {
        $this->last_result_code = null;
        return;
    }

    /**
     * Function to reset the number of attempts.
     */
    private function resetAttemptsCount(): void
    {
        $this->attempts = 0;
        return;
    }

    /**
     * Function to set a token.
     * 
     * @param string $token
     */
    public function setUserToken(string $token): void
    {
        $this->token = $token;
        return;
    }

    /**
     * Function to set the instance to make the connection.
     * (By default, "misskey.io" is set.)
     * 
     * @param string $instance
     */
    public function setInstance(string $instance): void
    {
        $this->instance = $instance;
        return;
    }

    /**
     * Sets the maximum waiting time for CURL connections. Set in milliseconds.
     * (If it times out, a "MisskeyAuthException" will be returned without retry.)
     * 
     * @param int $timeout_ms
     */
    public function setTimeout(int $timeout_ms): void
    {
        $this->timeout_ms = $timeout_ms;
        return;
    }

    /**
     * Set the UUID required to obtain a token. If a token has been obtained, no setting is required.
     * 
     * @param string $uuid
     */
    public function setUUID(string $uuid): void
    {
        $this->uuid = $uuid;
        return;
    }

    /**
     * Function to determine if a token has been set.
     * 
     * @return bool
     */
    private function isSetToken(): bool
    {
        return isset($this->token);
    }

    /**
     * Function to determine if an instance is set.
     * 
     * @return bool
     */
    private function isSetInstance(): bool
    {
        return isset($this->instance);
    }

    /**
     * Function to determine if a UUID is set.
     * 
     * @return bool
     */
    private function isSetUUID(): bool
    {
        return isset($this->uuid);
    }
}
