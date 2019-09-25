# [Looker](https://looker.com) Writer

[![Build Status](https://travis-ci.com/keboola/looker-writer.svg?branch=master)](https://travis-ci.com/keboola/looker-writer)

> Allows user to load their data from Keboola Connection to Snowflake to use in Looker. 

# Usage

> TBA

## Development
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/looker-writer
cd looker-writer
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

Following enviroment variables must be set:
```dotenv
# id of config in KBC (used id DB name)
KBC_CONFIGID=
```


```shell script
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 
