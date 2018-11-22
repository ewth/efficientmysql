<?php

include('efficientMysql.php');

$efficientMysql = new efficientMysql();

$efficientMysql->processDump('file.sql','output.sql');