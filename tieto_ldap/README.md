# Tieto - LDAP
Provides LDAP integration with Tieto Active Directory.
Relevant tasks:

* EL-9
* EL-113

## Install notes

Composer patches doesn't seem to handle local patches and we can't add absolute paths.
So pre-install step: 
- Copy the contents of the patches folder to `<project root>/patches`
