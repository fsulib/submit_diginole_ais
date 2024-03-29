#/usr/bin/env bash

echo "$(date): submit2ais_crontrigger.sh activated"
echo "Processing submissions from the $1 webform"

source /etc/environment
if [[ "$ENVIRONMENT" == "prod" ]]
then
  BUCKET_ENV='prod'
else
  BUCKET_ENV='test'
fi

/var/sites/submit_diginole/vendor/bin/drush ais_process --uri=https://$SUBMIT_DIGINOLE_DOMAIN $1

cd /tmp/ais_packages/
for PACKAGE in $(ls *)
do
  aws s3 cp $PACKAGE s3://ingest-$BUCKET_ENV.lib.fsu.edu/diginole/ais/new/$PACKAGE
done

echo "$(date): submit2ais_crontrigger.sh complete."
