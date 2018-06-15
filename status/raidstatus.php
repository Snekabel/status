<?php
	//make sure the nginx user can run hprm-info.sh, you can get it from here: https://github.com/Snekabel/hprm-info
	//Also make sure that the credentials file is accessible by the user but also is in a safe place.
	$result = shell_exec('hprm-info.sh 127.0.0.1 8383 /root/.raidcredentials');
	//$result = shell_exec('ls -al');

	$type = "";
	if(isset($_GET['type'])) {
		$type = $_GET['type'];
	}

	if($type == "xml")
	{
		header("Content-type: text/xml");
		echo $result;
	}
	else if($type == "json")
	{
		header('Content-Type: application/json');
		$result_parsed = simplexml_load_string($result);
		echo json_encode($result_parsed);
	}
	else if($type == "table")
	{
		?>
		<table>
		<thead>
			<tr><th>Nr</th><th>Id</th><th>Status</th><th>Model<th><th>Capacity</th></tr>
		</thead>
		<tbody>
		<?php
		$result_parsed = simplexml_load_string($result);
		for($i = 0; $i < count($result_parsed->hpt_raid->disks->disk); $i++)
		{
			$disk = $result_parsed->hpt_raid->disks->disk[$i];
			//error_log(json_encode($disk));
			//$attributes = $disk->attributes();
			/*foreach($disk->attributes() as $a => $b) {
				if($a == "id") {
					$id = $b;
				}
			}*/
			$id = $disk->attributes()['id'];
			//$id = $disk->@attributes->id;
			//$des = "";
			/*foreach($disk->flags->attributes() as $a => $b) {
                                if($a == "des") {
                                        $des = $b;
                                }
                        }*/
			$des = $disk->flags->attributes()['des'];
			//$des = $disk->flags->@attributes->des;
			$capacity = $disk->capacity;
			$model = $disk->model;
			echo "<tr> <td>$i</td><td>$id</td><td>$des</td><td>$model</td><td>$capacity</td></tr>";
		}
		?>
		</tbody>
		</table>
		<?php
		//print_r($result_parsed->hpt_raid->disks->disk[0]->attributes());
	}
	else
	{
	?>
		<h3>RAID Status</h3>
		<div>
			<a href="?type=xml">XML Output</a>
		</div>
		<div>
			<a href="?type=json">JSON Output</a>
		</div>
		<div>
			<a href="?type=table">Table Output</a>
		</div>
	<?php
	//echo "<pre>$result</pre>";
	}
?>
