<?php 

    define('DEBUG_LOG ', null);
    include_once(__DIR__ . '/../server/scripts/_set_include.php');  
    
    // Set the parameters to select pages from the ground truth
    // according to the paper
    $params = array('found' => 3, 'nroles' => 2);
    $s = Factory::createServer();
    $s->setRestrict(1);
    $qp = new QueryPageAction($s);
    $entity = new EntityCollection('pages', $s);
    $ps = $qp->exec($params, $entity);
    $pages = json_decode($ps, true);
    
?>

<!DOCTYPE unspecified PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">    
<html>
<head>
	<title>Automatic Segmenter</title>
</head>
<body>

<table>
	<?php 
	   $p = 1;
	   foreach($pages as $page): ?>
	<tr>
		<td><?php echo $p++ . ' ' . $page['category']; ?></td>
		<td><a href="<?php echo $page['url']; ?>"><?php echo $page['url']; ?></a></td>
	</tr>
	<?php endforeach; ?>

</table>

</body>
</html>