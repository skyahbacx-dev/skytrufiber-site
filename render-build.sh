#!/usr/bin/env bash
set -e

# Update package list
apt-get update

# Install PHP and necessary extensions
apt-get install -y php php-cli php-mysqli php-common php-json php-opcache php-readline
