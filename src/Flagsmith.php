<?php

namespace Flagsmith;

use Exception;

class Flagsmith
{
    private $url;
    private $curl;
    private $headers = [];

    public function __construct($api_key, $url = 'https://api.flagsmith.io/api/v1/')
    {
        array_push(
            $this->headers,
            "Accept: application/json",
            "Content-Type: application/json",
            "X-Environment-Key: {$api_key}"
        );

        $this->url = $url;
    }

    public function getFlags($user_id = null)
    {
        if (is_null($user_id)) {
            $data = $this->transformFlags($this->query('flags/', 'GET'));

            $data = array_filter($data, function ($flag) {
                if (is_null($flag['segment'])) {
                    return $flag;
                }
            });

            return $this->flagsToHash($data);
        } else {
            $res = $this->query("identities/?identifier={$user_id}", "GET");

            return $this->flagsToHash($this->transformFlags($res->flags));
        }
    }

    public function featureEnabled($feature, $user_id = null, $default = false)
    {
        $flag = $this->getFlags($user_id)[$this->normalizeKey($feature)];

        if (is_null($flag)) {
            return $default;
        } else {
            return $flag['enabled'];
        }
    }

    public function getValue($key, $user_id = null, $default = null)
    {
        $flag = $this->getFlags($user_id)[$this->normalizeKey($key)];

        if (is_null($flag)) {
            return $default;
        } else {
            return $flag['value'];
        }
    }

    public function setTrait($user_id, $trait, $value)
    {
        if (is_null($user_id)) {
            return new Exception('user_id cannot be null');
        } else {
            $trait = [
                'identity' => [
                    'identifier' => $user_id
                ],
                'trait_key' => $this->normalizeKey($trait),
                'trait_value' => $value
            ];

            return $this->query('traits/', 'POST' , json_encode($trait));
        }
    }

    public function getTraits($user_id = null)
    {
        if (is_null($user_id)) {
            return [];
        } else {
            $data = $this->query("identities/?identifier={$user_id}", "GET");

            return $this->transformFlags($data->flags);
        }
    }

    private function query($path, $method, $data = null)
    {
        $this->curl = curl_init();

        if ($method == 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, true);
            if (!is_null($data)) {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
            }
        }

        curl_setopt($this->curl, CURLOPT_URL, $this->url . $path);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
        $result = json_decode(curl_exec($this->curl));
        curl_close($this->curl);

        return $result;
    }

    private function flagsToHash($flags)
    {
        $results = [];

        foreach ($flags as $flag) {
            $key = $this->normalizeKey($flag['name']);
            $results[$key] = $flag;
            unset($results[$key]['name']);
        }

        return $results;
    }

    private function normalizeKey($key)
    {
        return strtolower($key);
    }

    private function transformFlags($flags)
    {
        $results = [];

        foreach ($flags as $flag) {
            array_push($results, [
                'name' => $flag->feature->name,
                'enabled' => $flag->enabled,
                'value' => $flag->feature_state_value,
                'segment' => $flag->feature_segment
            ]);
        }

        return $results;
    }
}
