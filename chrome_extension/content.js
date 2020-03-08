
var rolesFound = [];

// to reset the DOM
var firstDOM = document.body.cloneNode(true);

// corrispondence Aria / eMine / others:
var roles = {};
roles = {
	main: {emine: "article", html: "main"},
	contentInfo: {emine: "Footer", html: "footer"},
	navigation: {emine: "linkMenu", html: "nav"},
	/*none: {emine: "header"}*/
};

function getRoleFromTag(tag) {
	for (let ariaRole in roles) {
		if (tag == roles[ariaRole].html) {
			return ariaRole;
		}
	}
}

// Color and border color to draw for the different types of rectangles
rectColor = {
	block: {
				background: "rgba(180, 251, 156, 0.4)",
				border: "red"
			},
	aria: {
		background: "rgba(0, 0, 0, 0.2)",
		border: "blue"
	}
};

// the webpage to analize
var url = window.location.toString().split('#')[0];

/** 
 * Add the roles to the page
 */
function addRoles(id) {
	switch (id) {
		case 'eMine1':
			document.body = firstDOM.cloneNode(true);
			eMineRoles('first');
			break;
		case 'eMineDeep':
			document.body = firstDOM.cloneNode(true);
			eMineRoles('all');
			break;
		case 'sendAria':
			document.body = firstDOM.cloneNode(true);
			sendRolesAndTags();				 
	}
}

/**
 * Add eMine roles
 * @param string levels first | all
 */
function eMineRoles(levels) {
	drawAria();
	//var myBody = '{"url":"' + url + '", "width": 1920, "height": 1080, "agent": "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0", "explainRoles": false}';
	var myBody = {url: url, width: 1920, height: 1080, agent: "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0", 
		"explainRoles": false};	
	// we get the segments and role from eMine server
		chrome.runtime.sendMessage(
	{
		action: 'xhttp',
		method: 'POST', 
		data: myBody,
		url: 'http://localhost:8081'
	}, function(segments) {
		var blocks; // The eMine blocks
		// we do everything with the segments
		// For every key (main, contentInfo, navigation) we look for blocks
		// found by emine to associate  
		for (let key in roles) {
			rolesFound = [];
			if (levels == 'first') {
				// Select only the first eMine blocks that have the roles we need
				blocks = searchRoleFirst(roles[key].emine, segments.result);
			} else {
				blocks = searchRoleAll(roles[key].emine, segments.result, true);	
				console.log(roles[key].emine, segments.result, blocks);			
			}
			
			// Draws the block found by eMine
			if (blocks == [])
				continue;
			for (let i=0; i<blocks.length; i++) {
				block = blocks[i];
				var b = {x:block.topX, y:block.topY, w:block.width, h:block.height};
				let text = 'eMine' + ': ' + roles[key].emine + ' - ' + block.name; 
				// Get and color the DOM nodes covered by the block
				let nodes = getNodes(b);
				// Draw the eMine blocks
				drawBlock(text, b);
				// Send to the server the coordinates of the blocks
				var tUrl = 'http://localhost/helloroles/server/resource/evaluations?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a';
				var send = {};
				send.url = url;
				send.role = key;
				send.block = '(' + b.x + ',' + b.y + '),' + ' (' + (b.x + b.w) + ',' + (b.y + b.h) + ')';
				chrome.runtime.sendMessage(
					{
						action: 'xhttp',
						method: 'POST',
						data: send,
						url: tUrl
					}, function(page) {
					
					}
				);
			}			
		}
		
	});
}


/**
 * Draw Aria blocks
 * TODO Aria blocks should be write reading their position from the server. They are the blocks of the ground truth page
 * 	However, the page could be changed from when it was evaluated. A solution could be storing the webpage and not only the
 * 	position of the aria blocks.
 */
function drawAria() {
	var found = false;
	for (let key in roles) {
		let r = getNodeRect('role', key);
		if (r != undefined) {
			found = true;
			drawBlock(key, r, 'aria');
		}	
	}
	return found;
}




/**
 * Get the rectangle epressed by the node that has the attribute = value
 * @param {*} attribute 
 * @param {*} value 
 */
function getNodeRect(attribute, value) {
	var node = $("[role=" + value + " i ]");
	if (node == undefined || node.length == 0)
		return null;
	var r = {};
	je = jQuery(node);						// Jquery element from DOM
	r.x = je.offset().left;				// Retrieve the position top and left of the element
	r.y = je.offset().top;
	r.w = je.width();					// Retrieve the width of the elememt
	r.h = je.height();					// Retrieve the height of the element
	return r;
}

/**
 * Get the rectangle epressed by the node that has the tag given
 * @param {*} tag 
 */
function getTagRect(tag) {
	var node = $(tag);
	if (node == undefined || node.length == 0)
		return null;
	var r = {};
	je = jQuery(node);						// Jquery element from DOM
	r.x = je.offset().left;				// Retrieve the position top and left of the element
	r.y = je.offset().top;
	r.w = je.width();					// Retrieve the width of the elememt
	r.h = je.height();					// Retrieve the height of the element
	return r;
}


function getIntersection(r1, r2) {
	var x = Math.max(r1.x, r2.x);
	var y = Math.max(r1.y, r2.y);
	var xx = Math.min(r1.x + r1.w, r2.x + r2.w);
	var yy = Math.min(r1.y + r1.h, r2.y + r2.h);
	return({x:x, y:y, w:xx-x, h:yy-y});
  }



/**
 * Extract the positions of the role given from the eMine result
 * @param string role The eMine role to search
 * @param obj obj The eMine result
 * @param boolean all If false returns only articles that are descendant of containers  
 */
function searchRoleAll(role, obj, all) {

	var objRole = obj.role.toLowerCase();
	
	if (!all && role == 'article' && objRole != 'container' && objRole != 'body' && objRole != 'article') {
		return [];
	}
	
	var role = role.toLowerCase();
	var objRole = obj.role.toLowerCase();
	//console.log(obj.role.toLowerCase(), role);
	if (obj.role.toLowerCase() == role) {
		rolesFound.push(obj); 	
    } else if (obj.children == undefined) {
		return rolesFound;
	} else {
		for (var i=0; i<obj.children.length; i++) {
			var r = searchRoleAll(role, obj.children[i]);
		}
	}
	return rolesFound;
}




/**
 * Get the given role data of the role in the eMine object that is in the upper lever
 * @param {*} role 
 */
function searchRoleFirst(role, obj) {

	var firstObj;
	// get the roles less deep
	var blocks = searchRoleAll(role, obj, false);

	console.log(role, blocks);

	// get all roles
	for (let i=0; i<blocks.length; i++) {
		if (role.toLowerCase() == blocks[i].role.toLowerCase() && (firstObj === undefined || 
			firstObj.name.split('.').length > blocks[i].name.split('.').length)) {
			firstObj = blocks[i];
		}
	}

	if (firstObj == undefined)
		return [];
	return [firstObj];
}

/**
 * Return the DOM nodes covered by a rectangle
 */
function getNodes(b) {

	// Get all DOM elements of the page except the dialog panel
	var all = document.body.getElementsByTagName("*");
	var inpoint = [];
	var max = 0;
	var maxelem = undefined;
	for (var i=0, max=all.length; i < max; i++) {
		// get a DOM element
		e = all[i];
		if (e) {
			var isElement = (		
				jQuery(e).is(":visible") && 			// is visible
				(e.tagName.toLowerCase()!="html") && 	// it is not the html element (root)
				(e.tagName.toLowerCase()!="head") && 	// it is not the head
				(e.tagName.toLowerCase()!="body")	// it is not the body	
			);

			if (isElement) {
				je = jQuery(e);						// Jquery element from DOM
				eleft = je.offset().left;				// Retrieve the position top and left of the element
				etop = je.offset().top;
				ewidth = je.width();					// Retrieve the width of the elememt
				eheight = je.height();					// Retrieve the height of the element

				//~ console.log(eid(e),jQuery(e).is(":visible"),content_count_of(e),b["x"]<=eleft,b["y"]<=etop,eleft<=b["w"],etop<=b["h"]);
				deltah = 5;
				deltaw = 70;
				// Check if the element is inside the block
				// b.x - delta <= eleft + ewidth <= b.x + b.w + delta
				// b.y - delta <= etop + eheight <= b.y + b.h + delta
				//if (y == 141 && x == 8 && ewidth == 1904 && eheight == 90)
				//	console.log((x - delta <= eleft), (y - delta <= etop), (eleft + ewidth <= x + w + delta), (etop + eheight <= y + h + delta));
				var isInside = ( 
					(b.x - deltaw <= eleft) 	&&	// The left point has to be inside the block except for a delta value
					(b.y - deltah <= etop)  	&&	// The top point has to be inside the block except for a delta value
					(eleft + ewidth <= b.x + b.w + deltaw) 	&& 	// The left point has to be inside the block except for a delta value
					(etop + eheight <= b.y + b.h + deltah)	// The top point has to be inside the block except for a delta value
				);
				if (isInside) {
					inpoint.push(e);
				} else {
					//console.log(b['blockId'], e);
				}
			}
		}
	}
    return inpoint;
}

/**
 * Draw a rectangle 
 * @param {*} role The name of the role
 * @param {*} b The dimensions and coordinates of the rectangle to draw 
 * @param {*} type Type of the rectangle aria | block. It defines the background color
 */
function drawBlock(role, b, type) {
	if (type == undefined)
		type = 'block';
		
	//console.log(role, x, y, w, h);
	var div = document.createElement("div"); 
	var p = document.createElement("p"); 
	div.appendChild(p);
	document.body.appendChild(div); 
	p.innerText = role;
	p.setAttribute("style", "color:black; background: rgba(255, 0, 0, 1); display:table; padding: 0 10px");
	if (type != 'block') {
		div.setAttribute("style", "position:absolute; top:" + b.y + "px; left:" + b.x + "px; width:" + b.w + "px; height:" + b.h + "px; background: " + rectColor[type].background + "; border: 2px solid " + rectColor[type].border + "; z-index:100000");
	} else {
		div.setAttribute("style", "position:absolute; top:" + b.y + "px; left:" + b.x + "px; width:" + b.w + "px; height:" + b.h + "px; z-index:100000");
		div.className = ' stripes';
	}
}

function colorNode(role, node) {
	var color;

	role = role.toLowerCase();

	switch (role) {
		case 'article':
			color = "blue";
			break;
		case 'linkmenu':
			color = 'yellow';
			break;
		case 'footer':
			color = 'tomato';
			break;
	}


	if (node == undefined)
		return;
	/*
	je = jQuery(node);						// Jquery element from DOM
	var x = je.offset().left;				// Retrieve the position top and left of the element
	var y = je.offset().top;
	var w = je.width();					// Retrieve the width of the elememt
	var h = je.height();					// Retrieve the height of the element	
	var div = document.createElement("div"); 
	var p = document.createElement("p"); 
	div.appendChild(p);
	*/
	/*
	var p = document.createElement('p'); 
	node.appendChild(p); 
	p.innerText = role;
	p.setAttribute("style", "position:absolute; top:0; right:0; color:white; background: rgba(255, 0, 0, 1); display:table; padding: 0 10px");
	*/
//	div.setAttribute("style", "position:absolute; top:" + y + "px; left:" + x + "px; width:" + w + "px; height:" + h + "px; background: rgba(0, 0, 0, 0.4); border: 2px solid red; z-index:100000");
	node.setAttribute("style", "outline: 1px solid " + color + "; z-index:100000");
}