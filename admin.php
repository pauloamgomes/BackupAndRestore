<?php

/**
 * @file
 * Cockpit backup and restore admin functions.
 */

// Module ACL definitions.
$this("acl")->addResource('BackupAndRestore', [
  'manage.view',
  'manage.create',
  'manage.restore',
  'manage.delete',
]);

$app->on('admin.init', function () use ($app) {

  // Bind admin routes /backup-and-restore.
  $this->bindClass('BackupAndRestore\\Controller\\Admin', 'backup-and-restore');

  if ($app->module('cockpit')->hasaccess('BackupAndRestore', 'manage.view')) {
    // Add to modules menu.
    $this('admin')->addMenuItem('modules', [
      'label' => 'Backup And Restore',
      'icon'  => 'assets:app/media/icons/database.svg',
      'route' => '/backup-and-restore',
      'active' => strpos($this['route'], '/backup-and-restore') === 0,
    ]);
  }

});
