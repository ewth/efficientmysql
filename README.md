# efficientmysql

Turns an inefficient, one-insert-per-line SQL dump into a more efficient multi-insert statement version.

Very rough, not intended for public consumption.

Based off a Navicat dump so might not work for everything.

Usage:

`php run.php`

It will automatically read from `file.sql` and output to `output.sql`.
