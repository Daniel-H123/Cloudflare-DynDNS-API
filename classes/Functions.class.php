<?php class Functions
{
    public $ip;
    public $fullDomain;

    // Get user data for abuse monitoring
    // This data will not be kept longer than 2 weeks
    private $owner_email;
    private $owner_id;
    private $account_name;
    private $account_id;

    // Extract domain if record domain is subdomain (example.domain.com => domail.com)
    // NOTE: This does not work on example.co.uk domains!
    public function getSecondLevelDomain($fullDomain)
    {
        $pattern = "/[^.]+\.[^.]+$/";
        $matches = array();
        preg_match($pattern, $fullDomain, $matches);
        $secondLevelDomain = $matches[0];
        return $secondLevelDomain;
    }

    public function errorMessage($error)
    {
        if (!is_array($error)) {
            $error = [[
                'code' => '',
                'message' => $error
            ]];
        }
        $status = [
            'success' => false,
            'errors' => $error
        ];
        return $status;
    }

    public function successMessage()
    {
        $status = [
            'success' => true,
            'errors' => []
        ];
        return $status;
    }

    public function saveUserData($userData) {
        $this->owner_email = $userData['owner_email'];
        $this->owner_id = $userData['owner_id'];
        $this->account_name = $userData['account_name'];
        $this->account_id = $userData['account_id'];

        return;
    }

    // Store user data for abuse monitoring
    // This data will not be kept longer than 2 weeks
    public function storeSession($response)
    {
        $Store = new Store;

        $ip = $this->ip;
        $domain = $this->fullDomain;
        $owner_email = $this->owner_email;
        $owner_id = $this->owner_id;
        $account_name = $this->account_name;
        $account_id = $this->account_id;

        $Store->session($ip, $domain, $owner_email, $owner_id, $account_name, $account_id, $response);
    }

    public function response($status, $response_code)
    {
        http_response_code($response_code);
        header('Content-Type: application/json');
        $status = json_encode($status);
        $this->storeSession($status);
        return $status;
    }
}
