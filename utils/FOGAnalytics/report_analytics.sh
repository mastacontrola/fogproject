#!/bin/bash

# Get the OS Name.
os_name=$(lsb_release -i | cut -d':' -f2 | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')
echo "os_name=${os_name}"


# Get the OS version.
os_version=$(lsb_release -r | cut -d':' -f2 | sed -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//')
echo "os_version=${os_version}"


# Get the FOG version.
fog_version=$(cat /var/www/fog/lib/fog/system.class.php | grep FOG_VERSION | cut -d',' -f2 | cut -d"'" -f2)
echo "fog_version=${fog_version}"


# Format payload.
payload="{\"fog_version\":\"${fog_version}\",\"os_name\":\"${os_name}\",\"os_version\":\"${os_version}\"}"
echo "payload=${payload}"


# Send to reporting endpoint.
curl -s -X POST -H "Content-Type: application/json"  -d "${payload}" https://fog-analytics-entries.theworkmans.us:/api/records


