# Documentation for user.wifi code

This is the documentation for the user.wifi backend infrastructure.  If you’ve found this from a search online for user.wifi, and would like to use the service [read this] () If you’d like to implement user.wifi at a site where you offer guest wifi [read this] (https://governmenttechnology.blog.gov.uk/2016/06/17/wi-fi-security-and-government-wide-roaming-solutions/).

# Table of contents

<!-- MarkdownTOC -->

- [Elevator pitch - user.wifi](#elevator-pitch---userwifi)
- [Backend architecture](#backend-architecture)
	- [Overview](#overview)
	- [Database](#database)
	- [API tier](#api-tier)
	- [RADIUS tier](#radius-tier)
	- [Commiting, building and releasing](#commiting-building-and-releasing)
	- [Debugging](#debugging)
	- [To-do \(features\)](#to-do-features)
	- [To-do \(build and management\)](#to-do-build-and-management)

<!-- /MarkdownTOC -->

<a name="elevator-pitch---userwifi"></a>
# Elevator pitch - user.wifi 

A secure guest wi-fi service for UK government buildings.

<a name="backend-architecture"></a>
# Backend architecture

User.wifi :
- Onboarding process
 - new guest wi-fi users can sign up by SMS, user.wifi creating and issuing a unique and unchanging user + password and storing these in a database
 - has a similar process for sponsored sign up by email
- accepts RADIUS requests from users attempting to join the user.wifi SSID in government buildings, and checks against the database
- additionally checks if the site has a 'snowflake' rule requiring additional log in requirements to be met, and notifys the user of these by SMS

<a name="overview"></a>
## Overview

<a name="database"></a>
## Database

<a name="api-tier"></a>
## API tier

<a name="radius-tier"></a>
## RADIUS tier

<a name="commiting-building-and-releasing"></a>
## Commiting, building and releasing

<a name="debugging"></a>
## Debugging

<a name="to-do-features"></a>
## To-do (features)

<a name="to-do-build-and-management"></a>
## To-do (build and management)
