# Translation Keys Comparison Report

This report shows missing translation keys between Vietnamese (reference) and other languages (Chinese & English).

## Summary

Run the script `check_missing_translations.php` to see detailed comparison:

```bash
php check_missing_translations.php
```

## How to Use This Report

1. **Missing Keys**: Keys that exist in Vietnamese but not in the target language
2. **Extra Keys**: Keys that exist in the target language but not in Vietnamese (may be outdated)

## Quick Fix Guide

### For Chinese (CN) Translations

Add missing keys to the corresponding files in `lang/cn/`:

- `admin.php` - Many missing keys for admin interface
- `affiliate_link.php` - Missing: `cant_referrer_your_self`
- `auth.php` - Missing: `admin.remember`
- `booking.php` - 14 missing keys related to KTV and payment
- `common_error.php` - Missing: `invalid_parameter`
- `error.php` - 17 missing keys for error messages
- `notification.php` - Missing emergency support keys
- `dashboard.php` - Check for danger support table keys

### For English (EN) Translations

Add missing keys to the corresponding files in `lang/en/`:

- `admin.php` - Many missing keys for admin interface
- `affiliate_link.php` - Missing: `cant_referrer_your_self`
- `auth.php` - Missing: `admin.remember`
- `booking.php` - 14 missing keys related to KTV and payment
- `common_error.php` - Missing: `invalid_parameter`
- `dashboard.php` - 36 missing keys for dashboard statistics
- `error.php` - 17 missing keys for error messages
- `notification.php` - Missing emergency support keys

## Automated Checking

The `check_missing_translations.php` script will:

- ✅ Show files with matching keys
- ⚠️ List missing keys that need translation
- 📝 Show extra keys that might be outdated
- 📊 Provide a summary count at the end

## Next Steps

1. Review the output of `check_missing_translations.php`
2. Add missing translations to `lang/cn/` and `lang/en/` files
3. Remove or update extra keys that are no longer used
4. Re-run the script to verify all translations are complete
