<!DOCTYPE html>
<html>
<head>
<title>Scheduler</title>
</head>
<body>

<h2 align = "center">Select Your Meeting Times</h2>

<!-- make form -->
<form action = "index.php" method = "post" name="newuser">
<!-- make table -->
<table border="1" align = "center">

<?php //populate table

	$data = getSchedule();
	global $tableColCount;
	$tableColCount = 0;
	date_default_timezone_set('America/New_York');
	
	echo "<tr>";
	echo "<th>User</th>";
	echo "<th>Action</th>";
	foreach ($data as $key => $val):
		$exploded = explode('^', $val);
		$phpDate = date("D\n m/d/y", strtotime($exploded[0]));
		$explodedTime = explode('|', $exploded[1]);
		foreach ($explodedTime as $key => $value):
			$regTime = date("h:i: A", strtotime($value));
			echo "<th> $phpDate \n $regTime </th>";
			$tableColCount++;
		endforeach;
	endforeach;
	echo "</tr>";
	
	
	//check form postings and write / edit file accordingly
	if ($_POST) {
		if($_POST['new']) {

		} elseif ($_POST['submit']) {                           //write new entry to file
			$line = $_POST["newUserName"];
			$user = $line;
			//open file and get exclusive lock
			$file = fopen("users.txt", "a");
			while(!flock($file, LOCK_EX)) {}
		
			//write to file and close
			$line = $line . "^";
			for ($i = 0; $i < $tableColCount; $i++) {
				if (isset($_POST[$i])) {
					$line = $line . ($i-2) . "|";
				}
			}
			if (substr($line, -1) == "|") {
				$line = substr($line, 0, -1);
			}
			fwrite($file, "\n" . $line);
			fclose($file);
		
			//set cookie
			$username = str_replace(" ", "_", $user);
			setcookie($username, $user, time()+60*60*24*30);
			
		} elseif ($_POST['editSubmit']) {                        //write file edits
			//the following will edit the file
			$line = $_POST["editUserName"];
			$user = $line;
			$originalUsername = $_POST['oldUser'];
			$line = $line . "^";
			
			for ($i = 0; $i < $tableColCount; $i++) {
				$checkboxValue = "edit" . $i;
				if (isset($_POST[$checkboxValue])) {
					//echo "checkbox" . $i . "is set";
					$line = $line . ($i) . "|";
				}
			}
			
			$line = substr($line, 0, -1);
			echo "<br />";

			//open file and get lock
			$file = fopen("users.txt", "r+");
			while(!flock($file, LOCK_EX)) {}
			
			//temporary array for file contents --> replace line that is changed
			$tempFileLines = array();
			while($file_line = fgets($file)) {
				$tempLine = explode("^", $file_line);
				if ($tempLine[0] == $originalUsername) {
					array_push($tempFileLines, $line . "\n");
				} else {
					array_push($tempFileLines, $file_line);
				}
			}
			/*
			for ($i = 0; $i < count($tempFileLines); $i++) { 
				echo "<br />";
				echo $tempFileLines[$i];
				echo "<br />";
			} */
			
			//write temporary array lines back to file
			rewind($file);
			foreach($tempFileLines as $file_line) {
				fwrite($file, $file_line);
				//echo "wrote" . $file_line;
			}
			fclose($file);
			
			//remove old cookie and set new cookie
			setcookie($originalUsername, "", time()-3600);
			$username = str_replace(" ", "_", $user);
			setcookie($username, $user, time()+60*60*24*30);
		}
	}
	
	getUserEntries();
		
	addNewUserRow();
	
?>
</table>
</form>
<?php
function getSchedule() {
	$data = file("schedule.txt");
	  foreach ($data as $key => $val):
		 
	  endforeach;
	  return $data;
} 
?> 

<?php //get user entries
function getUserEntries() {
	global $tableColCount;
	global $totals;
	
	for ($j = 0; $j < $tableColCount; $j++) {   //initialize total column values to zero
		$totals[$j] = 0;
	}

	$userData = file("users.txt");
		foreach ($userData as $key => $val):
			$exploded = explode('^', $val);
			$username = $exploded[0];
			$timesChecked = explode('|', $exploded[1]);
			$editValues = array();
			
			//input user name
			if (! isset($_POST[$username])) {
				echo "<tr>";
				echo "<td> $username </td>";
				
				//add edit button if user cookie exists
				$user_cookie = str_replace(" ", "_", $username);
				if (isset($_COOKIE[$user_cookie])) {
					echo "<td><input id = 'edit' type = 'submit' name = $username value = 'edit'></td>";
				} else {
					echo "<td>&nbsp;</td>";
				}
				
				//add check marks to checked times
				for ($i = 0; $i < $tableColCount; $i++) {
					if (in_array($i, $timesChecked)) {
						echo "<td align = 'center'>&#x2713;</td>";
						$totals[$i]++;
					} else {
						echo "<td> </td>";
					}
				}
				echo "</tr>";
			} else { 
				echo "<tr>";
				echo "<td> <input id = 'editUserName' type = 'text' name = 'editUserName' value = $username><input id = 'hiddenName' type = 'hidden' name = 'oldUser' value = $username></td>";
				echo "<td> <input id = 'editSubmit' type = 'submit' name = 'editSubmit' value = 'Submit'></td>";
				
				for ($i = 0; $i < $tableColCount; $i++) {
					$checkboxValue = "edit" . $i;
					if (in_array($i, $timesChecked)) {
						echo "<td align = 'center'> <input id = 'editCheckTime' type = 'checkbox' name = $checkboxValue value = $i checked = true></td>";
						//echo "'edit' . $i";
					} else {
						echo "<td align = 'center'> <input id = 'editCheckTime' type = 'checkbox' name = $checkboxValue value = $i></td>";
					}
				}
			}
		endforeach;
	}
?>

<?php
function addNewUserRow() {   //function to add new user row
	global $tableColCount;
	global $totals;
	$checks = array();
	//print new user row
	echo "<tr>";
	for ($i = 0; $i <= $tableColCount + 1; $i++) {
		if(! isset($_POST['new'])) {
			if ($i == 1) {
				echo "<td> <input id = 'new' type = 'submit' name = 'new' value = 'new'></td>";
			} else {
				echo "<td> </td>";
			}
		} else {
			if ($i ==0) {
				echo "<td> <input id = 'NewUserName' type = 'text' name = 'newUserName' value = 'name'></td>";
			} elseif ($i == 1) {
				echo "<td> <input id = 'submit' type = 'submit' name = 'submit' value = 'submit'></td>";
			} else {
				echo "<td align = 'center'> <input id = 'checkTime' type = 'checkbox' name = $i value = $i></td>";
			}
		}
	}
	echo "<tr>";

	//print totals 
	echo "<tr><th>Total</th><td>&nbsp;</td>";
	for ($i = 0; $i < $tableColCount; $i++) {
		echo "<td align = 'center'>" . $totals[$i] . "</td>";
	}
	echo "</tr>";
}

?>

</body>
</html>