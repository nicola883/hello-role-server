<?php

/**
 * query per la creazione delle view temporanee dell'utente
 * in tutte le tabelle aggiungo il campo user_id necessario per fare le ricerche
 * accetta il parametro :id verra' sostituito con l'id dell'utente corrente
 * Parametri:
 * 	$isAdmin boolean True se l'utente connesso e' di tipo amministratore
 $adminTypeId integer Il valore del campo type nel caso di amministratore
 $agentTypeId integer Il valore del campo type nel caso di agente
 */

// Treshold to consider result valid
$treshold = .7;

$pages = "SELECT 
1 AS user_id,
deleted,
id,
r.url,
gt.width,
category,
'[]' AS tags,
json_agg(role) roles
FROM
(
	SELECT
	id,
    deleted,
	url,
	category,
	count(tr->>'role') as nr,
	tr->>'role' as role 
	FROM 
	pages,
	json_array_elements(roles) AS tr
	WHERE 
	(tr->>'role' ILIKE 'main' AND tr->>'tag' NOT ILIKE 'main' 
	 OR 
	 tr->>'role' ILIKE 'navigation' AND tr->>'tag' NOT ILIKE 'nav' 
	 OR
	 tr->>'role' ILIKE 'contentInfo' AND tr->>'tag' NOT ILIKE 'footer'
	)
	GROUP BY id, url, tr->>'role', category
	ORDER BY url
) r
LEFT JOIN
(
    select distinct url, width from gt_blocks where width is not null
) gt
ON gt.url = r.url
/* Select only pages that have the role univoque. gt_blocks fields have to be univoque */
WHERE nr = 1 AND deleted is false
GROUP BY id, r.url, category, gt.width, r.deleted
ORDER BY id";

$resultPre = "SELECT
false AS deleted,
tool,
role,
url,
gb_area,
e_area,
intersection_area_sum,
percentage,
really_relevant,
CASE WHEN really_relevant IS TRUE THEN 1 ELSE 0 END AS TP,
CASE WHEN really_relevant IS FALSE THEN 1 ELSE 0 END AS FP,
CASE WHEN really_relevant IS TRUE OR really_relevant IS NULL THEN 1 ELSE 0 END AS TN,
CASE WHEN really_relevant IS FALSE OR really_relevant IS NULL THEN 1 ELSE 0 END AS FN
FROM
(
select 
tool,
role,
url,
gb_area,
e_area,
intersection_area_sum,
percentage,
CASE 
	WHEN discovered_role IS NOT NULL THEN
		CASE WHEN percentage >= $treshold THEN
			true
		ELSE
			false
		END
	ELSE
		NULL
END really_relevant
FROM
(";

$resultsStart = "SELECT * FROM
(
SELECT 
false as deleted,
tool,
category,
url,
role,
discovered_role,
gb_area::integer,
e_area::integer,
sum(intersection_area)::integer intersection_area_sum,
CASE
	WHEN gb_area > 0 THEN (sum(intersection_area) / gb_area)::numeric(10,3)
ELSE
	0
END percentage
FROM
(
	SELECT
	tool,
	p.category,
	p.url,
	lower(gb.role) AS role,
	lower(e.role) discovered_role,
	gb.block gb_block,
	e.block e_block,
	area(gb.block) gb_area,
	area(e.block) e_area,
	CASE WHEN
		area(gb.block # e.block) IS NULL THEN 0
	ELSE
		area(gb.block # e.block)
	END as intersection_area
	FROM
	(
		SELECT
		id,
		url,
		category,
		count(tr->>'role') as nr,
		tr->>'role' as role 
		FROM 
		pages,
		json_array_elements(roles) AS tr
		WHERE 
		(tr->>'role' ILIKE 'main' AND tr->>'tag' NOT ILIKE 'main' 
		 OR 
		 tr->>'role' ILIKE 'navigation' AND tr->>'tag' NOT ILIKE 'nav' 
		 OR
		 tr->>'role' ILIKE 'contentInfo' AND tr->>'tag' NOT ILIKE 'footer'
		)
		GROUP BY id, url, tr->>'role', category
		ORDER BY url
	) p";

$resultsJoin = "	LEFT JOIN
gt_blocks gb
ON p.url = gb.url AND p.role = gb.role AND nr = 1 /* In the ground truth only roles that are univocal */
LEFT JOIN
evaluations e
ON e.url = p.url AND e.role ilike gb.role";

$resultsReverseJoin = "	LEFT JOIN
evaluations gb
ON p.url = gb.url AND p.role = gb.role AND nr = 1 /* In the ground truth only roles that are univocal */
LEFT JOIN
gt_blocks e
ON e.url = p.url AND e.role ilike gb.role";


$resultsEnd = ") a
GROUP BY tool, category, url, role, gb_area, e_area, discovered_role
) t
WHERE role is not null and gb_area > 0";

$resultFinal = " ) view_results
) rr
ORDER BY url";

$results = $resultsStart . $resultsJoin . $resultsEnd . ' ORDER BY url, role';
$resultsReverse = $resultsStart . $resultsReverseJoin . $resultsEnd . ' AND discovered_role is not null ORDER BY url, role';

$results1 = $resultPre . $results . $resultFinal;
$results2 = $resultPre . $resultsReverse . $resultFinal;

$perRoleStart = "SELECT 
false AS deleted, 
tool,
role, true_positives, false_positives, false_negatives, true_negatives, precision, recall,
CASE WHEN (precision + recall > 0) THEN
	(2*precision * recall / (precision + recall))::numeric(4,3)
ELSE null END AS f1
FROM
(
select 
tool,
role, true_positives, false_positives, false_negatives, true_negatives,
CASE WHEN (true_positives + false_positives > 0) THEN	
	(true_positives / (true_positives + false_positives))::numeric(4,3) 
ELSE null END AS precision,
CASE WHEN (true_positives + false_negatives > 0) THEN
	(true_positives / (true_positives + false_negatives))::numeric(4,3)
ELSE null END AS recall
FROM
(
SELECT
tool,
role,
sum(TP)::numeric(7,3) true_positives,
sum(FP)::numeric(7,3) false_positives,
sum(FN)::numeric(7,3) false_negatives,
sum(TN)::numeric(7,3) true_negatives
FROM (";

$perRoleEnd = ") view_test_per_url GROUP BY tool, role
) t
)tt";

$perRole = $perRoleStart . $results1 . $perRoleEnd;
$perRoleReverse = $perRoleStart . $results2 . $perRoleEnd;

$restrictQuery = array(
    
    'users' => "SELECT id, deleted, userid, name, surname, address, zip_code, city, mobile, vat_number, fiscal_code, active, type, profile, customer_group_id
				FROM users
				WHERE
				(id = :id OR $isAdmin)
				AND
				deleted = false",
    
    'signups' => "select u.id, false as deleted, u.userid, max(time) as time
					from
					users u,
					signups s
					where
					u.id = s.user_id
					and
					u.deleted = false
					and
					u.active = true
					and
					($isAdmin OR u.id = :id)
					group by u.id, u.userid
					order by time",
    
    'settings' => 'SELECT false as deleted, p.id as id, settings, u.id as user_id from profiles as p, users as u where u.profile = p.id and u.id = :id',
    
    //'pages' => 'SELECT * from pages WHERE deleted is false AND user_id = :id or true',
    
    // Select roles and tags of the pages of the ground truth
    'pages' => $pages,
    
    'evaluations' => "SELECT * from evaluations",
	'gt_blocks' => "select * from gt_blocks where deleted is false order by url, role",

	'results' => $results1,
	'results_reverse' => $results2,

	'perrole' => $perRole,
	'perrole_reverse' => $perRoleReverse
	
    
);




?>
