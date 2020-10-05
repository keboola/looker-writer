# [Looker](https://looker.com) Writer

[![Build Status](https://travis-ci.com/keboola/looker-writer.svg?branch=master)](https://travis-ci.com/keboola/looker-writer)

> Allows user to load their data from Keboola Connection to Snowflake to use in Looker. 

# Usage

## Keboola

* Set up Snowflake schema to be used for the data
* Get [Looker API credentials](https://docs.looker.com/reference/api-and-integration/api-auth#authentication_with_a_sdk)
* describe the tables to load from `/data/in/tables`

You need to have API access to Keboola Storage API to run the snowflake writer job.

## Looker

* Navigate to `https://[yourcompany].looker.com/projects`
* Click `New LookML project` button
    * Select `Generate Model from Database Schema` for `Starting Point`  
    * Fill the form and select the proper connection that was created in Keboola - see writer log for details
    * Select `All Tables` for `Build Views From`
    * Click `Create project`
* project is generated for you with all the relations already set up

## Example config

### Example Snowflake config

```json
{
    "parameters": {
      "db": {
        "driver": "snowflake",
        "database": "--fill in--",
        "host": "keboola.snowflakecomputing.com",
        "password": "--fill in--",
        "port": "443",
        "schema": "--fill in--",
        "user": "--fill in--",
        "warehouse": "--fill in--"
      },
      "looker": {
        "#token": "--fill in--",
        "credentialsId": "--fill in--",
        "host": "https://[yourcompany].api.looker.com/api/3.1"
      },
      "tables": [
        {
          "dbName": "CUSTOMERS",
          "export": true,
          "items": [
            {
              "dbName": "CUSTOMERS_ID",
              "default": "",
              "name": "id",
              "nullable": false,
              "size": "255",
              "type": "varchar"
            },
            {"...": "..."}
          ],
          "primaryKey": [
            "CUSTOMERS_ID"
          ],
          "tableId": "in.c-erp.customers"
        },
        {
          "dbName": "EMPLOYEES",
          "export": true,
          "items": [
            {"...": "..."}

          ],
          "primaryKey": [],
          "tableId": "in.c-erp.employees"
        },
        {"...": "..."}
    ]
  }
}
```

### Example BigQuery config

Key `parameters.db.service_account` contains the contents of a BigQuery JSON certificate. 


It can be created in https://console.developers.google.com -> `Credentials` -> `Service Accounts` -> `Keys`

```json
{
    "parameters": {
      "db": {
        "driver": "bigquery",
        "service_account": {
           "type": "service_account",
           "project_id": "looker-writer-bigquery",
           "private_key_id": "...",
           "private_key": "-----BEGIN PRIVATE KEY-----\n....\n-----END PRIVATE KEY-----\n",
           "client_email": "looker-writer-bigquery-test@looker-writer-bigquery.iam.gserviceaccount.com",
           "client_id": "103729776760399992476",
           "auth_uri": "https://accounts.google.com/o/oauth2/auth",
           "token_uri": "https://oauth2.googleapis.com/token",
           "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
           "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/looker-writer-bigquery-test%40looker-writer-bigquery.iam.gserviceaccount.com"
         }
      },
      "looker": {
        "#token": "--fill in--",
        "credentialsId": "--fill in--",
        "host": "https://[yourcompany].api.looker.com/api/3.1"
      },
      "tables": ["..."]
  }
}
```

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/looker-writer
cd looker-writer
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Create a project with Looker Writer config and Keboola Snowflake backend.


Following enviroment variables must be set:
```dotenv
KBC_URL=https://connection.keboola.com/
KBC_TOKEN=
SNOWFLAKE_BACKEND_CONFIG_ID=
SNOWFLAKE_BACKEND_DB_PASSWORD=
```

Run the test suite using this command:
```shell script
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
