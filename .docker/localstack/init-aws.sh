#!/bin/bash

DYNAMODB_TABLE_CREATION_RETURN=$(awslocal dynamodb create-table --table-name $DYNAMODB_TABLE_NAME --no-sign-request --endpoint-url=$DYNAMODB_LOCAL_ENDPOINT --attribute-definitions $DYNAMODB_TABLE_ATTRIBUTE_DEFINITIONS --key-schema $DYNAMODB_TABLE_KEY_SCHEMA --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=5 2>&1 >/dev/null)
DYNAMODB_TABLE_CREATION_RETURN=$(echo $DYNAMODB_TABLE_CREATION_RETURN | xargs)

if [ "$DYNAMODB_TABLE_CREATION_RETURN" == "" ] ; then
  echo "DynamoDB table '$DYNAMODB_TABLE_NAME' created successfully."
else
  echo "Error: $DYNAMODB_TABLE_CREATION_RETURN"
  echo "Skipping DynamoDB table creation."
fi

DYNAMODB_TABLE_INDEX_CREATION_RETURN=$(awslocal dynamodb create-table --table-name $DYNAMODB_TABLE_INDEX_NAME --no-sign-request --endpoint-url=$DYNAMODB_LOCAL_ENDPOINT --attribute-definitions $DYNAMODB_TABLE_INDEX_ATTRIBUTE_DEFINITIONS --key-schema $DYNAMODB_TABLE_INDEX_KEY_SCHEMA --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=5 --local-secondary-indexes "$DYNAMODB_TABLE_INDEX_LSI_DEFINITIONS" --global-secondary-indexes "$DYNAMODB_TABLE_INDEX_GSI_DEFINITIONS" 2>&1 >/dev/null)
DYNAMODB_TABLE_INDEX_CREATION_RETURN=$(echo $DYNAMODB_TABLE_INDEX_CREATION_RETURN | xargs)

if [ "$DYNAMODB_TABLE_INDEX_CREATION_RETURN" == "" ] ; then
  echo "DynamoDB table '$DYNAMODB_TABLE_INDEX_NAME' created successfully."
else
  echo "Error: $DYNAMODB_TABLE_INDEX_CREATION_RETURN"
  echo "Skipping DynamoDB table creation."
fi
