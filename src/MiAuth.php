<?php

declare(strict_types=1);

namespace Topi\MisskeyAuth;

use Exception;

class MiAuth
{

    public $instance = "misskey.io";
    private $token = null;

    public static function GenerateAuthURI($instance, $name, $callback, $permission): string
    {
        if (!isset($uuid)) $session = Util::GenerateUUID();
        $query =  http_build_query(
            array(
                "name" => $name,
                'callback' => $callback,
                'permission' => $permission
            ),
            $encoding_type = "PHP_QUERY_RFC3986"
        );
        $query = preg_replace('/%5B(\d+?)%5D/', '', $query);
        return "https://" . $instance . "/miauth/" . $session . "?" . $query;
    }

    public static function GetAccessToken($instance, $session)
    {
        if (!isset($session)) throw new Exception("Session isn't setting up.");
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://" . $instance . "/api/miauth/" . $session . "/check");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $results = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) != 200) throw new Exception("An error has occurred in API. " . curl_error($ch));
        curl_close($ch);
        $results = json_decode($results);
        if ($results->ok != true) throw new Exception("An error has occurred in API. ");
        $token = $results->token;

        return $token;
    }

    public function get($endpoint, $param = [])
    {
        if (!isset($this->token)) throw new Exception("Token cannot be empty.");
        $ch = curl_init();

        $token = ["i" => $this->token];
        $param = array_merge($param, $token);
        
        curl_setopt($ch, CURLOPT_URL, "https://" . $this->instance . "/api/" . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $results = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($code != 200) throw new Exception("An error has occurred in API. [".$code."] " . curl_error($ch));
        curl_close($ch);
        $results = json_decode($results);

        return $results;
    }

    public function SetToken($token)
    {
        $this->token = $token;
        return 0;
    }
}
