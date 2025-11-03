# alpdesk-core for Contao

Json based REST-API-Endpoint with JWT-Token Auth for Contao
- Extendable by custom Plugins using Events
- Basic CRUD-Operations for all database tables (Plugin "Contao CRUD") (!!! since v3.0.0)
- Full working File management-API (Finder)

For further information see Postman-Documentation stored in project.

## Additional Info
In order to be able to use the API, a "Mandant" must always be created.
The registered plugins can then be selected for this "Mandant".

After that, a member must be created through which the API is authenticated.
The "Mandant" is then linked to member and the necessary settings and access rights are set.

Add optional to config/config.yml

```yml

# e.g. fort awss3 Storage

alpdesk_core:
  storage:
    awss3:
      key: "KEY"
      secret: "SECRET"
      region: "eu-central-1"
      bucket: "vakanza-dev-local"

```

PHPSTAN
php vendor/bin/phpstan --memory-limit=2G analyse src
