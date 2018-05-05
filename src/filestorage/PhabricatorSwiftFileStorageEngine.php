<?php

/**
 * Swift file storage engine. This engine scales well but is relatively
 * high-latency since data has to be pulled off Swift.
 *
 * @task internal Internals
 */
final class PhabricatorSwiftFileStorageEngine
  extends PhabricatorFileStorageEngine {


/* -(  Engine Metadata  )---------------------------------------------------- */


  /**
   * This engine identifies as `swift`.
   */
  public function getEngineIdentifier() {
    return 'swift';
  }

  public function getEnginePriority() {
    return 100;
  }

  public function canWriteFiles() {
    $container = PhabricatorEnv::getEnvConfig('storage.swift.container');
    $account = PhabricatorEnv::getEnvConfig('storage.swift.account');
    $key = PhabricatorEnv::getEnvConfig('storage.swift.key');
    $endpoint = PhabricatorEnv::getEnvConfig('storage.swift.endpoint');

    return (strlen($container) &&
      strlen($account) &&
      strlen($key) &&
      strlen($endpoint));

  }


/* -(  Managing File Data  )------------------------------------------------- */


  /**
   * Writes file data into swift.
   */
  public function writeFile($data, array $params) {
    $object = $this->newSwiftAPI();
    $container = $this->newSwiftAPI();

    // Generate a random name for this file. We add some directories to it
    // (e.g. 'abcdef123456' becomes 'ab/cd/ef123456') to make large numbers of
    // files more browsable with web/debugging tools like the Swift administration
    // tool.
    $seed = Filesystem::readRandomCharacters(20);
    $parts = array();

    $parts[] = substr($seed, 0, 2);
    $parts[] = substr($seed, 2, 2);
    $parts[] = substr($seed, 4);

    $name = implode('/', $parts);

    AphrontWriteGuard::willWrite();
    $profiler = PhutilServiceProfiler::getInstance();
    $call_id = $profiler->beginServiceCall(
      array(
        'type' => 'swift',
        'method' => 'putObject',
      ));

    $res = $container
      ->setParametersForPutContainer($name)
      ->resolve();

    $res = $object
      ->setParametersForPutObject($name, $data)
      ->resolve();

    $profiler->endServiceCall($call_id, array());

    return $name;
  }


  /**
   * Load a stored blob from swift.
   */
  public function readFile($handle) {
    $swift = $this->newSwiftAPI();

    $profiler = PhutilServiceProfiler::getInstance();
    $call_id = $profiler->beginServiceCall(
      array(
        'type' => 'swift',
        'method' => 'getObject',
      ));

    $result = $swift
      ->setParametersForGetObject($handle)
      ->resolve();

    $profiler->endServiceCall($call_id, array());

    return $result;
  }


  /**
   * Delete a blob from swift.
   */
  public function deleteFile($handle) {
    $swift = $this->newSwiftAPI();

    AphrontWriteGuard::willWrite();
    $profiler = PhutilServiceProfiler::getInstance();
    $call_id = $profiler->beginServiceCall(
      array(
        'type' => 'swift',
        'method' => 'deleteObject',
      ));

    $swift
      ->setParametersForDeleteObject($handle)
      ->resolve();

    $profiler->endServiceCall($call_id, array());
  }


/* -(  Internals  )---------------------------------------------------------- */


  /**
   * Retrieve the swift container name.
   *
   * @task internal
   */
  private function getContainerName() {
    $container = PhabricatorEnv::getEnvConfig('storage.swift.container');
    if (!$container) {
      throw new PhabricatorFileStorageConfigurationException(
        pht(
          "No '%s' specified!",
          'storage.swift.container'));
    }
    return $container;
  }

  /**
   * Create a new swift API object.
   *
   * @task internal
   */
  private function newSwiftAPI() {
    $container = PhabricatorEnv::getEnvConfig('storage.swift.container');
    $account = PhabricatorEnv::getEnvConfig('storage.swift.account');
    $key = PhabricatorEnv::getEnvConfig('storage.swift.key');
    $endpoint = PhabricatorEnv::getEnvConfig('storage.swift.endpoint');

    return id(new PhutilSwiftFuture())
      ->setAccount($account)
      ->setSecretKey(new PhutilOpaqueEnvelope($key))
      ->setEndpoint($endpoint)
      ->setContainer($container);
  }

}
