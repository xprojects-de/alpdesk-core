<?php

declare(strict_types=1);

namespace Alpdesk\AlpdeskCore\Utils;

class JsonProxy
{
    private string $url = '';
    private ?string $username = null;
    private ?string $password = null;
    private ?string $requestType = null;
    private $data = null;
    private bool $verifyHost = false;
    private bool $verifyPeer = false;
    private bool $followLocation = true;
    private bool $verbose = true;
    private array $customHeaders = array();
    private bool $requestEncode = false;
    private bool $responseDecode = false;

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setRequestType(string $requestType): void
    {
        $this->requestType = $requestType;
    }

    public function setData($data): void
    {
        $this->data = $data;
    }

    public function setVerifyHost(bool $verifyHost): void
    {
        $this->verifyHost = $verifyHost;
    }

    public function setVerifyPeer(bool $verifyPeer): void
    {
        $this->verifyPeer = $verifyPeer;
    }

    public function setFollowLocation(bool $followLocation): void
    {
        $this->followLocation = $followLocation;
    }

    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    public function setCustomHeaders(array $customHeaders): void
    {
        $this->customHeaders = $customHeaders;
    }

    public function setRequestEncode(bool $requestEncode): void
    {
        $this->requestEncode = $requestEncode;
    }

    public function setResponseDecode(bool $responseDecode): void
    {
        $this->responseDecode = $responseDecode;
    }

    /**
     * @return bool|mixed|string
     * @throws \Exception
     */
    public function exec()
    {
        if ($this->requestEncode == true && $this->data != null) {
            $this->data = json_encode($this->data);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $this->url);

        if ($this->data != null) {
            if ($this->requestType != null) {
                if ($this->requestType == 'PUT') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
                } else if ($this->requestType == 'DELETE') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                } else if ($this->requestType == 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data);
                }
            }
        }

        if ($this->username != null && $this->password != null) {
            curl_setopt($ch, CURLOPT_USERPWD, "$this->username:$this->password");
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifyHost);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyPeer);

        if (!function_exists('ini_get') || !ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        }

        curl_setopt($ch, CURLOPT_ENCODING, '');

        $headers = ['Content-Type: application/json'];

        if (count($this->customHeaders) > 0) {
            foreach ($this->customHeaders as $value) {
                array_push($headers, $value);
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->verbose);

        $response = curl_exec($ch);

        $http_response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (!$response) {

            $body = curl_error($ch);
            curl_close($ch);

            throw new \Exception(sprintf('CURL Error: http response=%d, %s', $http_response, $body));

        } else {

            curl_close($ch);

            if ($http_response != 200 && $http_response != 201) {
                throw new \Exception('CURL HTTP Request Failed: Status Code : ' . $http_response . ', URL:' . $this->url . "\nError Message : " . $response, $http_response);
            }
        }

        if ($this->responseDecode == true) {
            $response = json_decode($response, true);
        }

        return $response;
    }
}
