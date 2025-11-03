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

## Register and tag custom storage
Storage must implement Alpdesk\AlpdeskCore\Library\Storage\BaseStorageInterface

(see also Alpdesk\AlpdeskCore\Library\Storage\Local\LocalStorage as example)

```yml
alpdeskcore.storage_local:
  class: Alpdesk\AlpdeskCore\Library\Storage\Local\LocalStorage
  arguments:
  tags:
    - { name: 'alpdeskcore.storage', alias: 'local' }
```

After register the storage, it can be used by manipulating the storageAdapter in the alpdesk.filemanagement.request.event

### TESTING
PHPSTAN
php vendor/bin/phpstan --memory-limit=2G analyse src
