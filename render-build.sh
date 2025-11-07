#!/usr/bin/env bash
set -e

echo "🔧 Updating package list..."
apt-get update -y

echo "📦 Installing PHP..."
apt-get install -y php php-cli php-mysqli php-common php-json php-opcache php-readline

echo "✅ PHP installed successfully!"
php -v
