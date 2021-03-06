# uiowa

The base application on Acquia Cloud for the University of Iowa.

# Getting Started

This project is based on BLT, an open-source project template and tool that enables building, testing, and deploying Drupal installations following Acquia Professional Services best practices. While this is one of many methodologies, it is our recommended methodology.

1. Review the [Required / Recommended Skills](https://docs.acquia.com/blt/developer/skills/) for working with a BLT project.
2. Ensure that your computer meets the minimum installation requirements (and then install the required applications). See the [System Requirements](https://docs.acquia.com/blt/install/#general-requirements).
3. Request access to organization that owns the project repo in GitHub (if needed).
4. Request access to the Acquia Cloud Environment for your project (if needed).
5. Setup a SSH key that can be used for GitHub and the Acquia Cloud (you CAN use the same key).
    1. [Setup GitHub SSH Keys](https://help.github.com/articles/adding-a-new-ssh-key-to-your-github-account/)
    2. [Setup Acquia Cloud SSH Keys](https://docs.acquia.com/acquia-cloud/ssh/generate)
6. Clone the repository. By default, Git names this "origin" on your local.
    ```
    $ git clone git@github.com:uiowa/uiowa
    ```
----
# Local Environment
Follow the [BLT docs](https://docs.acquia.com/blt/install/local-development/) to get started wit [DrupalVM](https://www.drupalvm.com/).

All BLT commands should be run on the VM. You can SSH into the VM using `vagrant ssh`. See the [Vagrant docs](https://www.vagrantup.com/docs/cli/) for basic CLI usage.

## Workspaces
Yarn [workspaces](https://classic.yarnpkg.com/en/docs/workspaces) can be defined in the top-level package.json file. Each workspace can depend on other workspaces as well as define their own build script. You can run workspace build scripts on the VM with `yarn workspace WORKSPACE_NAME run SCRIPT_NAME`. Every workspace build script gets run during continuous integration to build assets. The build assets are committed to the build artifact and deployed.

Workspaces that need to leverage uiowa/uids assets should depend on uids_base and not uiowa/uids directly. This is to ensure the version of uiowa/uids is strictly managed and because uids_base runs a build script that copies necessary assets into the build artifact. For example, fonts are available in uids_base which would not be available in the excluded node_modules directory.

Note that certain filesystem watch commands are either slow or broken over Vagrant synced folders. To get around this, you can run workspace build scripts manually or run watch commands on your host although you'll need Node, [NVM](https://github.com/nvm-sh/nvm#installing-and-updating) and [Yarn](https://classic.yarnpkg.com/en/docs/install#mac-stable). Once installed, you can run the following from the application root:

```
nvm install
nvm use
```

If you're watching SASS and compiling it, you'll need to rebuild node-sass bindings for your host OS.

`npm rebuild node-sass`

This should not result in any changes to yarn.lock. If you try to compile back on the VM, you may need to rebuild it there or run `blt frontend` again to match production. Note that you do not need to use NVM on the VM - it is not installed.

## Databases
Use [SequelPro](https://www.sequelpro.com/) to [connect to DrupalVM](http://docs.drupalvm.com/en/latest/configurations/databases-mysql/#connect-using-sequel-pro-or-a-similar-client).

## Logging
As long as a site has a local settings file, it should be configured to show all warnings and errors to the screen. Other log messages, like watchdog, can be viewed by tailing the syslog: `sudo tail -f /var/log/syslog | grep drupal`. [PimpMyLog](http://docs.drupalvm.com/en/latest/extras/pimpmylog/) is also available at https://pimpmylog.local.drupal.uiowa.edu but requires more configuration to work with syslog.

### BLT Configuration
Make sure you have an [Acquia Cloud key and secret](https://docs.acquia.com/acquia-cloud/develop/api/auth/) saved in the `blt/local.blt.yml` file. This file is ignored by Git. Be sure you do not accidentally commit your credentials to the `blt/blt.yml` file which is tracked in Git. Do not share your key or secret with anyone.
```
uiowa:
  credentials:
    acquia:
      key: foo
      secret: bar
```

Set the multisites that you want BLT to sync by default:
```
multisites:
  - default
  - bar.uiowa.edu
  - foo.uiowa.edu
```

### Common Tasks
The `drupal:sync:all-sites` command will generate settings files _only_ if they do not exist. If you want to re-generate all multisite local settings files, you can run `rm -f docroot/sites/*/settings/local.settings.php` beforehand.

The `blt frontend` command will install and compile frontend assets.

Local configuration overrides can be set in the local.settings.php file for each multisite. For example, to configure stage file proxy:
```
$config['stage_file_proxy.settings']['origin'] = 'https://mysite.com';
$config['stage_file_proxy.settings']['hotlink'] = TRUE;
```

## Multisite Management
### Creating Sites
To add a new site to the project, run the following command:
```
blt umc example.uiowa.edu
```
Replace `example.uiowa.edu` with the URI of the site you are creating.

The following options can also be passed in:
* `--requester=hawkid` - Use the hawkid of the person who requested the site. Will be given webmaster access when installed.
* `--no-db` - Do not create remote databases.
* `--no-commit` - Do not create a new commit in git.
* `--simulate` - Only runs the commands associated with `blt recipes:multisite:init`.

### Overriding Configuration
Please note this approach is not yet tested nor recommended.

If an individual site wants to export ALL of its configuration and manage it going forward, an [include setting](https://docs.acquia.com/blt/install/next-steps/#adding-settings-to-settings-php) with the following should accomplish that:
```
$blt_override_config_directories = FALSE;
$settings['config_sync_directory'] = DRUPAL_ROOT . '/config/' . $site_dir;
```

# Updating Dependencies
Before starting updates, make sure your local environment is on a feature branch created from the latest version of the default branch and synced with production by running `blt dsa`. After updating, certain scaffold files may need to be resolved/removed. For example, the htaccess patch might need to be regenerated if it does not apply to the new `.htaccess` file. BLT may download default config files that we don't use like `docroot/sites/default/default.services.yml`. Different updates may require difference procedures.

Configuration tracked in the repository will need to be exported before deployment. To ensure configuration is exported correctly, manually sync a site from production using Drush. Then run database updates and export any configuration changes. Add and commit the config changes and then run another `blt dsa` to check for any further config discrepancies. If there are none, proceed with code deployment as per usual.

## Testing Dependencies
Testing a uids change in uiowa:
1. Update the hash with the uids commit you wish you test in the uids_base package.json file: "@uiowa/uids": "uiowa/uids#[Enter hash here]"
2. Then run `yarn upgrade @uiowa/uids`
3. `rm -rf ./node_modules`
4. `yarn cache clean`
5. `yarn install`
6. `yarn workspace uids_base gulp --development`

## Core
Follow the `drupal/core-recommended` [instructions](https://github.com/drupal/core-recommended#upgrading) on updating.

## Contrib
You can run `composer update package/name` to update additional dependencies. The output from the Composer commands can be used as the long text for commit messages. Ideally, each package update would be one commit for clarity and easier reverting.

### Locked Packages
The packages below are locked at specific SHAs and will not update using the method described above. They should be periodically checked for new stable releases and updated, if viable.

| Package                               | Reason                   |
| ------------------------------------- | ------------------------ |
| drupal/layout_builder_shortcuts       | No stable release.       |
| drupal/lb_direct_add                  | No stable release.       |
| drupal/redirect                       | Need e5201ca5 from 8.x-1.x branch plus a patch. https://git.drupalcode.org/project/redirect/-/commits/8.x-1.x       |
| kartsims/easysvg                      | Need https://github.com/kartsims/easysvg/pull/27 which is not included in a release. |
| uiowa/block_content_template          | Forked from a deprecated project. |
| dompdf/dompdf                         | https://www.drupal.org/project/entity_print/issues/3169624 |

# Redirects
Redirects can be added to the docroot/.htaccess file. The .htaccess file be will deployed to all applications, regardless of the domain. Therefore, creating per-site redirects using the Redirect module is preferred.

Note that too many .htaccess redirects can incur a performance hit. See the [Acquia redirect documentation](https://docs.acquia.com/acquia-cloud/manage/htaccess/) for more information and examples.

Ideally, redirects in .htaccess would only exist temporarily. Check the commit history of that file using a command similar to: `git log --before="6 months ago" --grep="redirect" -- docroot/.htaccess` to see how old a redirect is.

# Resources
Additional [BLT documentation](https://docs.acquia.com/blt/) may be useful. You may also access a list of BLT commands by running this:
```
$ blt
```

Most of the BLT commands referenced above have shorthand aliases. Check the output of `blt` for details.

You can also run blt commands on an Acquia Cloud environment, but you must run them using the path and from the app root. `./vendor/bin/blt my:blt:command foo`

## Working With a BLT Project

BLT projects are designed to instill software development best practices (including git workflows).

Our BLT Developer documentation includes an [example workflow](https://docs.acquia.com/blt/developer/dev-workflow/#workflow-example-local-development).
