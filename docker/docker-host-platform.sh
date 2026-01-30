#!/bin/bash
# Return the platforms supported by a Docker image
# Usage: docker-host-platform.sh
# Get the host architecture
case "$(uname -m)" in
    "x86_64") echo "linux/amd64" ;;
    "aarch64" | "arm64") echo "linux/arm64" ;;
    # "i686" | "i386") HOST_PLATFORM="linux/386" ;;
    # "armv7l") HOST_PLATFORM="linux/arm/v7" ;;
    # "armv6l") HOST_PLATFORM="linux/arm/v6" ;;
    *) echo ""; exit 1 ;; # Unsupported architecture
esac