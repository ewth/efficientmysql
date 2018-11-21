# efficientmysql

Turns an inefficient, one-insert-per-line SQL dump into a more efficient multi-insert statement version.

Very rough, not intended for public consumption.

Based off a Navicat dump so might not work for everything.

Usage:

`php run.php input.sql`

It will dump the result to screen if you don't direct it into a file, e.g.:

`php run.php input.sql > output.sql`
