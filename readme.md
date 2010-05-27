Setup (ubuntu)

    aptitude install git-core php5-cli php5-curl
    
    touch /var/log/true-api.log
    chmod 777 /var/log/true-api.log # Or: at least write permissions for the user running your script
    
    mkdir -p /var/git 
    cd /var/git/  
    git clone git://github.com/true/true-api.git 
    cd true-api/  
    git submodule update --init

You now have a working copy of our PHP API Client in `/var/git/true-api`

Howto:

    $this->TrueApi->auth(
        '1823',
        'Pjsadrfj*1',
        'e89e1e521d0cedc6b96232fd2741addfbe6e69ddf235cedad140b986c200ead8'
    );