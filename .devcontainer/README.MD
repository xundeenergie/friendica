
This folder holds a devcontainer definition for Friendica.

The main features are:

- The development container is based on the PHP dev container image in variant that includes an Apache2 
  webserver. The variant defines the PHP version and the OS it is based on. The currently used variant 
  is defined in the Dockerfile.

- Creating a dev container from the Git repository should give you a running development environment 
  with no or optionally only a very little things to do after creation.

- A MariaDB container is used for the database. It can be accessed in the dev container's terminal with simple 
  calling mysql. The needed parameters for the client are created and copied during setup. The runtime configuration 
  needs to use 127.0.0.1 instead of localhost as the latter causes PHP to try to use a socket connection which is not 
  available in this setup.


The development setup is:

- After creation of the dev container the Apache2 web server shall be availaible through port forwarding on 
  port 8080 from your local development machine (http://localhost:8080/). This is also the url as configured 
  in local.config.php. You should be able to log in with user 'admin@friendica.local' and password 'admin'.

- Important values are defined in the devcontainer.env file and applied during creation wherever possible. The
  environment is also available during run/debug time to the application.

- XDebug can be started by the launch configuration 'Listen for Xdebug'. The launch configuration is in .vscode/launch.json
  (this file is added to git).


Open points:

- Cron jobs / worker are not available. For a dev environment those are disabled by default (but can be optionally 
  enabled).

- The creation of the container runs the postCreate.sh script. This includes a few setup steps that do not need to 
  be runned on a container rebuild (but on creation of the container). I plan to seperate steps from creation and rebuild 
  but it is not really a problem at the moment (it just gives some output stating Error where no error is).

- Passing values from the local development machine (with $localEnv) does not seem to work. This would be handy to apply
  a few settings differently based on user choice. 

- The dev container does not have an email MTA. 

- The devcontainer does currently not support TLS.

- There are still a bit too much warnings logged at startup but that doesn't seem to be a problem.

- Only the first launch configuration ('Listen for Xdebug') is working.

- There is no port exposed on the container (only forwarded ports used). It would be handy to have the dev instance being able
  to work as a normal instance in the fediverse. 
