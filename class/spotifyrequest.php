<?php

class SpotifyRequest {
    const ACTION_GET    = "GET";
    const ACTION_POST   = "POST";

    const TYPE_UNKNOWN              = 0;
    const TYPE_OAUTH_AUTHORIZE      = 1;
    const TYPE_OAUTH_GETTOKEN       = 2;
    const TYPE_OAUTH_REFRESHTOKEN   = 3;
    const TYPE_API_CALL             = 4;

    const CONTENT_TYPE_UNKNOWN      = '';
    const CONTENT_TYPE_JSON         = 'application/json';
    const CONTENT_TYPE_FORM_ENCODED = 'application/x-www-form-urlencoded';

    public int $type = SpotifyRequest::TYPE_UNKNOWN;
    public string $endpoint = '';
    public string $action = '';
    public string $contentType = SpotifyRequest::CONTENT_TYPE_UNKNOWN;
    public $headers = [];
    public bool $returnTransfer = true;
    public $result = null;
    public $info = null;
    public string $error_message = '';
    public int $error_number = 0;
    protected $ch = null;

    public function __construct($type, $action, $endpoint) {
        $this->type = $type;
        $this->action = $action;
        $this->endpoint = $endpoint;
    }

    public function setHeader($key, $value) : SpotifyRequest {
        $headers[$key] = $value;
        return $this;
    }

    public function send($data) : SpotifyRequest {
        global $config;

        // Initialise curl
        $this->ch = curl_init($this->endpoint);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_VERBOSE, true);

        // Set content-type header if appropriate
        if (!empty($this->contentType)) {
            $this->headers['Content-type'] = $this->contentType;
        }

        // Set Basic Auth if appropriate
        if (($this->type == SpotifyRequest::TYPE_OAUTH_GETTOKEN) || ($this->type == SpotifyRequest::TYPE_OAUTH_REFRESHTOKEN)) {
            curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($this->ch, CURLOPT_USERPWD, $config['SPOTIFY_CLIENTID'].':'.$config['SPOTIFY_CLIENTSECRET']);
        }

        // Set Authorization header if appropriate
        if ($this->type == SpotifyRequest::TYPE_API_CALL) {
            $this->headers['Authorization'] = "Bearer {$_SESSION['USER_ACCESSTOKEN']}";
        }

        // Parse headers
        $sendHeaders = [];
        foreach ($this->headers as $k=>$v) {
            $sendHeaders[] = "{$k}: {$v}";
        }

        // Set headers in curl
        curl_setopt($this->ch,CURLOPT_HTTPHEADER, $sendHeaders);

        // Set action and data fields in curl
        if ($this->action == SpotifyRequest::ACTION_POST) {
            curl_setopt($this->ch,CURLOPT_POST,1);
            if ($this->contentType == SpotifyRequest::CONTENT_TYPE_JSON) {
                curl_setopt($this->ch,CURLOPT_POSTFIELDS,json_encode($data));
            } else {
                curl_setopt($this->ch,CURLOPT_POSTFIELDS,$data);
            }
        } else {
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET' );
            // Add or extend querystring
            $url_parts = parse_url($this->endpoint);
            if (empty($url_parts['query'])) {
                // Create a querystring
                curl_setopt($this->ch, CURLOPT_URL, $this->endpoint . '?' . http_build_query($data));
            } else {
                curl_setopt($this->ch, CURLOPT_URL, $this->endpoint . '&' . http_build_query($data));
            }
        }
        
        // Set return transfer
        if ($this->returnTransfer) { curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); }

        // EXECUTE
        // TODO - remove curl error logging
        $fh = fopen('curl.err.log','w');
        curl_setopt($this->ch,CURLOPT_STDERR,$fh);
        $this->result = curl_exec($this->ch);
        $this->info = curl_getinfo($this->ch);
        $this->error_message = curl_error($this->ch);
        $this->error_number = curl_errno($this->ch);
        curl_close($this->ch);
        fclose($fh);

        return $this;
    }
}