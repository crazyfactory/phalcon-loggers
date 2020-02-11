#!/bin/bash

printf "{\"repositories\": [ { \"type\": \"composer\", \"url\": \"https://php.fury.io/$GEMFURY_TOKEN/crazyfactory/\"} ] }" > ~/.composer/config.json
composer global config -l | grep http
