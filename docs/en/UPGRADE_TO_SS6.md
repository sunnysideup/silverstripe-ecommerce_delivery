# Upgrade to Silverstripe CMS 6 Guide

This document outlines the necessary steps and breaking changes required to upgrade this module to be compatible with Silverstripe CMS 6.

## 🚨 CRITICAL REVIEW REQUIRED / RISKY

**You must manually review the dependency `sunnysideup/dataobjectsorter`. It has been removed from `composer.json` because no compatible version for Silverstripe 6 could be found. This will likely cause functionality that relies on sorting data objects to fail. You will need to find a replacement or update this module once a compatible version is available.**

## ⚠️ BREAKING CHANGES

### New Requirements

Your `composer.json` file must be updated to require Silverstripe CMS 6 and the latest version of `sunnysideup/ecommerce`.

-   **`silverstripe/recipe-cms`**: Update constraint to `^6.0`
-   **`sunnysideup/ecommerce`**: Update constraint to `^33.0`

### Configuration

-   **Database Admin Class Renamed**: The deprecated `SilverStripe\ORM\DatabaseAdmin` class has been removed. In your `.yml` configuration files, replace any references to it with `SilverStripe\Dev\DbBuild`.

    ```yaml
    # Before
    SilverStripe\ORM\DatabaseAdmin:
      # ...

    # After
    SilverStripe\Dev\DbBuild:
      # ...
    ```

### API Changes

-   **BuildTask Signature**: `BuildTask::run()` has been replaced by `BuildTask::execute()`. Update your custom build tasks to use the new method signature.

    ```php
    // Before
    public function run($request)
    {
        // ...
    }

    // After
    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        // ...
        return Command::SUCCESS;
    }
    ```

-   **PHP 8 Attributes**: PHP 8 `#[Override]` attribute has been added to many methods. This improves code clarity but does not change functionality.

-   **Type Hinting**: The return type for `BuildTask::$title` and `BuildTask::$description` has been changed to `string`.

    ```php
    // Before
    protected $title = 'My Task Title';

    // After
    protected string $title = 'My Task Title';
    ```
