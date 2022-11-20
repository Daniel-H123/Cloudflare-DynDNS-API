<?php class API
{
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

    public function REQUEST($type, $URL, $headers, $body = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (isset($body)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function response($http_response, $status)
    {
        http_response_code($http_response);
        header('Content-Type: application/json');
        $status = json_encode($status);
        die($status);
    }
}
