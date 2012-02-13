Currently exposed features: 

    https://care.true.nl
      /api_controllers
        /index (method: GET)
          [/scope:<scope>]

      /basket_items
        /index (method: GET)
          [/scope:<scope>]

      /dns_domains
        /delete (method: DELETE)
          /<id>
        /edit (method: PUT)
          /<id>
        /add (method: PUT)
        /index (method: GET)
          [/scope:<scope>]

      /dns_records
        /edit (method: PUT)
          /<id>
        /add (method: PUT)
        /view (method: GET)
          /<id>
        /index (method: GET)
          [/dns_domain:<dns_domain>]

      /monitoring_services
        /view (method: GET)
          /<id>
        /alerts (method: GET)

      /servers
        /view (method: GET)
          /<id>
        /index (method: GET)
          /<id>
            [/scope:<scope>]
        /store (method: PUT)

      /storage_accounts
        /index (method: GET)
          [/scope:<scope>]
        /delete (method: DELETE)
          /<id>

      /vm_instances
        /view (method: GET)
          /<id>
        /index (method: GET)
          [/scope:<scope>]

