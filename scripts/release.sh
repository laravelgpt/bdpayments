#!/bin/bash

# Release script for Laravel Payment Gateway Package
# Usage: ./scripts/release.sh [version]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if version is provided
if [ -z "$1" ]; then
    print_error "Please provide a version number (e.g., 1.0.0)"
    exit 1
fi

VERSION=$1
CURRENT_VERSION=$(cat VERSION)

print_status "Current version: $CURRENT_VERSION"
print_status "New version: $VERSION"

# Validate version format
if ! [[ $VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    print_error "Invalid version format. Use semantic versioning (e.g., 1.0.0)"
    exit 1
fi

# Check if git is clean
if [ -n "$(git status --porcelain)" ]; then
    print_error "Working directory is not clean. Please commit or stash changes."
    exit 1
fi

# Check if we're on main branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "main" ]; then
    print_warning "Not on main branch. Current branch: $CURRENT_BRANCH"
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

print_status "Starting release process for version $VERSION..."

# Update VERSION file
echo $VERSION > VERSION
git add VERSION

# Update composer.json version
if [ -f "composer.json" ]; then
    print_status "Updating composer.json version..."
    # This would require jq or sed to update the version in composer.json
    # For now, we'll just note it needs to be done manually
    print_warning "Please update the version in composer.json manually"
fi

# Run tests
print_status "Running tests..."
composer test

# Run static analysis
print_status "Running static analysis..."
composer stan

# Check code style
print_status "Checking code style..."
composer cs

# Commit changes
print_status "Committing version update..."
git commit -m "chore: bump version to $VERSION"

# Create tag
print_status "Creating tag v$VERSION..."
git tag -a "v$VERSION" -m "Release version $VERSION"

# Push changes and tags
print_status "Pushing changes and tags..."
git push origin main
git push origin "v$VERSION"

print_success "Release $VERSION created successfully!"
print_status "Tag: v$VERSION"
print_status "Branch: main"

# Optional: Create GitHub release
read -p "Create GitHub release? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Creating GitHub release..."
    
    # Check if gh CLI is installed
    if command -v gh &> /dev/null; then
        gh release create "v$VERSION" \
            --title "Release $VERSION" \
            --notes-file RELEASE_NOTES.md \
            --latest
        print_success "GitHub release created!"
    else
        print_warning "GitHub CLI (gh) not found. Please create release manually at:"
        print_status "https://github.com/laravelgpt/bdpayments/releases/new"
    fi
fi

print_success "Release process completed!"
print_status "Next steps:"
print_status "1. Update documentation if needed"
print_status "2. Announce the release"
print_status "3. Monitor for any issues"
