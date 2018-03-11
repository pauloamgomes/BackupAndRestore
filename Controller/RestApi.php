<?php

namespace BackupAndRestore\Controller;

use \LimeExtra\Controller;

/**
 * RestApi class for remove handling of backups.
 */
class RestApi extends Controller {

  protected $options = [
    'collections' => ['key' => 'collections', 'value' => TRUE],
    'regions' => ['key' => 'regions', 'value' => TRUE],
    'forms' => ['key' => 'forms', 'value' => TRUE],
    'config' => ['key' => 'config', 'value' => TRUE],
    'accounts' => ['key' => 'accounts', 'value' => TRUE],
    'webhooks' => ['key' => 'webhooks', 'value' => TRUE],
    'entries' => ['key' => 'entries', 'value' => TRUE],
    'assets' => ['key' => 'assets', 'value' => TRUE],
    'uploads' => ['key' => 'uploads', 'value' => TRUE],
  ];

  /**
   * Perform a backup.
   */
  public function create() {
    $description = $this->param("description", 'Automated backup using REST');
    $requestOptions = $this->param("options", array_keys($this->options));
    $options = [];
    foreach ($this->options as $key => $option) {
      $options[$key] = [
        'key' => $key,
        'value' => in_array($key, $requestOptions),
      ];
    }

    return $this->app->module('backupandrestore')->saveBackup($description, $options);
  }

}
