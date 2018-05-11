<?php

final class PhutilSwiftFuture extends PhutilOpenstackFuture {

  private $container;

  public function getServiceName() {
    return 'swift';
  }

  /**
   * Set the container name prefix
   */
  public function setContainer($container) {
    $this->container = $container;
    return $this;
  }

  /**
   * Get the container name to use for storing a given key
   */
  public function getContainer($key) {
    $key = substr(md5sum($key), 0, 2);
    return $this->container.'-'.$key;
  }

  /**
   * Get the container + object path for an object with a given key.
   */
  public function getPathForObject($key) {
    return $this->getContainer($key) . '/' . $key;
  }

  public function setParametersForGetObject($key) {
    $this->setHTTPMethod('GET');
    $this->setPath($this->getPathForObject($key));
    $this->addHeader('X-Auth-Token', $this->getAuthToken());
    return $this;
  }

  public function setParametersForPutContainer($key) {
    $this->setHTTPMethod('PUT');
    $this->setPath($this->getContainer($key));
    $this->addHeader('X-Auth-Token', $this->getAuthToken());
    return $this;
  }

  public function setParametersForPutObject($key, $value) {
    $this->setHTTPMethod('PUT');
    $this->setPath($this->getPathForObject($key));
    $this->addHeader('X-Auth-Token', $this->getAuthToken());
    $this->addHeader('Content-Type', 'application/octet-stream');
    $this->setData($value);
    return $this;
  }

  public function setParametersForDeleteObject($key) {
    $this->setHTTPMethod('DELETE');
    $this->setPath($this->getPathForObject($key));
    $this->addHeader('X-Auth-Token', $this->getAuthToken());
    return $this;
  }

  protected function didReceiveResult($result) {
    list($status, $body, $headers) = $result;

    if (!$status->isError()) {
      return $body;
    }

    return parent::didReceiveResult($result);
  }

  protected function shouldSignContent() {
    return false;
  }

}
