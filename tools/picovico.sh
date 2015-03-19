#!/bin/bash

function red() {
	echo -e "\e[31m$@\e[0m"
}
# function red() { echo -e "\e[31m $@\e[0m";  }

function show_help () {
	echo "
	Usage: ./picovico.sh <action> <arg> <arg> ...

	## Authentication actions
	
	 * login USERNAME PASSWORD
	 * authenticate APP_ID APP_SECRET
	 * set-login-tokens ACCESS_KEY ACCESS_SECRET
	 * session
	 * logout

	## Account / Profile actions
	 * profile 
	
	## Project actions
	 * open PROJECT_ID
	 * begin TITLE QUALITY
	 * set-quality
	 * set-callback-url
	 * save
	 * preview
	 * create	
	 * reset
	 * project
	 * duplicate
	 * draft

	## Image actions
	 * upload-image 
	 * add-image
	 * add-library-image

	## Music actions
	 * upload-music
	 * add-music
	 * add-library-music

	## Text actions
	 * add-text

	## Style actions
	 * get-styles
	 * set-style

	## Credit actions
	 * add-credits
	 * remove-credits
	"
}

if [ -z "$1" ]; then
	show_help
	exit
fi

env_missing=""
function required_envs() {
	red " --\n" \
		"-- Following environment values are available -- \n" \
		"-- \n" \
 		" * PICOVICO_APP_ID\n" \
		" * PICOVICO_APP_SECRET\n" \
		" * PICOVICO_DEVICE_ID \e[0m[ optional ]" 
	red "  * PICOVICO_SDK\n" 
}

required_env_vars=( "PICOVICO_APP_ID" "PICOVICO_APP_SECRET" "PICOVICO_SDK" )

for v in "${required_env_vars[@]}"; do
	if [ -z "${!v}" ]; then
		red " Undefined : \e[1m${v}\e[0m"
		env_missing=1
	fi
done

if [ "$env_missing" == "1" ]; then
	required_envs
	exit
fi

__dir__=$(dirname $(readlink -f ${0}) )

function spinner() {
	PROC=$1
	count=0
	while [ -d /proc/$PROC ];do
		echo -ne "\r${count}\r" ; sleep 0.1
		(( count++ ))
		if [ $count -gt 9 ]; then
			count=0
		fi
	done
}

# invoke the self client for further proceedings
case "${PICOVICO_SDK}" in 
	"php" )
 		php "${__dir__}/self-client.php" "${PICOVICO_APP_ID}" "${PICOVICO_APP_SECRET}" "${PICOVICO_DEVICE_ID}" "$@" &
		;;
	"*" )
		red SDK ${PICOVICO_SDK} is not available
		exit
esac

PID=$!
spinner $PID
wait $PID
exit
