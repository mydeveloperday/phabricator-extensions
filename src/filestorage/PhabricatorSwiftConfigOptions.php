<?php

final class PhabricatorSwiftConfigOptions
  extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Swift object storage');
  }

  public function getDescription() {
    return pht('Configure Swift Object Storage for uploads and git-lfs.');
  }

  public function getIcon() {
    return 'fa-hdd-o';
  }

  public function getGroup() {
    return 'core';
  }

  public function getOptions() {
    return array(

      $this->newOption('storage.swift.enabled', 'bool', false)
        ->setSummary(pht('Enable the swift storage engine.')),

      $this->newOption('storage.swift.account', 'string', 'phab')
        ->setSummary(pht('The swift storage account name.')),

      $this->newOption('storage.swift.container', 'string', 'phab')
        ->addExample('phab', pht('Default container name prefix'))
        ->setSummary(pht('The name prefix for phabricator containers.'))
        ->setDescription(
          pht('Phabricator will create a bunch of containers '.
            'named with the given prefix followed by a short random suffix')),

      $this->newOption('storage.swift.user', 'string', 'phabricator:files')
        ->setSummary(pht('The username for swift authentication')),

      $this->newOption('storage.swift.key', 'string', null)
        ->setHidden(true)
        ->setSummary(pht('Secret key for swift authentication.')),

      $this->newOption('storage.swift.endpoint', 'string', null)
        ->setSummary(pht('The url prefix for the swift cluster frontend.'))
        ->addExample('https://ms-fe01', pht('MediaStorage-FrontEnd01')),
    );
  }

}
