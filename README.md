# Moodle Branch For The Well

This is the Moodle branch to be deployed for The Well, using Connectbox [https://github.com/ConnectBox](https://github.com/ConnectBox) or 
Raspberry Pi but can also run on AWS, Azure or a server.

# Key Modifications  
* Addition of APIs outside of Moodle for Custom Application Branding
* Addition of files (images typically) outside of Moodle for Custom Application Branding
* outputrenderers.php: If present, return partnerlogo.png or relaytrust.png as logo
  * See logo information below

(See more details below)

# Moodle User Accounts
* A newly set up Well Connectbox will have the following two user accounts set up (both have the password !1TheWell):
  * admin
  * user
* The default passwords should be changed for production use.

# APIs: See embedded API documentation in each API folder: [wellapi](wellapi)

* /wellapi/site returns sitename, shortsitename, logos (Relay Trust and Partner) in JSON format  
  * See logo information below

# File Storage Outside of Moddle

* /wellfiles holds files that can be accessed outside of typical Moodle image model.  Assists with remote branding in batch.
* Key Files:
  * relaytrust.png: logo for Relay Trust, sponsor of The Well
  * partnerlogo.png: may be included and will overwrite the Relay Trust logo if present
