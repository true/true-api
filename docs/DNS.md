
### Managing DNS Domains and Records

#### `GET /dns_domains/index`

Retrieves a list of DNS Domains.

Example:
```php
<?php
$domains = $TrueApi->DnsDomains->index();
print_r($domains['data']['dns_domains']);
```

Output:
```
[0] => Array
(
	[id] => 91185
	[name] => example.com
	[master] =>
	[last_check] =>
	[type] => NATIVE
	[notified_serial] =>
	[auto_serial] => 20
	[status] => A
	[template_domain_id] => 0
	[domain_type] => MASTER
)
```


#### `POST /dns_domains/add`

Create a new DNS Domain.

Example:
```php
<?php
$domain = $TrueApi->DnsDomains->add(array(
	'name' => 'example.com',
	'type' => 'NATIVE',
));
print_r($domain['data']['DnsDomain']);
```

Output:
```
Array
(
	[id] => 91185
	[name] => example.com
	[type] => NATIVE
)
```


#### `DELETE /dns_domains/delete/$domainId`

Deletes a DNS Domain.

Example:
```php
<?php
$response = $TrueApi->DnsDomains->delete(91185);
print_r($response);
```


#### `GET /dns_records/index/domain_id:$domainId`

Retrieves a list of DNS Records of a given DNS Domain.

Example:
```php
<?php
$records = $TrueApi->DnsRecords->index(array(
	'domain_id' => 91185,
));
print_r($records['data']['dns_records']);
```

Output:
```
[0] => Array
	(
		[id] => 43661157
		[domain_id] => 91185
		[name] => example.com
		[type] => SOA
		[content] => auth01.dns.trueserver.nl hostmaster@trueserver.nl 2015031904 86400 3600 604800 86400
		[ttl] => 86400
		[prio] =>
		[change_date] => 1426782588
		[hr_ip] =>
		[soa_primary] => auth01.dns.trueserver.nl
		[soa_hostmaster] => hostmaster@trueserver.nl
		[soa_serial] => 2015031904
		[soa_refresh] => 86400
		[soa_retry] => 3600
		[soa_expire] => 604800
		[soa_default_ttl] => 86400
	)

[1] => Array
	(
		[id] => 43661158
		[domain_id] => 91185
		[name] => example.com
		[type] => NS
		[content] => auth01.dns.trueserver.nl
		[ttl] => 86400
		[prio] =>
		[change_date] => 1426782587
		[hr_ip] =>
	)
```


#### `POST /dns_records/add`

Creates a new DNS Record.

Example:
```php
<?php
$record = $TrueApi->DnsRecords->add(array(
	'type'      => 'A',
	'name'      => 'www',
	'ttl'       => 86400,
	'content'   => '123.123.123.123',
	'domain_id' => 91185,
));
print_r($record['data']['DnsRecord']);
```

Output:
```
Array
(
	[type] => A
	[name] => www
	[ttl] => 86400
	[content] => 123.123.123.123
	[domain_id] => 91185
	[id] => 43661163
)
```


#### `PUT /dns_records/edit/$recordId`

Updates a DNS Record.

Example:
```php
<?php
$record = $TrueApi->DnsRecords->edit(43661163, array(
	'content' => '123.123.123.124',
));
print_r($record['data']['DnsRecord']);
```

Output:
```
Array
(
	[content] => 123.123.123.124
	[id] => 43661163
)
```
