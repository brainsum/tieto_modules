#!/usr/bin/env bash

parallel-lint . --exclude vendor

phpcs --standard=phpcs.xml .
