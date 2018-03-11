<?php

/**
 * @file
 * Cockpit module bootstrap implementation.
 */

$this->module("backupandrestore")->extend([
  'saveBackup' => function ($description, $options = []) {
    $toSave = [];
    foreach ($options as $option) {
      $toSave[$option['key']] = $option['value'];
    }

    $settings = [
      'description' => $description,
      'created' => time(),
    ] + $toSave;

    // Create backup.yaml file.
    $backupYaml = \Spyc::YAMLDump(['backup' => $settings], TRUE);

    $uniqId = bin2hex(openssl_random_pseudo_bytes(6)) . '-' . date('YmdHis');

    $zipfile = $this->app->path('#storage:') . 'backups/bak-' . $uniqId . '.zip';
    $zip = new \ZipArchive();

    $zip->open($zipfile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    $zip->addFromString('backup.yaml', $backupYaml);

    $settings['backup'] = trim(str_replace($this->app->path('#root:'), COCKPIT_BASE_URL . '/', $zipfile));

    // Save global config.
    if ($settings['config'] && file_exists(COCKPIT_DIR . '/config/config.yaml')) {
      $zip->addFile(COCKPIT_DIR . '/config/config.yaml', '/data/config.yaml');
    }

    // Save collections.
    if ($settings['collections']) {
      $collections = $this->app->module('collections')->collections();
      $zip->addFromString('/data/collections.yaml', \Spyc::YAMLDump(['collections' => $collections], TRUE));
    }

    // Save regions.
    if ($settings['regions']) {
      $regions = $this->app->module('regions')->regions();
      $zip->addFromString('/data/regions.yaml', \Spyc::YAMLDump(['regions' => $regions], TRUE));
    }

    // Save forms.
    if ($settings['forms']) {
      $forms = $this->app->module('forms')->forms();
      $zip->addFromString('/data/forms.yaml', \Spyc::YAMLDump(['forms' => $forms], TRUE));
    }

    // Save users.
    if ($settings['accounts']) {
      $accounts = $this->app->storage->find("cockpit/accounts", [])->toArray();
      $zip->addFromString('/data/accounts.yaml', \Spyc::YAMLDump(['accounts' => $accounts], TRUE));
    }

    // Save webhooks.
    if ($settings['webhooks']) {
      $webhooks = $this->app->storage->find("cockpit/webhooks", [])->toArray();
      $zip->addFromString('/data/webhooks.yaml', \Spyc::YAMLDump(['webhooks' => $webhooks], TRUE));
    }

    // Save entries.
    if ($settings['entries']) {
      foreach ($collections as $collection) {
        $json = json_encode($this->app->module('collections')->find($collection['name']), JSON_PRETTY_PRINT);
        $zip->addFromString('/data/collection.' . $collection['name'] . '.json', $json);
      }
      foreach ($forms as $form) {
        $json = json_encode($this->app->module('forms')->find($form['name']), JSON_PRETTY_PRINT);
        $zip->addFromString('/data/form.' . $form['name'] . '.json', $json);
      }
    }

    // Save assets.
    if ($settings['assets']) {
      $assets = $this->app->storage->find("cockpit/assets", [])->toArray();
      $zip->addFromString('/data/assets.yaml', \Spyc::YAMLDump(['assets' => $assets], TRUE));
    }

    // Save uploads.
    if ($settings['uploads']) {
      $folder = $this->app->path('#uploads:');
      $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder), \RecursiveIteratorIterator::LEAVES_ONLY);

      foreach ($files as $name => $file) {
        if ($file->isDir() || $file->getFileName() === 'index.html') {
          continue;
        }
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($folder));
        $zip->addFile($filePath, '/data/uploads/' . $relativePath);
      }
    }

    $zip->close();

    return $settings;
  },
]);

// If admin.
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {
  include_once __DIR__ . '/admin.php';
}

// If REST include handlers for remote backup actions.
if (COCKPIT_API_REQUEST) {
  $this->on('cockpit.rest.init', function ($routes) {
      $routes['backupandrestore'] = 'BackupAndRestore\\Controller\\RestApi';
  });
}
