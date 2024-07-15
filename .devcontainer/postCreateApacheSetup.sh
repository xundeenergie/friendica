#!/bin/bash
#
source $workspaceFolder/.devcontainer/devcontainer.env

echo ">>> Apache2 Configuration"
envsubst < $workspaceFolder/.devcontainer/include/001-friendica.conf > /tmp/001-friendica.conf

sudo cp /tmp/001-friendica.conf /etc/apache2/sites-available/001-friendica.conf
sudo a2enmod rewrite actions
sudo a2ensite 001-friendica
sudo a2dissite 000-default

exit 0
