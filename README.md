# cautious-journey
A Log File Monitoring script

Uses:

1. PHPMailer to send emails with the logs as attachments
1. Commando for CLI parameter management.

---

Usage:

List the Help: `php log_monitor.php --help`

Add "some@email.address" to the array of To Addresses: `php log_monitor.php --to 'some@email.address'`

Replace the log list found in the config with the single file found at "path/to/log.file":
`php log_monitor.php --logname 'path/to/log.file'`

You can use the short alias `-l` in place of `--logname` and `-t` in place of `--to`.

Both parameters are optional and can be used together.

Please notice the difference in behavior between the two:
* `--logname` **REPLACES** the emtire log file list found in the config
* `--to` **APPENDS** to the end of the "mail to" list found in the config

This difference **is** intentional. The script as a whole is designed to be run via a cron job.
The additional of the command line arguments is to enable it to serve a dual purpose as a single run script.

---

* **TODO**: Add an argument to override the "age" parameter found in the config.
* Possible **TODO**: add aruments to override any given config param.

---
