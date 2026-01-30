#!/bin/bash
# Return the platforms supported by a Docker image
# Usage: docker-image-platforms.sh IMAGE_NAME

if [ $# -eq 0 ]; then
    exit 1
fi

IMAGE=$1

# Extract Docker version number dynamically
DOCKER_VERSION=$(docker --version | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+' | head -1)
if [ -z "$DOCKER_VERSION" ]; then
    # ❌ Error: Could not determine Docker version
    exit 1
fi

MANIFEST=$(curl -s -S --unix-socket /var/run/docker.sock "http://v$DOCKER_VERSION/distribution/$IMAGE/json")
if [ -z "$MANIFEST" ]; then
    # ❌ Error: No manifest found for $IMAGE
    exit 1
fi

# The manifest JSON contains a "Platforms" array.
# Extract each object from the array and format as os/architecture/variant
echo "$MANIFEST" | grep -o -E '\{[^}]*"os": *"[^"]*".*?\}' | while read -r PLATFORM; do
    os=$(echo "$PLATFORM" | grep -o -E '"os": *"[^"]*"' | sed -E 's/"os": *"([^"]*)"/\1/')
    arch=$(echo "$PLATFORM" | grep -o -E '"architecture": *"[^"]*"' | sed -E 's/"architecture": *"([^"]*)"/\1/')
    variant=$(echo "$PLATFORM" | grep -o -E '"variant": *"[^"]*"' | sed -E 's/"variant": *"([^"]*)"/\1/')

    # Skip unknown OS or architecture
    if [ "$os" = "unknown" ] || [ "$arch" = "unknown" ]; then
        continue
    fi

    # # Format as os/architecture/variant or os/architecture
    # if [ -n "$variant" ]; then
    #     echo "$os/$arch/$variant"
    # else
        echo "$os/$arch"
    # fi
done