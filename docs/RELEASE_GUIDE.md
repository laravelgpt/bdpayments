# Release Guide

This guide explains how to create and manage releases for the Laravel Payment Gateway Package.

## 🚀 Creating a Release

### Prerequisites
- Git repository access
- GitHub CLI installed (optional)
- Composer installed
- PHP 8.4+ and Laravel 12+

### Release Process

#### 1. Prepare for Release

```bash
# Ensure you're on the main branch
git checkout main
git pull origin main

# Run all tests
composer test

# Run static analysis
composer stan

# Check code style
composer cs
```

#### 2. Update Version

```bash
# Update VERSION file
echo "1.0.0" > VERSION

# Update composer.json version (if needed)
# The version is already set to 1.0.0 in composer.json
```

#### 3. Create Release

**Option A: Using the release script**
```bash
# Make script executable (Linux/Mac)
chmod +x scripts/release.sh

# Run release script
./scripts/release.sh 1.0.0
```

**Option B: Manual process**
```bash
# Commit version changes
git add VERSION composer.json
git commit -m "chore: bump version to 1.0.0"

# Create and push tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin main
git push origin v1.0.0
```

#### 4. Create GitHub Release

**Using GitHub CLI:**
```bash
gh release create v1.0.0 \
    --title "Release 1.0.0" \
    --notes-file RELEASE_NOTES.md \
    --latest
```

**Using GitHub Web Interface:**
1. Go to [Releases](https://github.com/laravelgpt/bdpayments/releases)
2. Click "Create a new release"
3. Select tag: `v1.0.0`
4. Release title: `Release 1.0.0`
5. Copy content from `RELEASE_NOTES.md`
6. Mark as "Latest release"
7. Click "Publish release"

## 📋 Release Checklist

### Pre-Release
- [ ] All tests passing
- [ ] Static analysis clean
- [ ] Code style compliant
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] RELEASE_NOTES.md prepared
- [ ] Version files updated

### Release
- [ ] Git tag created
- [ ] Tag pushed to remote
- [ ] GitHub release created
- [ ] Release notes published
- [ ] Announcement made

### Post-Release
- [ ] Monitor for issues
- [ ] Update documentation if needed
- [ ] Prepare next version planning

## 🏷️ Version Numbering

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (0.1.0): New features, backward compatible
- **PATCH** (0.0.1): Bug fixes, backward compatible

### Version Examples
- `1.0.0` - Initial stable release
- `1.1.0` - New features added
- `1.1.1` - Bug fixes
- `2.0.0` - Breaking changes

## 📝 Release Notes Template

```markdown
# Release Notes - v1.0.0

## 🎉 What's New
- Feature 1
- Feature 2
- Feature 3

## 🐛 Bug Fixes
- Fixed issue 1
- Fixed issue 2

## 🔧 Improvements
- Improvement 1
- Improvement 2

## 📚 Documentation
- Updated README
- Added examples
- API documentation

## 🧪 Testing
- New test cases
- Improved coverage
- Performance tests

## 🔄 Migration Guide
- Breaking changes
- Upgrade instructions
- Deprecation notices
```

## 🚨 Emergency Releases

For critical security fixes:

1. Create hotfix branch from main
2. Apply fix
3. Test thoroughly
4. Create patch release (e.g., 1.0.1)
5. Merge back to main
6. Update documentation

## 📊 Release Metrics

Track these metrics for each release:

- **Downloads**: Package download count
- **Issues**: New issues reported
- **Pull Requests**: Community contributions
- **Documentation**: Page views and feedback
- **Support**: Support ticket volume

## 🔄 Automated Releases

The package includes GitHub Actions workflows:

- **CI Pipeline**: Runs on every push/PR
- **Release Pipeline**: Runs on tag creation
- **Automated Testing**: PHPUnit, PHPStan, CodeSniffer

### Workflow Files
- `.github/workflows/ci.yml` - Continuous Integration
- `.github/workflows/release.yml` - Release automation

## 📦 Package Distribution

### Composer
```bash
# Install from Packagist
composer require bd-payments/laravel-payment-gateway

# Install specific version
composer require bd-payments/laravel-payment-gateway:^1.0.0
```

### GitHub Packages
```bash
# Configure composer for GitHub packages
composer config repositories.github composer https://github.com/laravelgpt/bdpayments.git

# Install from GitHub
composer require bd-payments/laravel-payment-gateway:dev-main
```

## 🆘 Troubleshooting

### Common Issues

**Tag already exists:**
```bash
git tag -d v1.0.0
git push origin :refs/tags/v1.0.0
```

**Release creation fails:**
- Check GitHub token permissions
- Verify tag exists
- Ensure release notes file exists

**Tests failing:**
- Check PHP version compatibility
- Verify Laravel version
- Check dependencies

### Getting Help

- **GitHub Issues**: Report bugs and request features
- **Discussions**: Community support and questions
- **Email**: support@bdpayments.com
- **Documentation**: Check README.md and docs/

## 📈 Release Analytics

Monitor release success:

- **GitHub Insights**: Repository analytics
- **Packagist Stats**: Download statistics
- **Community Feedback**: Issues and discussions
- **Documentation Usage**: Page views and search queries

## 🔮 Future Releases

### Planned Features
- Additional payment gateways
- Enhanced analytics dashboard
- Mobile app integration
- Advanced reporting
- Multi-currency support
- Subscription billing

### Release Schedule
- **Major releases**: Every 6 months
- **Minor releases**: Every 2 months
- **Patch releases**: As needed
- **Security releases**: Immediately
