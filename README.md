<p align="center"><img src="./src/paymongo.png" width="100" height="100" alt="PayMongo for Craft Commerce icon"></p>

<h1 align="center">PayMongo for Craft Commerce</h1>

This plugin provides a [PayMongo](https://paymongo.com/) integration for [Craft Commerce](https://craftcms.com/commerce).

## Requirements

This plugin requires Craft 3.1.5 and Craft Commerce 2.2 or later.


## Installation

You can install this plugin from the Plugin Store or with Composer.


#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require craftcommerce/paymongo

# tell Craft to install the plugin
./craft install/plugin craftcommerce/paymongo
```


## Setup

To add the PayMongo payment gateway, go to Commerce → Settings → Gateways, create a new gateway, and set the gateway type to “PayMongo Payment Intents”.
 

 In order for the gateway to work properly, the following settings are required:
 
* Public API Key
* Secret API Key

