<?php



class Queue
{

	private $db;
	
	public function __construct(Db $db) {
		$this->db = $db;
	}
	
	/**
	 * Inserisce un item nella coda
	 * @param array $data
	 * @param integer $agentId L'id dello script che processera' l'elemento
	 * @param json $description  descrizioni del lavoro, utili per visualizzare una descrizione all'utemte
	 * @param integer $subAgentId L'id di un subagente: uno script che gira insieme all'agent o per indicare delle opzioni di questo
	 * 	Usato per indicare se si tratta di un signup per l'agent queue-load-gse.php, puo' essere usato anche per altro
	 * @param string $deviceid L'identificativo del dispositivo iot all'interno della sua classe (ad esempio in Enel e' l'account)
	 * @param string $deviceType Il tipo di dispositivo iot, ad esempio Enel
	 */
	public function insert($data, $agentId=null, $description=null, $subAgentId=null, $deviceid=null, $deviceType=null) {
		$s = Factory::createServer();
		$user = $s->getCurrentUser(true);
		$userId = empty($user) ? null : $user['id'];
		return $this->db->insert('queue', 
				array(
						'user_id' => $userId, 
						'data' => $data, 
						'agent_id' => $agentId, 
						'description' => $description,
						'sub_agent_id' => $subAgentId,
						'deviceid' => $deviceid,
						'device_type' => $deviceType
				));
	}
	
	/**
	 * Restituisce l'elemento piu' giovane (quello con il minore id) in stato new
	 */
	public function getNew($agentId=LOAD_GSE_AGENT) {
		$query = "select min(id) id from queue where status = :status and agent_id = :agentid";
		$id = $this->db->dbQuery2($query, array(':status' => 'new', ':agentid' => $agentId))[0]['id'];
		return $this->db->getItem('queue', $id, true, null, true);
	}
	
	/**
	 * Restituisce l'elemento piu' giovane che ha raggiunto la data per essere
	 * processato ancora
	 */
	public function getTryAgain($agentId=LOAD_GSE_AGENT) {
		$query = "select min(id) id 
		from 
		queue 
		where status = :status and agent_id = :agentid and try_again_date < now()";
		$id = $this->db->dbQuery2($query, array(':status' => 'tryagain', ':agentid' => $agentId))[0]['id'];
		return $this->db->getItem('queue', $id, true, null, true);
	}
	
	/**
	 * Setta un elemento per essere processato ancora
	 * @param integer $itemId L'id dell'elemento nella coda
	 * @param string $interval L'intervallo dopo il quale riprovare. Sintassi postgresql. Default 28hour
	 */
	public function setTryAgain($itemId, $interval='28hour') {
		$query = "update queue set status = 'tryagain', progress = null, try_again_date = now() + interval '$interval' where id = :id";
		$params = array(':id' => $itemId);
		$this->db->dbQuery2($query, $params);
	}
	
	/**
	 * Aggiorna l'item nella coda
	 * @param integer $itemId
	 * @param array $dataArray Un array con i campi e valori da aggiornare:
	 * - status
	 * - errorMessage
	 * - done
	 */
	public function update($itemId, $dataArray) {
		$this->db->update('queue', $dataArray, $itemId);
	}
	
	
	
}



?>