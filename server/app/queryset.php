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

$pages = "SELECT 1 as user_id, false as deleted, id, category, url, json_agg(role) AS roles, nrole as nroles, found,
            json_agg(
            CASE 
            	WHEN tag = 'main' AND role ilike 'main' THEN null
            	WHEN tag = 'footer' AND role ilike 'contentInfo' THEN null
            	WHEN tag = 'nav' AND role ilike 'navigation' THEN null
            	ELSE
            		tag
            END) AS tags
            FROM
            (
            	/* tag and role for main */
				SELECT 
            	pp.id, url, category,
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
            			pages
						LEFT JOIN
            			json_array_elements(roles) AS role
						ON true
            			WHERE
            			role->>'role' ilike 'main'
            		) r
            		ON r.id = p.id
            		LEFT JOIN
            		(
            			SELECT id, tag AS tag
            			FROM
            			pages
						LEFT JOIN
            			json_array_elements_text(tags) AS tag
						ON true
            			WHERE
            			tag ilike 'main'
            		) t
            		ON t.id = p.id
            
            		UNION
            
					/* tag and role for navigation */
            		SELECT p.id, role, tag
            		FROM 
            		pages p
            		LEFT JOIN
            		(
            			SELECT id, role->>'role' AS role
            			FROM
            			pages
						LEFT JOIN
            			json_array_elements(roles) AS role
						ON true
            			WHERE
            			role->>'role' ilike 'navigation'
            		) r
            		ON r.id = p.id
            		LEFT JOIN
            		(
            			SELECT id, tag AS tag
            			FROM
            			pages
						LEFT JOIN
            			json_array_elements_text(tags) AS tag
						ON true
            			WHERE
            			tag ilike 'nav'
            		) t
            		ON t.id = p.id
            
            		UNION
            		
					/* tag and role for contentInfo */
            		SELECT p.id, role, tag
            		FROM 
            		pages p
            		LEFT JOIN
            		(
            			SELECT id, role->>'role' AS role
            			FROM
            			pages
						LEFT JOIN
            			json_array_elements(roles) AS role
						ON true
            			WHERE
            			role->>'role' ilike 'contentInfo'
            		) r
            		ON r.id = p.id
            		LEFT JOIN
            		(
            			SELECT id, tag AS tag
            			FROM
            			pages
						LEFT JOIN
            			json_array_elements_text(tags) AS tag
						ON true
            			WHERE
            			tag ilike 'footer'
            		) t
            		ON t.id = p.id
            	) pp
            	LEFT JOIN
            	(
            		SELECT id, url, count(role) nrole, category
            		FROM
            		pages
					LEFT JOIN
            		json_array_elements(roles) role
					ON true
            		GROUP BY id, url
            	) c
            	ON c.id = pp.id
            	WHERE 
            	(role is not null OR tag is not null) /* At least one landmark tags or roles */
            ) f
            GROUP BY f.id, category, url, nroles, found
            ORDER by category, url";

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
    'gt_blocks' => "select * from gt_blocks"
    
);




?>
