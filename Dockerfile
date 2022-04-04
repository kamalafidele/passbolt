wget "https://raw.githubusercontent.com/passbolt/passbolt_docker/master/docker-compose/docker-compose-ce.yaml"
[ "$(sha256sum docker-compose-ce.yaml | awk '{print $1}')" = "4f93b7c4f823df64c70a43b7cd34bfb73ef8641fcfde0491523282bd937df3f7" ] && echo "Checksum OK" || (echo "Bad checksum. Aborting" && rm -f docker-compose-ce.yaml)
docker-compose -f docker-compose-ce.yaml up -d