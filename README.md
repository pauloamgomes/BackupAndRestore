# Cockpit Backup and Restore

This addon extends Cockpit CMS core functionality by providing a granular and flexible backup and restore mechanism.

## Installation

1. Confirm that you have Cockpit CMS (Next branch) installed and working.
2. Download zip and extract to 'your-cockpit-docroot/addons' (e.g. cockpitcms/addons/BackupAndRestore)
3. Access module settings (http://your-cockpit-site/backup-and-restore) and confirm that page loads.

## Configuration

The Backup and Restore operations are available to the super admin user, and they can be used by other users if they belong to a group with proper permissions. The following permissions are defined:

  - manage.create → Can create and upload backups
  - manage.view → Can view and download backups
  - manage.restore → Can restore backups
  - manage.delete → Can delete backups

Above ACLs can be added to the global configuration file as below:

```
groups:
  managers:
    BackupAndRestore:
        manage.create: true
        manage.view: true
        manage.restore: true
        manage.delete: true
  editors:
        manage.view: true
```

## Usage

### Creating a backup (UI)

When a user clicks the Backup button a new page will be open with the backup options. The user can set a description for the backup and define what he wants to be stored along with the backup. The options are the following:

- Global Cockpit configuration: the global config.yaml file settings
- Collection definitions: the structure (Name, Icon, Fields, Rules, etc..) of all existing collections
- Form definitions: the structure (Name, etc..) of all existing forms
- Region definitions: the structure (Name, fields, etc..) of all existing regions
- Webhook definitions: the configurations of all existing webhooks
- User accounts: all existing user accounts
- Collection entries: the contents of all collections
- Assets: all the saved assets
- Uploads: all the files and directories inside the uploads folder


![Backup and Restore between 2 sites](https://monosnap.com/file/Sngzezd9uGNDogT2WXKI7490pik1Xm.png)

### Creating a backup (REST API)

It's possible to create a backup using the REST API, that can be useful for example for doing automatic backups on cron execution. For using the rest API is required to have a configured token (global or that can use the backupandrestore/create rule):

![Cockpit Backup and Restore tokens](https://monosnap.com/file/ShR21HGENGSodKWVKBv3BBQDLM9Kpx.png)

Triggering the backup only requires a request like below:

![Backup using REST API](https://monosnap.com/file/VufnXlEbeIy0cgFgqKJMhJZI0PTkzO.png)

### Restoring a backup

Any valid backup file that is stored in the Cockpit backups folder can be restored using the UI, the process of restoring is similar to creating a backup:

![Restore Example](https://monosnap.com/file/7f8IStPNtei2gXnoRuPJPAvATXEyFz.png)

Backup files can be also uploaded and restored using the UI, just click on upload and select your backup, after uploading the file, it will be available on the backups list.


## Possible issues

The approach taken to handle backups is based on the Cockpit API functions, so further changes on Cockpit may affect existing backup/restore functionality. The decision to handle the backups that way, instead of a simple backup/dump of the database, was to provide more granularity and flexibility, so a backup can consist for example only in user accounts, system config, collections structure or everything. Also, there is no need to deal with particularities of dumping/restoring sqlite or mongodb databases.

## Copyright and license

Copyright 2018 pauloamgomes under the MIT license.


