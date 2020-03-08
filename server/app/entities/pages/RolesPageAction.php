<?php



class RolesPageAction extends EntityAction
{
	
	const METHOD = 'get';
	
	public function exec($params, Entity $entity, $data=null) {
        $db = Factory::createDb();
        
        $query = "";
        
		return $this->getServer()->getItem($entity->getCollectionName(), $entity->getId(), false);
	}
	
	
	function getQuery() {
	    return "SELECT id, url, json_agg(role),
json_agg(
CASE 
	WHEN tag = 'main' AND role ilike 'main' THEN null
	WHEN tag = 'footer' AND role ilike 'contentInfo' THEN null
	WHEN tag = 'nav' AND role ilike 'navigation' THEN null
	ELSE
		tag
END)
FROM
(
	SELECT 
	pp.id, url,
	count(pp.id) OVER (PARTITION BY pp.id) AS found,
	nrole, role, tag
	FROM
	(
		SELECT p.id, role, tag
		FROM 
		pages p
		LEFT JOIN
		(
			SELECT id, role->>'role' AS role
			FROM
			pages,
			json_array_elements(roles) AS role
			WHERE
			role->>'role' = 'main'
		) r
		ON r.id = p.id
		LEFT JOIN
		(
			SELECT id, tag AS tag
			FROM
			pages,
			json_array_elements_text(tags) AS tag
			WHERE
			tag = 'main'
		) t
		ON t.id = p.id

		UNION

		SELECT p.id, role, tag
		FROM 
		pages p
		LEFT JOIN
		(
			SELECT id, role->>'role' AS role
			FROM
			pages,
			json_array_elements(roles) AS role
			WHERE
			role->>'role' = 'navigation'
		) r
		ON r.id = p.id
		LEFT JOIN
		(
			SELECT id, tag AS tag
			FROM
			pages,
			json_array_elements_text(tags) AS tag
			WHERE
			tag = 'nav'
		) t
		ON t.id = p.id

		UNION

		SELECT p.id, role, tag
		FROM 
		pages p
		LEFT JOIN
		(
			SELECT id, role->>'role' AS role
			FROM
			pages,
			json_array_elements(roles) AS role
			WHERE
			role->>'role' = 'contentInfo'
		) r
		ON r.id = p.id
		LEFT JOIN
		(
			SELECT id, tag AS tag
			FROM
			pages,
			json_array_elements_text(tags) AS tag
			WHERE
			tag = 'footer'
		) t
		ON t.id = p.id
	) pp
	LEFT JOIN
	(
		SELECT id, url, count(role) nrole
		FROM
		pages,
		json_array_elements(roles) role
		GROUP BY id, url
	) c
	ON c.id = pp.id
	WHERE 
	(role is not null OR tag is not null)
	AND
	nrole > 2 /* We accept only apges that have at least two roles defined */
) f
WHERE found = 3 /* the page has to have main, footer, and nav defined */
GROUP BY f.id, url
";
	}
	
}

?>