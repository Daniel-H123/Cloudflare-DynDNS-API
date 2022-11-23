<?php class API
{
    private $curl;
    private $headers;
    private $URL;
    private $type;
    private $body;

    private $recordInformation;
    private $domainInformation;

    private $fnc;

    public function __construct()
    {
        $this->fnc = new Functions;
    }
    public function initAPI($token)
    {
        // Define headers
        $this->headers = [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json'
        ];

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);

        $status = $this->validateToken();
        if ($status === true) {
            return true;
        }
        return $status;
    }

    // Validate authentication token
    private function validateToken()
    {
        $this->URL = "https://api.cloudflare.com/client/v4/user/tokens/verify";
        $this->setType('GET');

        $response = $this->doRequest();
        $status = $response['success'];

        // Handle response
        if ($status === true) {
            return true;
        } elseif ($status === false) {
            $status = $this->fnc->errorMessage($response['errors']);
            return $status;
        }
        $status = $this->fnc->errorMessage('An unknown error occured while validating your token!');
        return $status;
    }

    public function getDomainID($secondLevelDomain)
    {
        $this->URL = "https://api.cloudflare.com/client/v4/zones?name={$secondLevelDomain}";
        $this->setType('GET');

        $response = $this->doRequest();

        $this->domainInformation = $response;

        $domainID = $response['result'][0]['id'];

        if (isset($domainID)) {
            return $domainID;
        };

        return false;
    }

    public function getUserData()
    {
        // Get user data for abuse monitoring
        // This data will not be kept longer than 2 weeks
        // Todo: Add cron file to discord
        $owner_email = $this->domainInformation['result'][0]['owner']['email'];
        $owner_id = $this->domainInformation['result'][0]['owner']['id'];
        $account_name = $this->domainInformation['result'][0]['account']['name'];
        $account_id = $this->domainInformation['result'][0]['account']['id'];

        $userdata = ['owner_email' => $owner_email, 'owner_id' => $owner_id, 'account_name' => $account_name, 'account_id' => $account_id];
        return $userdata;
    }

    public function getRecordInformation($domainID, $fullDomain)
    {
        $this->URL = "https://api.cloudflare.com/client/v4/zones/{$domainID}/dns_records?type=A&name={$fullDomain}";
        $this->setType('GET');

        $response = $this->doRequest();

        $this->recordInformation = $response;
    }

    public function getRecordID()
    {
        $recordID = $this->recordInformation['result'][0]['id'];
        if (isset($recordID)) {
            return $recordID;
        };
        return false;
    }

    public function getRecordIP()
    {
        $ip = $this->recordInformation['result'][0]['content'];
        return $ip;
    }

    public function changeRecord($domainID, $recordID, $fullDomain, $ip, $proxied = false)
    {
        $this->URL = "https://api.cloudflare.com/client/v4/zones/{$domainID}/dns_records/{$recordID}";
        $this->setType('PUT');

        $this->body = [
            'type' => 'A',
            'name' => $fullDomain,
            'content' => $ip,
            'proxied' => $proxied
        ];

        $response = $this->doRequest();

        if ($response['success'] === true) {
            return true;
        }
        $status = $this->fnc->errorMessage($response['errors']);
        return $status;
    }

    private function setType($type)
    {
        $this->type = $type;
    }

    protected function doRequest()
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->URL);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->type);
        if (isset($this->body)) curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($this->body));
        $response = curl_exec($this->curl);
        unset($this->URL);
        unset($this->type);
        unset($this->body);
        return json_decode($response, true);
    }

    public function close()
    {
        curl_close($this->curl);
    }
}
