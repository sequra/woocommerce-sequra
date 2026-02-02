#!/bin/bash
# Build the Docker image

# Colors for the output
RED=$(tput setaf 1)
YELLOW=$(tput setaf 3)
NC=$(tput sgr0) # No color

PUSH=0
WP_TAG=""
BASEDIR="$(dirname $(realpath $0))"
ENV_FILE="$BASEDIR/../.env"

# Check if .env file exists
if [ ! -f "$ENV_FILE" ]; then
    echo "${RED}.env file not found. Please create a .env file using .env.sample as a template and set the required environment variables${NC}"
    exit 1
fi

set -o allexport
source $ENV_FILE
set +o allexport

# Parse arguments:
# --push: Push the image to the registry
# --wp=VERSION: WordPress version
while [[ "$#" -gt 0 ]]; do
    if [ "$1" == "--push" ]; then
        PUSH=1
    elif [[ "$1" == --wp=* ]]; then
        WP_TAG="${1#*=}"
    fi
    shift
done

if [ -z "$WP_TAG" ]; then
    echo "${RED}Please set the WordPress version using either --wp=VERSION or defining WP_TAG in your .env file${NC}"
    exit 1
fi

build_args="--build-arg WP_TAG=$WP_TAG"

# Build the Docker image
echo "Building Docker image for WordPress $WP_TAG..."

PLATFORMS_ARG=$($BASEDIR/docker-image-platforms.sh "wordpress:${WP_TAG}" | grep -E "^linux/(amd64|arm64)$" | awk '{printf "%s%s", sep, $0; sep=","} END {print ""}')

if [ $PUSH -eq 1 ]; then
    echo "Login to the GitHub Container Registry..."
    if [ -z "$GITHUB_TOKEN" ]; then
        echo "${RED}Please, set an environment variable named GITHUB_TOKEN with your GitHub token. You can define it in your .env file${NC}"
        exit 1
    fi

    echo "$GITHUB_TOKEN" | docker login ghcr.io -u sequra --password-stdin || (echo "${RED}Login failed${NC}" && exit 1)
    echo "The resulting image will be pushed to the GitHub Container Registry"

    if [ -z "$PLATFORMS_ARG" ]; then
        echo "${RED}The base image does not support the required platforms (linux/amd64, linux/arm64)${NC}"
        exit 1
    fi

    build_args+=" --platform $PLATFORMS_ARG --push"
else
    HOST_PLATFORM=$($BASEDIR/docker-host-platform.sh)
    if [ -z "$HOST_PLATFORM" ]; then
        echo "${RED}Could not determine the host platform or it's not supported${NC}"
        exit 1
    fi
    # check if the host platform is supported
    if [[ $PLATFORMS_ARG != *"$HOST_PLATFORM"* ]]; then
        echo "${YELLOW}The base image does not support the host platform ($HOST_PLATFORM)${NC}"

        # Use the first supported platform
        FIRST_PLATFORM=$(echo $PLATFORMS_ARG | cut -d, -f1)
        echo "${YELLOW}Using the first supported platform: $FIRST_PLATFORM${NC}"
        build_args+=" --platform $FIRST_PLATFORM"
    fi
    build_args+=" --load"
fi

build_args+=" --tag ghcr.io/sequra/woocommerce-sequra:$WP_TAG"

DOCKERFILE="Dockerfile"

build_args+=" -f $BASEDIR/$DOCKERFILE $BASEDIR"

BUILDER_NAME="sequra-builder"
EXISTING_BUILDER=$(docker buildx ls --format '{{.Name}}' | grep -w "$BUILDER_NAME")

export DOCKER_BUILDKIT=1
if [ -z "$EXISTING_BUILDER" ]; then
  docker buildx create --name "$BUILDER_NAME" --use || (echo "${RED}Builder creation failed${NC}" && exit 1)
  docker buildx inspect "$BUILDER_NAME" --bootstrap || (echo "${RED}Builder bootstrap failed${NC}" && exit 1)
else
  # Check if the builder is already in use
  ACTIVE_BUILDER=$(docker buildx ls | grep -w "$BUILDER_NAME" | awk '/\*/ {print $1}')

  if [ "$ACTIVE_BUILDER" != "*" ]; then
    # Use the builder if it's not the active one
    docker buildx use "$BUILDER_NAME" || (echo "${RED}Builder use failed${NC}" && exit 1)
  fi
fi

docker buildx build $build_args