<?php


class ActivationTimeUserAction extends EntityCollectionAction
{
	
	const METHOD = 'get';
	
	public function exec($params, EntityCollection $entity, $data=null) {
		
		$server = $entity->getServer();
		
		$user = $server->getCurrentUser(true);
		if (empty($user) || $user['type'] != ADMIN_TYPE_ID)
			return null;
		
		$table = $server->getTable($entity->getCollectionName());
		
		/*
		$query = "select DISTINCT ON (u.id, s.step) u.id, u.userid, u.name, u.surname, u.active, u.profile, u.mobile, u.address, u.zip_code, u.city, u.customer_group_id, type, time, s.step
		from
		$table u, signups s
		where u.type = :typeid and u.id = s.user_id and u.deleted = false and s.step = 'user-data'
		order by id, s.step DESC";
		*/
		
		// Estraggo gli utenti con join a signups e commissions_generator
		$query = "select DISTINCT ON (u.id, U.STEP) u.id, u.userid, u.name, u.surname, u.active, u.profile, u.address, u.zip_code, u.city, u.mobile, u.customer_group_id, u.wl,
					type, time, coupons, agents, u.guid
				FROM
				(
					select DISTINCT ON (u.id, s.step) u.id, u.userid, u.name, u.surname, u.active, u.profile, u.mobile, u.address, u.zip_code, u.city, u.customer_group_id, u.wl,
						type, time, s.step, u.guid
					FROM
					users u, signups s
					where u.type = :typeid and u.id = s.user_id and u.deleted = false and s.step = 'user-data'
					order by id, s.step DESC
				) u
				left join
				(
						select
						user_id, 
						json_agg((select row_to_json(_) from (
							select DISTINCT
							coupon_id as id, 
							coupon_code as code
						) as _)) as coupons,
						json_agg((select row_to_json(_) from (
							select DISTINCT
							agent_id as id, 
							agent_name as name
						) as _)) as agents		
						from
						(
						select distinct
							user_id, 
							cs.id as coupon_id, 
							origin->'description'->>'code' as coupon_code,
							commission->>'agent_id' as agent_id, 
							u2.name || ' ' || u2.surname agent_name
						from
						commissions_generator cg,
						json_array_elements(cg.commissions) commission,
						users u,
						users u2,
						coupons cs
						where
						(commission->>'agent_id')::numeric = u.id
						and
						u2.id = (commission->>'agent_id')::numeric
						and
						cs.code = origin->'description'->>'code'
						and
						u.deleted = false
						and
						cg.deleted = false
						and
						cg.start_date + (commission->'period' || ' days')::interval > now()
						) ag
						group by user_id
				) comm
				on
				u.id = comm.user_id";
		
		$queryAndParams = array('query' => $query, 'params' => array(':typeid' => CUSTOMER_TYPE_ID));
		
		return $server->getList('users', null, false, $queryAndParams);		
		
	}
}



?>