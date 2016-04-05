## Setup (ubuntu)

```sh
apt-get install git-core php5-cli php5-curl

touch /var/log/true-api.log
chmod 666 /var/log/true-api.log # Or: at least write permissions for the user running your script

mkdir -p /var/git 
cd /var/git/  
git clone git://github.com/true/true-api.git 
cd true-api/  
git submodule update --init
```

### Update

```sh
cd /var/git/true-api
git pull
git submodule update
```

You now have a working copy of our PHP API Client in `/var/git/true-api`
Let's look at an example how to include & use the Client

## Code sample:

```php
<?php
// Import class
use \TrueApi;

// Instantiate
// (more configurable options below)
$TrueApi = new TrueApi(array(
	'log-file' => __DIR__ . '/api.log',
));

// In real life: get credentials from some place safe,
// but for the sake of example let's store them here:
$account  = '1231';
$password = 'Pjsadrfj*1';
$apikey   = 'e89e1e521d0cedc6b96232fd2741addfbe6e69ddf235cedad140b986c200ead8';

// Store credentials & Initialize all available API controllers
$TrueApi->auth($account, $password, $apikey);

// Now we can address API controllers directly as client objects, e.g. Servers:
// Return all servers
$servers = $TrueApi->Servers->index();
print_r($servers);

// Edit server with id: 1337
$success = $TrueApi->Servers->edit(1337, array(
	'hostname' => 'updated-hostname.example.com',
));

// Add server with hostname: www.example.com
$success = $TrueApi->Servers->add(array(
	'hostname' => 'www.example.com',
));
```

## More Configurable options

Other configurable [options](https://github.com/true/true-api/blob/master/TrueApi.php#L45):

```php
<?php
$_options = array(
	'verifySSL' => true,
	'returnData' => false,
	'fetchControllers' => true,
	'checkVersion' => true,

	'log-date-format' => 'Y-m-d H:i:s',
	'log-file' => '/var/log/true-api.log',
	'log-break-level' => 'crit',
);
```
