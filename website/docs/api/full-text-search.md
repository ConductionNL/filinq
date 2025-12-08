# Full Text Search
Docudesk provides the ability to perform full text searching in files with the help of Apache Solr.
The search results will be included in the search results provided by Nextcloud General Search when searching with the search bar.

## Prerequisites
To run full text search with DocuDesk an Apache Solr instance in SolrCloud setup is needed. 

For testing purposes there is a `docker-compose.yml` included in the folder `website/static/solr`. To run this, go to this folder with your terminal and run `docker-compose up`.

## Setup
### Solr
Start solr, and browse to the Solr UI in your browser (typically on your local machine this will be http://localhost:8983/solr) and create a collection in the tab 'collections'. In this collection add the following fields to the schema (select your collection in the collection selector, go to schema and click 'add field'):

- fileName (type string)
- nodeId (type pint)
- tags (type strings)
- text (type text_general)

Especially the type of the text field is important, as this field would not be indexed or tokenized properly when using the string type.

### DocuDesk
When the above is done, go to the app settings of docudesk and fill out the fields 'Solr API url' and 'collection'. At Solr API Url you fill out the base url of your solr instance, for example, with the docker compose mentioned above, this would be http://host.docker.internal:8983. In the field 'collection' you fill out the name of the collection you made in the previous step.

Please note that reporting must be enabled to write files to Solr, and the search functionality will not work without it.


