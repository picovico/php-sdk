#!/bin/bash

function red() {
	echo -e "\e[31m$@\e[0m"
}
# function red() { echo -e "\e[31m $@\e[0m";  }

function show_help () {
	echo "
	Usage: ./picovico.sh <action> <arg> <arg> ...

	Available actions: 	
	------------------

	 * login USERNAME PASSWORD
	 * authenticate APP_ID APP_SECRET
	 * profile 
	 * set-login-tokens ACCESS_KEY	 ACCESS_SECRET
	 * open PROJECT_ID
	 * begin
	 * upload-image 
	 * upload-music
	 * add-image
	 * add-library-image
	 * add-text
	 * add-music
	 * add-library-music
	 * get-styles
	 * set-style
	 * set-quality
	 * add-credits
	 * remove-credits
	 * set-callback-url
	 * save
	 * preview
	 * create	
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

# invoke the self client for further proceedings
case "${PICOVICO_SDK}" in 
	"php" )
		php "./self-client.php" "${PICOVICO_APP_ID}" "${PICOVICO_APP_SECRET}" "${PICOVICO_DEVICE_ID}" "$@"
		exit
esac

red SDK ${PICOVICO_SDK} is not available

