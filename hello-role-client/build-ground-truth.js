var uc = window.location.toString();

let ua = uc.split('#');
var u = ua[0];
var readCategory = ua[1];

// Add a link to the page listed by Alexa
if (u.search('https://www.alexa.com/topsites/category') == 0) {
	var category = u.split('/')[6].toLowerCase();
	// extract the name of all the pages listed
	var aa = $('[class="td DescriptionCell"]>p>a[href]');
	for (let i=0; i<aa.length; i++) {
		// Value of the href attribute
		var h = $(aa[i]).attr('href');
		let s = h.split('/');
		let hs = s[s.length - 1];
		let l = 'https://' + hs + '#' + category;
		// change the link 
		$(aa[i]).attr("href", l);
		// add the right pointer
		$(aa[i]).attr('style', function(i,s) { return (s || '') + 'cursor: pointer !important;' });
	}
} else {
	/*
	let aria = getAriaRoles();
	aria = aria.length == 0 ? null : aria;
	sendPage(u, aria, getAllTags());
	*/
}

/**
 * Extract roles and tags and send them to the server
 */
function sendRolesAndTags() {
	let a = getAriaRoles();
	if (a == undefined) {
		console.log("Roles not found; however, data are sent to the server");
	}
	let t = getAllTags();
	sendPage(u, a, t);
}


function sendPage(url, roles, tags) {
	console.log(roles);
	var send = {};
	send.url = url;
	if (roles != undefined)
		send.roles = roles;
	if (tags != undefined)
		send.tags = tags;
	if (readCategory != undefined)
		send.category = readCategory;		
	chrome.runtime.sendMessage(
		{
			action: 'xhttp',
			method: 'POST', 
			data: send,
			url: 'http://localhost/helloroles/server/resource/pages?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a'
		}, function(segments) {
			// Add the coordinates of the block that represents main, contentInfo or navigation
			// In the server is defined a query to select the right blocks to consider. 
			// Landmarks are defined by the role or, if it exists, by the corrisponding HTML5 tag
			var gtUrl = 'http://localhost/helloroles/server/resource/pages?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a&url=' + url;
			chrome.runtime.sendMessage(
				{
					action: 'xhttp',
					method: 'GET',
					url: gtUrl
				}, function(page) {
					if (page.length == 0 || page == undefined) {
						// If there is not at least one landmark role or tag, it is not possible to mark anything 
						console.log("Probably, not enough roles");
					} else {
						// The url to save the blocks of the ground truth
						var gtUrl = 'http://localhost/helloroles/server/resource/gt_blocks?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a';
						// first, save the roles available
						for (let i=0; i<page[0].roles.length; i++) {
							if (page[0].roles[i] == undefined)
								continue;
							let r = getNodeRect('role', page[0].roles[i]);
							drawBlock(page[0].roles[i], r, 'aria');
							// Send the coordinates of the rectangle to the server
							var send = {};
							send.url = url;
							send.role = page[0].roles[i];
							send.block = '(' + r.x + ',' + r.y + '),' + ' (' + (r.x + r.w) + ',' + (r.y + r.h) + ')';
							send.width = windowWidth;
							chrome.runtime.sendMessage(
								{
									action: 'xhttp',
									method: 'POST',
									data: send,
									url: gtUrl
								}, function(page) {
									// post ok
								}
							);
						}
						// Then, save the tag available
						for (let i=0; i<page[0].tags.length; i++) {
							if (page[0].tags[i] == undefined)
								continue;
							let r = getTagRect(page[0].tags[i]);
							drawBlock(page[0].tags[i], r, 'aria');
							// Send the coordinates of the rectangle to the server
							var send = {};
							send.url = url;
							send.role = getRoleFromTag(page[0].tags[i]);
							send.block = '(' + r.x + ',' + r.y + '),' + ' (' + (r.x + r.w) + ',' + (r.y + r.h) + ')';
							send.width = windowWidth;
							chrome.runtime.sendMessage(
								{
									action: 'xhttp',
									method: 'POST',
									data: send,
									url: gtUrl
								}, function(page) {
									// post ok
								}
							);
						}					
					}
				}
			);
		}
	);
}

/**
 * Search for ARIA attributes (role) 
 */
function getAriaRoles() {
	let roles = $("[role]");
	let elements = [];
	for (let i=0; i<roles.length; i++) {
		let j = $(roles[i]);
		elements.push({tag: j.prop("tagName").toLowerCase(), role: j.attr('role')});
	}
	return elements;
}

function getAllTags() {
	let tags = $("*");
	let helper = {};
	for (let i=0; i<tags.length;i++) {
		helper[($(tags[i]).prop("tagName").toLowerCase())] = true;
	}
	return Object.keys(helper);

}

