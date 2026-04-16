#!/usr/bin/env bash

source /etc/yiimpool.conf
source $STORAGE_ROOT/yiimp/.yiimp.conf

cd "$STORAGE_ROOT/yiimp/site/stratum" || exit 1

exec ./stratum config/$1.conf