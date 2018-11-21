<?php


class efficientMysql
{

	protected $sqlFile;

	public function __construct()
	{

	}

	public function processDump($file, $skipAutoIncrement = false, $maxStatementSize = 1048576)
	{
		if (!file_exists($file) || !($f = fopen($file, 'r'))) {
			die("Cannot open '$file'");
		}
		$this->sqlFile = $file;

		fclose($f);
		$data = file_get_contents($file);

		$data = str_replace("\r\n", "\n", $data);

		$statements = $insertStatements = [];
		$statement = $statementStarter = '';
		$autoIncrement = '';
		$tableName = '';
		$insertOpen = false;
		$data = explode("\n", $data);
		echo "-- Processing " . number_format(count($data)) . " lines.\n";
		for ($i=0;$i<count($data);$i++) {
			$line = $data[$i];
			$statements[] = $line;
			$autoIncrement = 0;
			$startCount = $stopCount = $i;
			$inserts = [];

			// New table
			if (strlen($line) >= 13 && strtoupper(substr($line, 0, 13)) == 'CREATE TABLE ') {
				if (preg_match('#CREATE TABLE (.[^ ]+)#i', $line, $match)) {
					$tableName = str_replace('`', '', $match[1]);
					$statements[] = $line;
				}
				// Find auto increment column
				if ($skipAutoIncrement) {
					while (!preg_match('#\)(.[^\n;]*);#', $line)) {
						if (strpos($line, ' AUTO_INCREMENT,') !== false) {
							if (preg_match('#(.[^ ]*)(.[^ ]+) #', $line, $match)) {
								$stopCount = $i;
							}
						}
						$statements[] = $line;
						$i++;
						$line = $data[$i];
					}
		 			if ($stopCount > $startCount) {
		 				$autoIncrement = ($stopCount - $startCount);
 					}
				}

				// Skip ahead to first insert statement
				while (strlen($line) < 13 || strtoupper(substr($line, 0, 12)) !== 'INSERT INTO ') {
					$statements[] = $line;
					$i++;
					$line = $data[$i];
				}

				$line = $data[$i];

				// Find all insert statements
				$finishInsert = false;
				while (!$finishInsert) {
					if (strlen($line) > 12 && strtoupper(substr($line, 0, 12)) == 'INSERT INTO ') {
						if (preg_match('#insert into [`](' . $tableName . ')[`] values \((.+)\)#i', $line, $match)) {
							$statementStarter = str_replace('('. $match[2] . ')', '', $match[0]);
							$inserts[] = $match[2];
						} else {
							$finishInsert = true;
						}
					} else {
						$finishInsert = true;
					}

					$i++;
					$line = $data[$i];
				}
				for ($j=0;$j<count($inserts);$j++) {
					$insert = $inserts[$j];
					$statement = $statementStarter;
					$keepGoing = true;
					while ($keepGoing) {
						$statement .= '(' . $insert . ')';
						if ($j >= (count($inserts)-1) || (strlen($statement . $insert) + 10) >= $maxStatementSize) {
							$keepGoing = false;
						}
						if ($keepGoing) {
							$j++;
							$statement .= ', ';
						} else {
							$statement .= ';';
						}
						$insert = $inserts[$j];
					}
					$statements[] = $statement;
				}
			}
			if (count($statements) > 1024) {
				echo implode("\n", $statements) . "\n";
				$statements = [];
			}
		}
		echo implode("\n", $statements) . "\n";
		$statements = [];
	}
}