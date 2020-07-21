<?php 

    define('DEBUG_LOG ', null);
    include_once(__DIR__ . '/../server/scripts/_set_include.php');  
	
	

    // Set the parameters to select pages from the ground truth
    // according to the paper
	if (isset($_GET['tool']) && $_GET['tool'] == '') unset($_GET['tool']);
	$params = $_GET;
    $s = Factory::createServer();
    $s->setRestrict(1);
    $qp = new ResultEvaluationAction($s);
    $entity = new EntityCollection('evaluations', $s);
    $ps = $qp->exec($params, $entity);
    $pages = json_decode($ps, true);
    
?>

<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">    
<html>
<head>
	<title>Automatic Segmenter</title>
</head>
<body>
<?php if (isset($_GET['tool']) && $_GET['tool'] == 'segments') echo 'selected' ?> 
<form method="GET">
	<label>Reverse</label>
	<input type="checkbox" id="reverse" name="reverse" value="true" <?php echo isset($_GET['reverse']) ? 'checked' : '' ?>/>

	<label>Per role</label>
	<input type="checkbox" id="perrole" name="perrole" value="true" <?php echo isset($_GET['perrole']) ? 'checked' : '' ?>/>
	<label for="tool">Select a tool</label>

	<select name="tool" id="tool">
		<option value>--- select an option ---</option>
		<option value="segments" <?php if (isset($_GET['tool']) && $_GET['tool'] == 'segments') echo 'selected' ?> >segments</option>
		<option value="emine" <?php if (isset($_GET['tool']) && $_GET['tool'] == 'emine') echo 'selected' ?> >emine</option>
	</select>	
	<input type="submit">
</form>

<?php if(!isset($_GET['perrole'])) : ?>
	<table border="1">
		<tbody>
			<tr>
				<th>Tool</th><th>url</th><th>Role</th><th>gb_area</th><th>e_area</th><th>Intersection</th><th>%</th><th>e_area/gb_area</th>
				<th>tp</th><th>fp</th><th>tn</th><th>fn</th>
			</tr>
		</tbody>
		<?php 
		$p = 1;
		foreach($pages as $page): ?>
		<tr>
			<td><?php echo $page['tool'];?></td>
			<td><a href="<?php echo $page['url']; ?>"><?php echo $page['url']; ?></a></td>
			<td><?php echo $page['role']?></td>
			<td><?php echo $page['gb_area']?></td>
			<td><?php echo $page['e_area']?></td>
			<td><?php echo $page['intersection_area_sum']?></td>
			<td><?php echo $page['percentage']?></td>
			<td><?php echo (int)(($page['e_area'] / $page['gb_area']) * 100) / 100 ?></td>			
			<td><?php echo $page['tp']?></td>
			<td><?php echo $page['fp']?></td>
			<td><?php echo $page['tn']?></td>
			<td><?php echo $page['fn']?></td>
		</tr>
		<?php endforeach; ?>

	</table>
<?php endif; ?>

<?php if(isset($_GET['perrole'])) : ?>
	<table border="1">
		<tbody>
			<tr>
				<th>Tool</th><th>Role</th><th>TP</th><th>FP</th><th>FN</th><th>TN</th>
				<th>Precision</th><th>Recall</th><th>F1</th>
			</tr>
		</tbody>
		<?php 
		foreach($pages as $page): ?>
		<tr>
			<td><?php echo $page['tool'];?></td>
			<td><?php echo $page['role']?></td>
			<td><?php echo $page['true_positives']?></td>
			<td><?php echo $page['false_positives']?></td>
			<td><?php echo $page['false_negatives']?></td>
			<td><?php echo $page['true_negatives']?></td>
			<td><?php echo $page['precision']?></td>
			<td><?php echo $page['recall']?></td>
			<td><?php echo $page['f1']?></td>
		</tr>
		<?php endforeach; ?>

	</table>
<?php endif; ?>


</body>
</html>