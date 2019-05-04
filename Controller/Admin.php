<?php

namespace BackupAndRestore\Controller;

use \Cockpit\AuthController;
use \ZipArchive;
use \Spyc;

/**
 * Admin controller class.
 */
class Admin extends AuthController {

  protected $definitions = [
    'collections' => 'collections',
    'regions' => 'regions',
    'singletons' => 'singletons',
    'forms' => 'forms',
    'config' => 'config',
    'accounts' => 'accounts',
    'webhooks' => 'webhooks',
    'entries' => 'entries',
    'assets' => 'assets',
    'uploads' => 'uploads',
  ];

  /**
   * Default index controller.
   */
  public function index() {
    if (!$this->app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.view')) {
      return FALSE;
    }

    if (!$this->app->path('#storage:backups')) {
      if (!$this->app->helper('fs')->mkdir($this->app->path('#storage:') . '/backups')) {
        return FALSE;
      }
    }

    $backupsPath = $this->app->path('#storage:') . '/backups';
    $backupsPath = trim(str_replace($this->app->path('#root:'), '', $backupsPath));

    return $this->render('backupandrestore:views/backups/index.php', [
      'backupsPath' => $backupsPath,
    ]);
  }

  /**
   * Find controller for retrieving backups.
   */
  public function find() {
    $options = array_merge(['sort' => ['created' => 1]], $this->param('options', []));

    $backups = [];
    $zip = new ZipArchive();
    $total = 0;

    foreach ($this->app->helper("fs")->ls('bak-*.zip', '#storage:backups') as $path) {
      if (!$path->isReadable() || $path->getExtension() !== 'zip') {
        continue;
      }
      if ($zip->open($path->getRealPath()) === TRUE) {
        $total++;
        $data = Spyc::YAMLLoad($zip->getFromName('backup.yaml', 0, ZipArchive::FL_NODIR));
        if (!$data && !isset($data['backup'])) {
          continue;
        }
        if (!empty($options['filter'])) {
          if (stripos($path->getFilename(), $options['filter']) === FALSE
            && stripos($data['backup']['description'], $options['filter']) === FALSE) {
            continue;
          }
        }

        $definitions = [];
        foreach ($this->definitions as $definition) {
          $definitions[$definition] = isset($data['backup'][$definition]) && $data['backup'][$definition];
        }

        $backups[] = [
          'name' => str_replace('.zip', '', $path->getFilename()),
          'filename' => $path->getFilename(),
          'created' => $data['backup']['created'],
          'description' => $data['backup']['description'],
          'definitions' => $definitions,
          'path' => $path->getRealPath(),
          'size' => $path->getSize(),
        ];
        $zip->close();
      }
    }

    usort($backups, function ($a, $b) use ($options) {
      $field = key($options['sort']);
      $order = $options['sort'][$field];
      return $order < 1 ? $a[$field] <=> $b[$field] : $b[$field] <=> $a[$field];
    });

    $count = (!isset($options['skip']) && !isset($options['limit'])) ? count($backups) : $total;
    $pages = isset($options['limit']) ? ceil($count / $options['limit']) : 1;
    $page = 1;

    if ($pages > 1 && isset($options['skip'])) {
      $page = ceil($options['skip'] / $options['limit']) + 1;
    }

    if (!empty($backups) && isset($options['limit'])) {
      $backups = array_chunk($backups, $options['limit']);
      $backups = $backups[$page - 1];
    }

    return compact('backups', 'count', 'pages', 'page');
  }

  /**
   * View backup controller.
   */
  public function view($backup) {
    if (!$this->app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.view')) {
      return FALSE;
    }

    $backupFile = $this->app->path('#storage:') . '/backups/' . $backup . '.zip';

    if (!is_file($backupFile)) {
      return FALSE;
    }

    $zipHandle = zip_open($backupFile);

    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== TRUE) {
      return FALSE;
    }

    $info = Spyc::YAMLLoad($zip->getFromName('backup.yaml', 0, ZipArchive::FL_NODIR));

    $config = NULL;
    if ($info['backup']['config']) {
      $config = $zip->getFromName('config.yaml', 0, ZipArchive::FL_NODIR);
    }

    $collections = [];
    if ($info['backup']['collections']) {
      $data = Spyc::YAMLLoad($zip->getFromName('collections.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['collections'])) {
        foreach ($data['collections'] as $collection) {
          $collections[$collection['name']] = [
            'name' => $collection['name'],
            'label' => $collection['label'],
            'count' => 0,
          ];
        }
      }
    }

    $regions = [];
    // Regions are deprecated and supported only with legacy module.
    if (isset($info['backup']['regions']) && $info['backup']['regions'] && $this->app->module('regions') instanceof Lime\Module) {
      $data = Spyc::YAMLLoad($zip->getFromName('regions.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['regions'])) {
        foreach ($data['regions'] as $region) {
          $regions[] = !empty($region['label']) ? $region['label'] : $region['name'];
        }
      }
    }

    $singletons = [];
    // Singletons are a replace to regions and therefore not present in old backups.
    if (isset($info['backup']['singletons']) && $info['backup']['singletons']) {
      $data = Spyc::YAMLLoad($zip->getFromName('singletons.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['singletons'])) {
        foreach ($data['singletons'] as $singleton) {
          $singletons[] = !empty($singleton['label']) ? $singleton['label'] : $singleton['name'];
        }
      }
    }

    $forms = [];
    if ($info['backup']['forms']) {
      $data = Spyc::YAMLLoad($zip->getFromName('forms.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['forms'])) {
        foreach ($data['forms'] as $form) {
          $forms[] = !empty($form['label']) ? $form['label'] : $form['name'];
        }
      }
    }

    $accounts = [];
    if ($info['backup']['accounts']) {
      $data = Spyc::YAMLLoad($zip->getFromName('accounts.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['accounts'])) {
        foreach ($data['accounts'] as $account) {
          if (!empty($account['user'])) {
            $accounts[] = $account['user'];
          }
        }
      }
    }

    $webhooks = [];
    if ($info['backup']['webhooks']) {
      $data = Spyc::YAMLLoad($zip->getFromName('webhooks.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['webhooks'])) {
        foreach ($data['webhooks'] as $webhook) {
          $webhooks[] = $webhook['name'];
        }
      }
    }

    if ($info['backup']['entries']) {
      foreach ($collections as $name => $collection) {
        $json = $zip->getFromName('collection.' . $name . '.json', 0, ZipArchive::FL_NODIR);
        $data = json_decode($json, TRUE);
        $collections[$name]['count'] = count($data);
      }
    }

    $assets = [];
    if ($info['backup']['assets']) {
      $data = Spyc::YAMLLoad($zip->getFromName('assets.yaml', 0, ZipArchive::FL_NODIR));
      if ($data && isset($data['assets'])) {
        foreach ($data['assets'] as $asset) {
          $assets[] = [
            'title' => $asset['title'],
            'mime' => $asset['mime'],
          ];
        }
      }
    }

    $uploads = [];
    if ($info['backup']['uploads']) {
      while ($zipEntry = zip_read($zipHandle)) {
        $file = zip_entry_name($zipEntry);
        if (strpos($file, '/data/uploads') === FALSE) {
          continue;
        }
        $uploads[] = [
          'file' => str_replace('/data', '', $file),
          'size' => $this->app->helper("utils")->formatSize(zip_entry_filesize($zipEntry)),
        ];
      }
    }

    $zip->close();

    return $this->render('backupandrestore:views/backups/view.php', [
      'info' => $info['backup'],
      'name' => $backup,
      'filename' => $backup . '.zip',
      'config' => $config,
      'collections' => array_values($collections),
      'regions' => $regions,
      'singletons' => $singletons,
      'forms' => $forms,
      'accounts' => $accounts,
      'webhooks' => $webhooks,
      'entries' => $info['backup']['entries'],
      'assets' => $assets,
      'uploads' => $uploads,
    ]);
  }

  /**
   * Create Backup controller.
   */
  public function create() {
    if (!$this->app->module("cockpit")->hasaccess("BackupAndRestore", 'manage.create')) {
      return FALSE;
    }

    $definitions = $this->definitions;
    if (!$this->app->module('regions') instanceof Lime\Module) {
      unset($definitions['regions']);
    }

    return $this->render('backupandrestore:views/backups/create.php', ['definitions' => array_keys($definitions)]);
  }

  /**
   * Save Backup controller.
   */
  public function save() {
    if (!$this->app->module("cockpit")->hasaccess("BackupAndRestore", 'manage.create')) {
      return FALSE;
    }

    if (!$description = $this->param("description", FALSE)) {
      return FALSE;
    }

    if (!$options = $this->param("options", FALSE)) {
      return FALSE;
    }

    return $this->app->module('backupandrestore')->saveBackup($description, $options);
  }

  /**
   * Restore Backup controller.
   */
  public function restore($backup = NULL) {
    if (!$this->app->module("cockpit")->hasaccess("BackupAndRestore", 'manage.restore')) {
      return FALSE;
    }

    $backupFile = $this->app->path('#storage:') . '/backups/' . $backup . '.zip';

    if (!is_file($backupFile)) {
      return FALSE;
    }

    $zipHandle = zip_open($backupFile);
    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== TRUE) {
      return FALSE;
    }

    $info = Spyc::YAMLLoad($zip->getFromName('backup.yaml', 0, ZipArchive::FL_NODIR));

    // Regions are now depreacted and supported only by legacy addon.
    if (!$this->app->module('regions') instanceof Lime\Module) {
      $info['backup']['regions'] = FALSE;
    }

    return $this->render('backupandrestore:views/backups/restore.php', [
      'info' => $info['backup'],
      'name' => $backup,
      'backup' => $backup . '.zip',
    ]);
  }

  /**
   * Delete Backup controller.
   */
  public function delete($backup) {
    if (!$this->app->module("cockpit")->hasaccess("BackupAndRestore", 'manage.delete')) {
      return FALSE;
    }

    $file = $this->app->path('#storage:') . '/backups/' . trim($backup, '/') . '.zip';

    if (!$backup && !file_exists($file)) {
      $this->app->stop();
    }

    unlink($file);
    return [];
  }

  /**
   * Download Backup controller.
   */
  public function download($backup) {
    if (!$this->app->module("cockpit")->hasaccess("BackupAndRestore", 'manage.view')) {
      $this->app->stop();
    }

    $file = $this->app->path('#storage:') . '/backups/' . trim($backup, '/') . '.zip';

    if (!$backup && !file_exists($file)) {
      $this->app->stop();
    }

    $pathinfo = $path_parts = pathinfo($file);

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", FALSE);
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=\"" . $pathinfo["basename"] . "\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($file));
    ob_clean();
    readfile($file);

    $this->app->stop();
  }

  /**
   * Restore Backup controller.
   *
   * @param string $operation
   *   The operation key to perform the restore.
   */
  public function restoreBackup($operation) {
    if (!$this->app->module("cockpit")->hasaccess("BackupAndRestore", 'manage.restore')) {
      return ['status' => 'danger', 'msg' => 'Invalid access!'];
    }

    if (!$backup = $this->param("backup", FALSE)) {
      return ['status' => 'danger', 'msg' => 'Missing backup!'];
    }

    $fullRestore = $this->param("fullRestore", FALSE);

    $backupFile = $this->app->path('#storage:') . '/backups/' . $backup;

    if (!is_file($backupFile)) {
      return ['status' => 'danger', 'msg' => 'Invalid backup file!'];
    }

    $zipHandle = zip_open($backupFile);
    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== TRUE) {
      return ['status' => 'danger', 'msg' => 'Error opening backup file!'];
    }

    $method = 'restore' . ucfirst($operation);
    if (!method_exists($this, $method)) {
      return ['status' => 'danger', 'msg' => 'Invalid operation!'];
    }

    $this->{$method}($zip, $zipHandle, $fullRestore);
    $zip->close();

    return ['operation' => $operation, 'status' => 'success'];
  }

  /**
   * Restore a set of collections from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreCollections($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('collections.yaml', 0, ZipArchive::FL_NODIR));
    $collections = $this->module('collections')->collections();

    if ($data && isset($data['collections'])) {
      if ($fullRestore) {
        foreach ($collections as $collection) {
          $this->module("collections")->removeCollection($collection['name']);
        }
      }

      foreach ($data['collections'] as $collection) {
        $name = $collection['name'];
        if ($fullRestore || !isset($collections[$name])) {
          $this->module("collections")->createCollection($name, $collection);
          $this->app->storage->insert($collection['_id'], $collection);
        }
        elseif (isset($collections[$name])) {
          $this->module("collections")->updateCollection($name, $collection);
        }
      }
    }
  }

  /**
   * Restore a set of regions from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreRegions($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('regions.yaml', 0, ZipArchive::FL_NODIR));
    $regions = $this->module('regions')->getRegionsInGroup();

    if ($data && isset($data['regions'])) {
      if ($fullRestore && !empty($regions)) {
        foreach ($regions as $region) {
          $this->module('regions')->removeRegion($region['name']);
        }
      }

      foreach ($data['regions'] as $region) {
        $name = $region['name'];
        if ($fullRestore || !isset($regions[$name])) {
          $this->module("regions")->createRegion($name, $region);
        }
        elseif (isset($regions[$name])) {
          $this->module("regions")->updateRegion($name, $region);
        }
      }
    }
  }

  /**
   * Restore a set of singletons from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreSingletons($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('singletons.yaml', 0, ZipArchive::FL_NODIR));
    $singletons = $this->module('singletons')->singletons();

    if ($data && isset($data['singletons'])) {
      if ($fullRestore && !empty($singletons)) {
        foreach ($singletons as $singleton) {
          $this->module('singletons')->removeSingleton($singleton['name']);
        }
      }

      foreach ($data['singletons'] as $singleton) {
        $name = $singleton['name'];
        if ($fullRestore || !isset($singletons[$name])) {
          $this->module("singletons")->createSingleton($name, $singleton);
        }
        elseif (isset($singletons[$name])) {
          $this->module("singletons")->updateSingleton($name, $singleton);
        }
      }
    }
  }

  /**
   * Restore a set of forms from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreForms($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('forms.yaml', 0, ZipArchive::FL_NODIR));
    $forms = $this->module('forms')->getRegionsInGroup();

    if ($data && isset($data['forms'])) {
      if ($fullRestore && !empty($forms)) {
        foreach ($forms as $form) {
          $this->module("forms")->removeForm($form['name']);
        }
      }

      foreach ($data['forms'] as $form) {
        $name = $form['name'];
        if ($fullRestore || !isset($forms[$name])) {
          $this->module("forms")->createForm($name, $form);
        }
        elseif (isset($collections[$name])) {
          $this->module("forms")->updateForm($name, $form);
        }
      }
    }
  }

  /**
   * Restore a set of accounts from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreAccounts($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('accounts.yaml', 0, ZipArchive::FL_NODIR));
    $accounts = $this->storage->find("cockpit/accounts", [])->toArray();
    $current  = $this->user["_id"];

    if ($data && isset($data['accounts'])) {
      if ($fullRestore && !empty($accounts)) {
        foreach ($accounts as $account) {
          // Do not remove active user.
          if ($account['_id'] !== $current) {
            $this->app->storage->remove("cockpit/accounts", ["_id" => $account["_id"]]);
          }
        }
      }

      foreach ($data['accounts'] as $account) {
        if ($account['user'] && $account['_id'] !== $current) {
          if ($fullRestore || !$this->app->storage->findOne("cockpit/accounts", ["_id" => $account['_id']])) {
            $this->app->storage->insert("cockpit/accounts", $account);
          }
          else {
            $this->app->storage->update('cockpit/accounts', ['_id' => $account['_id']], ['data' => $account]);
          }
        }
      }
    }
  }

  /**
   * Restore a set of webhooks from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreWebhooks($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('webhooks.yaml', 0, ZipArchive::FL_NODIR));

    if ($data && isset($data['webhooks'])) {
      if ($fullRestore) {
        $this->app->storage->remove("cockpit/webhooks", []);
      }

      foreach ($data['webhooks'] as $webhook) {
        $this->app->storage->insert("cockpit/webhooks", $webhook);
      }
    }
  }

  /**
   * Restore a set of entries from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreEntries($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('collections.yaml', 0, ZipArchive::FL_NODIR));
    $collections = $this->module('collections')->collections();
    if ($data && isset($data['collections'])) {
      if ($fullRestore) {
        foreach ($collections as $collection) {
          $this->module('collections')->remove($collection['name'], []);
        }
      }

      // Store the relation between old entry id and new entry id to use later.
      $idsMapping = [];
      foreach ($data['collections'] as $collection) {
        $json = $zip->getFromName('collection.' . $collection['name'] . '.json', 0, ZipArchive::FL_NODIR);
        $entries = json_decode($json, TRUE);
        foreach ($entries as $entry) {
          $id = $entry['_id'];
          unset($entry['_id']);
          // Save is causing some php warnings, ignore errors.
          $res = @$this->module('collections')->save($collection['name'], [$entry]);
          $idsMapping[$id] = $res['_id'];
        }
      }

      // Check all imported entries for referencing fields (collection links).
      $collections = $this->module('collections')->collections();
      foreach ($collections as $collection) {
        if (!isset($collection['fields'])) {
          continue;
        }

        $entries = $this->module('collections')->find($collection['name']);

        // Handle collection link fields by updating the referencing ids.
        foreach ($collection['fields'] as $field) {
          switch ($field['type']) {
            case 'multiplecollectionlink':
            case 'collectionlink':
              foreach ($entries as &$entry) {
                if (!isset($entry[$field['name']])) {
                  continue;
                }
                foreach ($entry[$field['name']] as $idx => $value) {
                  if (is_array($value)) {
                    if (isset($value['_id']) && isset($idsMapping[$value['_id']])) {
                      $entry[$field['name']][$idx]['_id'] = $idsMapping[$value['_id']];
                    }
                  } else {
                    if (isset($idsMapping[$value])) {
                      $entry[$field['name']]['_id'] = $idsMapping[$value];
                    }
                  }
                }
              }
              unset($entry);
              break;
          }
        }
        $this->module('collections')->save($collection['name'], $entries);
      }
    }
  }

  /**
   * Restore a set of assets from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreAssets($zip, $zipHandle, $fullRestore) {
    $data = Spyc::YAMLLoad($zip->getFromName('assets.yaml', 0, ZipArchive::FL_NODIR));
    $assets = $this->storage->find("cockpit/assets", [])->toArray();
    if ($data && isset($data['assets'])) {
      if ($fullRestore) {
        foreach ($assets as $asset) {
          $this->app->storage->remove("cockpit/assets", ['_id' => $asset['_id']]);
        }
      }
      $this->app->storage->insert("cockpit/assets", $data['assets']);
    }
  }

  /**
   * Restore a set of uploads from a backup zip file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreUploads($zip, $zipHandle, $fullRestore) {
    $tempFolder = $this->app->path('#tmp:') . '/restore_' . time();
    $uploadsFolder = $this->app->path('#uploads:');

    $this->app->helper('fs')->mkdir($tempFolder);

    while ($zipEntry = zip_read($zipHandle)) {
      $file = zip_entry_name($zipEntry);
      if (strpos($file, '/data/uploads') === FALSE) {
        continue;
      }
      $zip->extractTo($tempFolder, $file);
    }

    // If full restore remove all existing uploads (but not the uploads folder).
    if ($fullRestore) {
      $files = $this->app->helper('fs')->ls($uploadsFolder);
      foreach ($files as $file) {
        if ($file->isDir()) {
          $this->app->helper('fs')->delete($file->getRealPath());
        }
      }
    }

    $this->app->helper('fs')->copy($tempFolder . '/data/uploads', $uploadsFolder);
    $this->app->helper('fs')->delete($tempFolder);
  }

  /**
   * Restore the configuration file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreConfig($zip, $zipHandle, $fullRestore) {
    $config = $zip->getFromName('config.yaml', 0, ZipArchive::FL_NODIR);
    if (!$this->app->path(COCKPIT_CONFIG_PATH)) {
      $this->app->helper('fs')->mkdir(dirname(COCKPIT_CONFIG_PATH));
    }
    $this->app->helper('fs')->write(COCKPIT_CONFIG_PATH, $config);
  }

  /**
   * Restore the configuration file.
   *
   * @param object $zip
   *   The ZipArchive object that contains the backup.
   * @param resource $zipHandle
   *   A Zip Handler.
   * @param bool $fullRestore
   *   Flag to define if its a full or partial restore.
   */
  protected function restoreClearCaches($zip, $zipHandle, $fullRestore) {
    // Clear caches.
    $this->module("cockpit")->clearCache();
  }

}
