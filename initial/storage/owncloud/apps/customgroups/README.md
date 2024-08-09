# ownCloud custom groups support

This apps makes it possible for users to create their own custom groups and manage their members.
It is then possible to share files or folders with these groups.

## QA metrics on master branch:

[![Build Status](https://drone.owncloud.com/api/badges/owncloud/customgroups/status.svg?branch=master)](https://drone.owncloud.com/owncloud/customgroups)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=owncloud_customgroups&metric=alert_status)](https://sonarcloud.io/dashboard?id=owncloud_customgroups)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=owncloud_customgroups&metric=security_rating)](https://sonarcloud.io/dashboard?id=owncloud_customgroups)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=owncloud_customgroups&metric=coverage)](https://sonarcloud.io/dashboard?id=owncloud_customgroups)

## Requirements

* ownCloud 10.0

## Building the app

* Make sure you have [Node JS](https://nodejs.org/) installed
* Run `make` and find the tarball in the "build" directory

## Install

* Extract the resulting tarball in the "apps" folder in ownCloud 

## Usage

* Login as a regular user
* Go to the settings page
* Create custom group and add other users as members
* Share file/folder with said groups

## Developing

* Run `make help` to get information about the different targets.

## Authors:

[Vincent Petry](https://github.com/PVince81/) :: PVince81 at owncloud dot com

