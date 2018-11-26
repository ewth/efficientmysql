<?php


class efficientMysql
{

	const MAX_INSERT_SIZE = 100; // kb
	const MAX_LINE_LENGTH = 1; // kb

	public function processDump($inFile, $outFile)
	{

		die("This doesn't work at the moment and you will lose data.");

		if (!file_exists($inFile) || !($f = fopen($inFile, 'r'))) {
			die("Cannot open '$inFile'");
		}
		fclose($f);

		if (!($f = fopen($outFile, 'w'))) {
			die("Cannot open '$outFile' for writing.");
		}

		$data = file_get_contents($inFile);
		$data = str_replace("\r\n", "\n", $data);

		preg_match_all('#INSERT INTO \`(.[^\`]+)` VALUES (\(.+)#', $data, $matches);

		// Extract and group all inserts
		$inserts = [];
		$tables = [];
		foreach ($matches[2] as $key => $match) {
			$table = $matches[1][$key];
			if (!isset($inserts[$table])) {
				$inserts[$table] = [];
			}
			$inserts[$table][] = $match;
		}

		unset($matches);

		// Print all non-insert statements to file
		$data = explode("\n", $data);
		foreach ($data as $line) {
			if (!preg_match('#INSERT INTO \`(.[^\`]+)` VALUES (\(.+)#', $line)) {
				fputs($f, $line . "\n");
			}
		}
		unset($data);

		fputs($f, "\n\n-- --------------\n-- Inserting data\n-- --------------\n\n");

		$insert = $statement = '';
		$lastLine = 0;
		$statements = [];
		foreach ($inserts as $table => $values) {
			echo "\tTable `$table`...";
			$statementStart = $statement = 'INSERT INTO `' . $table . '` VALUES ';
			foreach ($values as $insert) {
				if (substr($insert, (strlen($insert)-1), 1) == ';') {
					$insert = substr($insert, 0, (strlen($insert) - 1));
				}
				$statement .= $insert;
				$lastLine += strlen($insert);

				if (strlen($statement . $insert) >= 1024 * self::MAX_INSERT_SIZE) {
					$statement .= ';';
					fputs($f, $statement . "\n");
					$lastLine = 0;
					$statement = $statementStart;
				} else {
					if ($lastLine >= 1024 * self::MAX_LINE_LENGTH) {
						$statement .= ",\n  ";
						$lastLine = 0;
					} else {
						$statement .= ', ';
					}
				}
			}
			echo " " . count($values) . " records.\n";
		}
		fclose($f);
		echo "Done";

	}
}