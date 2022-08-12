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
