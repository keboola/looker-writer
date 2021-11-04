#!/bin/bash
set -e

for config in ${KBC_TEST_PROJECT_CONFIGS}
do
  export CONFIG="{\"component\": \"${KBC_DEVELOPERPORTAL_APP}\",\"mode\": \"run\",\"config\": \"${config}\",\"tag\": \"${TAG}\"}"

  jobid=$(curl -s -X POST -H "X-StorageApi-Token: ${KBC_STORAGE_TOKEN}" -H "Content-Type: application/json" -d "${CONFIG}" https://queue.keboola.com/jobs | jq -r '.id')
  echo "Job '$jobid' started"
  jobfinished=false
  while ! $jobfinished; do
    sleep 5
    curl -s -X GET -H "X-StorageApi-Token: ${KBC_STORAGE_TOKEN}" -H "Content-Type: application/json" https://queue.keboola.com/jobs/$jobid > jobResult.json
    jobfinished=$(jq -r '.isFinished' jobResult.json)
    status=$(jq -r '.status' jobResult.json)

    if [[ "$jobfinished" == "true" && "$status" != "success" ]]
    then
      echo "Job '$jobid' ended with status '$status'"
      exit 1
    fi
    if [ "$jobfinished" == "true" ]
    then
      echo "Job '$jobid' has been successful"
    fi
  done
done

echo "OK"
