<?php


class efficientMysql
{

	const MAX_INSERT_SIZE = 250; // kb

	public function processDump($inFile, $outFile)
	{
		if (!file_exists($inFile) || !($f = fopen($inFile, 'r'))) {
			die("Cannot open '$file'");
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
			if (strlen($line) < 12 || substr($line, 0, 12) != 'INSERT INTO ') {
				fputs($f, $line . "\n");
			}
		}
		unset($data);

		fputs($f, "\n\n-- --------------\n-- Inserting data\n-- --------------\n\n");

		$insert = $statement = '';
		$statements = [];
		foreach ($inserts as $table => $values) {
			$statementStart = $statement = 'INSERT INTO `' . $table . '` VALUES ';
			foreach ($values as $insert) {
				if (substr($insert, strlen($insert)-1, 1) == ';') {
					$insert = substr($insert, 0, strlen($insert) - 1);
				}
				$statement .= $insert;
				if (strlen($statement . $insert) >= 1024 * self::MAX_INSERT_SIZE) {
					$statement .= ';';
					fputs($f, $statement . "\n");
					$statement = $statementStart;
				} else {
					$statement .= ', ';
				}
			}
		}
		fclose($f);
		echo "Done";

	}
}