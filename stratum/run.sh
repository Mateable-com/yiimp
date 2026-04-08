#!/bin/bash

# YiiMP Stratum Run Wrapper
# Appends .conf to the algo name to match the config files

cd /var/stratum/config/ && ./run.sh "$1.conf"

