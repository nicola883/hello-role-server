<?php

class Factory
{
		/*
		 * Crea e fornisce un Robot
		 * @return Un robot
		 */
		static public function createRobot() {
			$db = self::createDb();
			return new Robot($db);
		}
		
		/*
		* Restituisce il Server. 
		*/
		static public function createServer() {
			return Server::get();
		}
		
		static public function createDb($pHost=null, $pUser=null, $pPwd=null, $pDb=null) {
			return Db::get($pHost, $pUser, $pPwd, $pDb);
		}
		
		/**
		 * Crea un ordine fornendo i dati
		 * @param array $data
		 * @param Server $server
		 * @return Order
		 */
		static public function createOrder($data, $server) {
			$order = new Order('orders', null, $server);
			$order->setData($data);
			return $order;
		}
		
		static public function createPayPal() {
			return new PayPal();
		}
		
		static public function createCatalog($productsData=null, $server) {
			if ($productsData == null) {
				$productsData = $server->getList('products', false, true, null, true);
			}
			$id = isset($productData['id']) ? $productData['id'] : null;
			return new Catalog('products', $id, $server, $productsData);
		}
		
		static public function createProduct($productData, $server) {
			$id = isset($productData['id']) ? $productData['id'] : null;
			$product = new Product('products', $id, $server);
			$product->setData($productData);
			return $product;
		}
		
		static public function createPlant($plantData, $server) {
			$plant = new Plant('plants', $plantData['id'], $server);
			$plant->setData($plantData);
			return $plant;
		}
		
		/**
		 * Costruisce e restituisce un coupon dato il suo codice
		 * Restituisce anche i non validi perche' ne servono i dati per calcolare commissioni e altro
		 * @param integer $couponCode Un codice coupon
		 * @return Coupon
		 */
		static public function createCoupon($couponCode, $server) {
			if ($couponCode === null)
				return null;
			// ricavo l'id
			$db = self::createDb();
			$query['params'] = array(':couponcode' => $couponCode);
			// Prendo anche i non validi perche' servono i dati
			$query['query'] = "select * from coupons where code = :couponcode and deleted = false";
			$data = $db->getItem('coupons', null, false, $query, true);
			
			if ($data === null)
				return null;		
			
			$coupon = new Coupon('coupons', $data['id'], $server, $data);
			$coupon->setData($data);
			return $coupon;
		}
		
		static public function createCart($cartData, $server) {
			$id = isset($cartData['id']) ? $cartData['id'] : null;
			$cart = new Cart('carts', $id, $server);
			$cart->setData($cartData);
			return $cart;
		}
		
		static public function createUploadedFile($fileData) {
			return new UploadedFile($fileData);
		}
		
		static public function createPaperReport($plantData, $language=null, $version=null) {
			return new PaperReport($plantData, $language, $version);
		}

		static public function createCrm() {
			return new EspoCrm();
		}
		
		static public function createQueue() {
			$db = self::createDb();
			return new Queue($db);
		}
		
		/**
		 * Crea un'entita' dato il nome della sua collezione (la tabella nel db)
		 * @param String $collectionName Il nome della collezione, chiesta dopo resource e che corrisponde
		 * a una tabella nel db
		 * @param integer $id L'id dell'entita'
		 * @param Server $server Il server
		 * @return Entity Una entita'
		 */
/*
		static public function createEntity($collectionName, $id, Server $server) {
			$entityName = Helper::uri2Camel(rtrim($collectionName, 's'));
			if (!class_exists($entityName))
				$entityName = 'Entity';
			return new $entityName($collectionName, $id, $server);
		}
*/
		/**
		 * Crea un'entita' dato il nome della sua collezione (la tabella nel db)
		 * @param String $collectionName Il nome della collezione, chiesta dopo resource e che corrisponde
		 * a una tabella nel db
		 * @param Server $server Il server
		 * @return Entity Una entita' collezione
		 */
/*
		static public function createEntityCollection($collectionName, Server $server) {
			$entityName = Helper::uri2Camel(rtrim($collectionName, 's')) . 'Collection';
			if (!class_exists($entityName))
				$entityName = 'EntityCollection';
			return new $entityName($collectionName, $server);
		}
*/
		
		/**
		 * Crea un'entita' dato il nome della sua collezione (la tabella nel db)
		 * Se l'id non e' vuoto, viene restituito un oggetto di tipo Entity, altrimenti di tipo
		 * EntityCollection
		 * Se non e' definita una classe Entity che ha un nome come quello della risorsa richiesta
		 * viene creata la superclasse Entity o EntityCollection
		 * Serve creare una classe EntityCollection perche' non voglio rischiare di creare una entita'
		 * senza id. In questo caso la EntityCollection conserva comunque il nome della collezione e altri parametri
		 * definiti all'ingresso, necessari per eseguire un'azione.
		 * @param string $collectionName Il nome della collezione, chiesta dopo resource e che corrisponde
		 * a una tabella nel db o a una tabella temporanea_restrict
		 * @param string $entityProposedName Il nome della entita' a cui verra' aggiunto eventualmente 'Collection'
		 * 	o che verra' sostituito dal nome di una entita' di default 
		 * @param Server $server Il server
		 * @param integer $id L'id dalla uri (dafault null) 
		 */
		static public function createEntity($collectionName, $entityProposedName, Server $server, $id=null) {
			$entityName = $entityProposedName; //Helper::uri2Camel(rtrim($collectionName, 's'));
			if (empty($id)) {
				$entityName = $entityName . 'Collection';
				if (!class_exists($entityName) || (class_exists($entityName) && !is_subclass_of($entityName, 'EntityCollection')))
					return new EntityCollection($collectionName, $server);
				return new $entityName($collectionName, $server);
			} else {
				if (!class_exists($entityName) || (class_exists($entityName) && !is_subclass_of($entityName, 'Entity')))
					return new Entity($collectionName, $id, $server);
				return new $entityName($collectionName, $id, $server);
			}
		}
		
		
		/**
		 * Crea un'azione per un'entita' con il nome del tipo dato.
		 * Se il nome dell'azione dato e' null, allora crea un'azione il cui tipo e':
		 * Get | Query | Update | Create <nome-classe-entita'>Action, in cui corrisponda il
		 * parametro METHOD con il metodo
		 * Essendoci di default le azioni:
		 * CreateAction, GetAction, QueryAction e DeleteAction, se non sono state definite delle 
		 * azioni per una Entita, vengono date queste.
		 * Le Action di default si applicano per:
		 * - richieste di entita' senza richiesta di ActionEntity e che non hanno una Action per le attivita' CRUD
		 * - richieste di entita' di default senza richiesta di ActionEntity
		 * @param string $entityClassName Il nome della classe ActionEntity
		 * @param string $method Il metodo della richiesta
		 * @param integer $id L'id della richiesta
		 * @param Server $server L'oggetto singleton Server
		 * @return EntityAction | boolean La classe EntityAction o false se non e' possibile crearla 
		 */
		static public function createEntityAction($actionEntityClassName, $method, $id=null, $className, $server) {
			if (empty($actionEntityClassName)) {
				//echo $className;
				$minClassNameA = ($pos = strrpos($className, 'Collection')) === false ? $className : substr($className, 0, $pos);
				$minClassName = ($pos = strrpos($minClassNameA, 'Entity')) === false ? $minClassNameA : substr($minClassNameA, 0, $pos);
				
				switch (strtoupper($method)) {
					case 'GET':
						$crudName = empty($id) ? 'Query' : 'Get';
						break;
					case 'POST':
						$crudName = 'Create';
						break;
					case 'PUT':
						$crudName = 'Update';
						break;
					case 'DELETE':
						$crudName = 'Delete';
				}
				// Provo a cercare una classe $crudName . nome_classe_senza_Collection . Action
				// Qui entro se ho, ad esempio, GetCartAction
				// qui entro anche se l'entita' richiesta e' una di default Entity o EntityCollection
				// ed esiste l'Action di default
				$crudAction = $crudName . $minClassName . 'Action';
				if(class_exists($crudAction) && strtoupper($crudAction::METHOD) == $method) {
					$actionClassName = $crudAction;
				} else {
					// Se non c'e' nessun azione definita per la classe do' quella di default
					// cerco per, ad esempio, GetAction
					$crudAction = $crudName . 'Action';
					if (class_exists($crudAction) && strtoupper($crudAction::METHOD) == $method)
						$actionClassName = $crudAction;
					else 
						return false; // Se non ho trovato neanchela classe Action di default
				}
			} else if(class_exists($actionEntityClassName) && defined("$actionEntityClassName::METHOD") && strtoupper($actionEntityClassName::METHOD) == $method) {
				$actionClassName = $actionEntityClassName;
			} else
				return false;

			// Se l'azione e' figlia di una EntityCollectionAction deve agire su una EntityCollection,
			// analogamente se e' figlia di una EntityAction deve agire su una Entity
			if (empty($id) && !is_subclass_of($actionClassName, 'EntityCollectionAction') || !empty($id) && !is_subclass_of($actionClassName, 'EntityAction'))
				return false;
			
			return new $actionClassName($server);
			
		}
		
		
}


?>
