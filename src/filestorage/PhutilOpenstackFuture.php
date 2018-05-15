<?php

abstract class PhutilOpenstackFuture extends FutureProxy {

  private $future;
  private $account;
  private $secretKey;
  private $region;
  private $httpMethod = 'GET';
  private $path = '/';
  private $endpoint;
  private $data = '';
  private $user;
  private $authToken;
  private $headers = array();

  abstract public function getServiceName();

  public function __construct() {
    parent::__construct(null);
  }

  public function setAccount($account) {
    $this->account = $account;
    return $this;
  }

  public function getAccount() {
    return $this->account;
  }

  public function setUserAndKey($user, PhutilOpaqueEnvelope $secret_key) {
    $this->user = $user;
    $this->secretKey = $secret_key;
    return $this;
  }

  public function getUser() {
    return $this->user;
  }

  public function getSecretKey() {
    return $this->secretKey;
  }

  public function getAuthToken() {
    return $this->authToken;
  }

  public function setAuthToken(PhutilOpaqueEnvelope $token) {
    $this->authToken = $token;
    return $this;
  }

  public function setEndpoint($endpoint) {
    $this->endpoint = $endpoint;
    return $this;
  }

  public function getEndpoint() {
    return $this->endpoint;
  }

  public function setHTTPMethod($method) {
    $this->httpMethod = $method;
    return $this;
  }

  public function getHTTPMethod() {
    return $this->httpMethod;
  }

  public function setPath($path) {
    $this->path = $path;
    return $this;
  }

  public function getPath() {
    return $this->path;
  }

  public function setData($data) {
    $this->data = $data;
    return $this;
  }

  public function getData() {
    return $this->data;
  }

  protected function getParameters() {
    return array();
  }

  public function addHeader($key, $value) {
    $this->headers[] = array($key, $value);
    return $this;
  }



  protected function getProxiedFuture() {
    if (!$this->future) {
      $params = $this->getParameters();
      $method = $this->getHTTPMethod();
      $host = $this->getEndpoint();
      $path = $this->getPath();
      $data = $this->getData();
      $account = $this->getAccount();

      $uri = id(new PhutilURI("{$host}"))
        ->setPath(
          implode('/', array('v1',$account,$path)))
        ->setQueryParams($params);

      $future = id(new HTTPSFuture($uri, $data))
        ->setMethod($method);

      foreach ($this->headers as $header) {
        list($key, $value) = $header;
        $future->addHeader($key, $value);
      }

      $this->future = $future;
    }

    return $this->future;
  }

  protected function shouldSignContent() {
    return false;
  }

  protected function didReceiveResult($result) {
    list($status, $body, $headers) = $result;

    try {
      $xml = @(new SimpleXMLElement($body));
    } catch (Exception $ex) {
      $xml = null;
    }

    if ($status->isError() || !$xml) {
      if (!($status instanceof HTTPFutureHTTPResponseStatus)) {
        throw $status;
      }

      $params = array(
        'body' => $body,
      );
      if ($xml) {
        $params['RequestID'] = $xml->RequestID[0];
        $errors = array($xml->Error);
        foreach ($errors as $error) {
          $params['Errors'][] = array($error->Code, $error->Message);
        }
      }

      throw new PhutilAWSException($status->getStatusCode(), $params);
    }

    return $xml;
  }

}
