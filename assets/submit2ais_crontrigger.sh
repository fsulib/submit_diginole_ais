#/usr/bin/env bash

if [ $(whoami) != "root" ];
then 
  echo "Error: This script needs to be run as root."
  exit 1
fi

if [ -z "$1" ];
then 
  echo "Error: No webform ID provided."
  exit 1
fi

if [ "$1" != 'honors_thesis_submission' ] && [ "$1" != 'research_repository_submission' ] && [ "$1" != 'university_records_submission' ]
then
  echo "Error: $1 is not a valid webform ID."
  echo "Select from the following webform IDs:"
  echo "'honors_thesis_submission'"
  echo "'research_repository_submission'"
  echo "'university_records_submission'"
  exit 1
fi

source /etc/environment

echo "$(date): submit2ais_crontrigger.sh activated."

/var/sites/submit_diginole/vendor/bin/drush ais_process $1

cd /tmp/ais_packages/
for PACKAGE in $(ls *)
do
  aws s3 cp $PACKAGE s3://ingest-dev.lib.fsu.edu/diginole/ais/new/$PACKAGE
done

echo "$(date): submit2ais_crontrigger.sh complete."
