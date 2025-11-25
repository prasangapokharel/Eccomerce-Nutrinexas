# Cron Jobs Setup Guide

This directory contains all cron job scripts for the NutriNexus platform.

## Available Cron Jobs

### 1. Process Seller Balance Releases
**File:** `process_seller_balance_releases.php`  
**Purpose:** Automatically releases seller balance for orders that have passed the 24-hour wait period after delivery.  
**Frequency:** Every hour  
**Command:**
```bash
0 * * * * cd /path/to/Nutrinexus && php cron/process_seller_balance_releases.php >> logs/cron_balance_releases.log 2>&1
```

### 2. Process Abandoned Carts
**File:** `process_abandoned_carts.php`  
**Purpose:** Sends recovery emails for abandoned carts.  
**Frequency:** Every hour  
**Command:**
```bash
0 * * * * cd /path/to/Nutrinexus && php cron/process_abandoned_carts.php >> logs/cron_abandoned_carts.log 2>&1
```

### 3. Send Winback Emails
**File:** `send_winback_emails.php`  
**Purpose:** Sends winback emails to inactive customers.  
**Frequency:** Daily at 9 AM  
**Command:**
```bash
0 9 * * * cd /path/to/Nutrinexus && php cron/send_winback_emails.php >> logs/cron_winback.log 2>&1
```

### 4. Update Sale Statuses
**File:** `update_sale_statuses.php`  
**Purpose:** Updates product sale statuses based on dates.  
**Frequency:** Daily at midnight  
**Command:**
```bash
0 0 * * * cd /path/to/Nutrinexus && php cron/update_sale_statuses.php >> logs/cron_sale_statuses.log 2>&1
```

## Setup Instructions

### For Linux/Unix (crontab)

1. Open crontab editor:
```bash
crontab -e
```

2. Add all cron jobs:
```bash
# Seller Balance Releases (every hour)
0 * * * * cd /path/to/Nutrinexus && php cron/process_seller_balance_releases.php >> logs/cron_balance_releases.log 2>&1

# Abandoned Carts (every hour)
0 * * * * cd /path/to/Nutrinexus && php cron/process_abandoned_carts.php >> logs/cron_abandoned_carts.log 2>&1

# Winback Emails (daily at 9 AM)
0 9 * * * cd /path/to/Nutrinexus && php cron/send_winback_emails.php >> logs/cron_winback.log 2>&1

# Update Sale Statuses (daily at midnight)
0 0 * * * cd /path/to/Nutrinexus && php cron/update_sale_statuses.php >> logs/cron_sale_statuses.log 2>&1
```

3. Replace `/path/to/Nutrinexus` with your actual project path.

4. Save and exit.

### For Windows (Task Scheduler)

1. Open Task Scheduler
2. Create Basic Task
3. Set trigger (hourly/daily)
4. Action: Start a program
5. Program: `php.exe`
6. Arguments: `C:\path\to\Nutrinexus\cron\process_seller_balance_releases.php`
7. Start in: `C:\path\to\Nutrinexus`

### Manual Testing

You can run any cron job manually for testing:

```bash
php cron/process_seller_balance_releases.php
php cron/process_abandoned_carts.php
php cron/send_winback_emails.php
php cron/update_sale_statuses.php
```

## Logs

All cron job logs are stored in the `logs/` directory:
- `logs/cron_balance_releases.log`
- `logs/cron_abandoned_carts.log`
- `logs/cron_winback.log`
- `logs/cron_sale_statuses.log`

## Notes

- Ensure PHP CLI is in your system PATH
- Make sure file permissions allow execution
- Check logs regularly for errors
- Test each cron job manually before scheduling

