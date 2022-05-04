# Fix FKs utility for Craft CMS

This plugin enables users to restore foreign key constraints in their database.

## Requirements

This plugin requires Craft CMS 4.0 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Fix FKs”. Then click on the “Install” button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require craftcms/fix-fks

# tell Craft to install the plugin
./craft install/plugin fix-fks
```

## Usage

Run the restore operation by navigating to Utilities → Fix FKs in the Control Panel.
