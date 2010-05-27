## Setup (ubuntu)

    aptitude install git-core php5-cli php5-curl
    
    touch /var/log/true-api.log
    chmod 777 /var/log/true-api.log # Or: at least write permissions for the user running your script
    
    mkdir -p /var/git 
    cd /var/git/  
    git clone git://github.com/true/true-api.git 
    cd true-api/  
    git submodule update --init

You now have a working copy of our PHP API Client in `/var/git/true-api`
Let's look at an example how to include & use the Client

## Code sample:

    <?php
    // Include
    require_once '/var/git/true-api/TrueApi.php';

    // In real life: get credentials from some place safe,
    // but for the sake of example let's store them here:
    $account  = '1231';
    $password = 'Pjsadrfj*1';
    $apikey   = 'e89e1e521d0cedc6b96232fd2741addfbe6e69ddf235cedad140b986c200ead8';

    // Checks credentials & Initializes all available API objects
    $TrueApi->auth($account, $password, $apikey);

    // Now we can call API objects directly such as Servers:
    $servers = $TrueApi->Servers->index();
    print_r($servers);
    ?>

