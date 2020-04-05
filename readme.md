# Steps to install & run

- Clone this repo
- Move to the newly created directory `cd import-excel-into-mysql`
- Run `composer install`.
- Import file 'users.sql' to MySQL database to create `users` table.
- Update config.php
	- Update the value of `mailgun_api_key` with the api key sent in email.
- Update `post_max_size` and `upload_max_filesize` values in php.ini to be greater than the file size.
- Run `php -S php -S 127.0.0.1:[custom_port]` in terminal
- Open "http://127.0.0.1:[custom_port]/uploader.php" in browser.
- Upload the 100K reocrds excel file.

**Notes:**
- I'm assuming the running OS os linux
- I used Spout library instead of PHPSpreadsheet to read the excel file because of the performance is very optimized
PhpSpreadSheet peak memory usage: 219 MB.
Spout peak memory usage: 2 MB.


*From Spout documentation:
Only one row at a time is stored in memory. A special technique is used to handle shared strings in XLSX, storing them - if needed - into several small temporary files that allows fast access.*

