<?php 


/**
 * Definisco i ruoli 
 */

$roles = '{
	"none": [
		{
			"location": "/",
			"methods": []
		}
	],
	"anonymous": [
		{
			"location": "/login",
			"methods": ["POST"]
		}
	],
	"member": [
		{
			"location": "/",
			"methods": ["GET"]
		},
		{
			"location": "/logout",
			"methods": ["POST"]
		},
		{
			"location": "/login",
			"methods": ["POST"]
		}		
	],
	"editor": [
		{
			"location": "/",
			"methods": ["GET", "PUT"]
		},
		{
			"location": "/resource/users/{uid}/carts",
			"methods": ["GET", "PUT", "POST"]
		},
		{
			"location": "/resource/users/{uid}/orders",
			"methods": ["GET", "PUT", "POST"]
		},
		{
			"location": "/logout",
			"methods": ["POST"]
		}
	]
}';




?>