# Cese Submit Proposal

## How to use

* Clone this repository
* Change user ownership with `sudo chown -R www-data:www-data .` or `sudo chown -R phalo:phalo .`
* Open in container
* Install git and ant in container with `apt update` and `apt install -y git ant`
* Install Cese Submit Proposal

## How to publish
* From within container.
* change accordingly version in file build.xml
* change all 
* run `ant build`
* Commit and push
* From github create a new release and make sure to attach the created zip installation file.
