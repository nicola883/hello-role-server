var thisToolName = 'segments';
var active = true;
var typeId = 'data-33eae942-8f16-4ec3-a98b-0b6409e74095-segment-block';
var roles = {"main": "Main", "contentInfo":"Footer", "navigation":"Menu"};
var rolesToSet = {"main": "Main", "contentInfo":"Footer", "navigation":"Menu"}; 
// the webpage to analize
var url = window.location.toString().split('#')[0];

var windowWidth = window.innerWidth;

// to reset the DOM
var firstDOM = document.body.cloneNode(true);


// Variables used to draw a block
var drawOn = false;
var saved = true;
var start = {};
var end = {};
var inp;
var div = undefined;
var color = 'rgba(180, 251, 156, 0.4)';
var colorGroundTruth = 'rgba(0, 0, 0, 0.2)';

chrome.storage.local.get(['active'], function(result) {
	active = result.active;
	if (active) {
		drawEvaluations();

		// in a content script, at run_at:document_start
		window.addEventListener('mousedown', function(e) {
			
			if (!isEmpty(rolesToSet) && saved && (e.srcElement.getAttribute(typeId) == undefined && 
					(e.srcElement.parentElement == undefined || e.srcElement.parentElement.getAttribute(typeId) == undefined))) {
				div = document.createElement('div');
				div.setAttribute(typeId, 'true');
				document.body.appendChild(div);		
				drawOn = true;
				saved = false;
				start.x = e.pageX;
				start.y = e.pageY;
			}
			event.stopImmediatePropagation();
		}, true);

		window.addEventListener('mouseup', function(e) {
			if (drawOn) {
				let w = e.pageX - start.x;
				let h = e.pageY - start.y;
				if (w > 50 && h > 20) {
					drawOn = false;
					end.x = e.pageX;
					end.y = e.pageY;
					inp = document.createElement('select');
					inp.setAttribute('id', 'role'); // TODO role should be unique
					let o;
					let r = orderObj(rolesToSet);
					for (role in r) {
						o = document.createElement('option');
						o.setAttribute('value', role);
						o.innerHTML = rolesToSet[role];
						inp.appendChild(o);
					}
					
					div.appendChild(inp);

					let s = document.createElement('button');
					s.setAttribute('type', 'submit');
					s.innerHTML = 'Save';
					s.addEventListener("click", function() {
						saveDiv(this.parentElement, inp.value, start, end);
					});
					div.appendChild(s);
				} else {
					div.remove();
					saved = true;
				}
			}
			event.stopImmediatePropagation();
		}, true);

		window.addEventListener('mousemove', function(e) {
			if (drawOn) {
				let w = e.pageX - start.x;
				let h = e.pageY - start.y;
				var s = 'z-index:10000;position:absolute; top:' + start.y +'px;left: ' + start.x + 'px;background-color:'+color+'; height: '+ h +'px; width: ' + w + 'px;';
				div.setAttribute("style", s);
			}

			event.stopImmediatePropagation();
		}, true);
	}
});


/**
 * Save the current block to the server
 */
function saveDiv(el, role, start, end) {
		var pUrl = 'http://localhost/helloroles/server/resource/evaluations?single&key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a';
		var gUrl = 'http://localhost/helloroles/server/resource/evaluations?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a';
		var send = {};
		send.url = url;
		send.role = role;
		send.tool = thisToolName;
		send.block = '(' + start.x + ',' + start.y + '),' + ' (' + end.x + ',' + end.y + ')';
		chrome.runtime.sendMessage(
			{
				action: 'xhttp',
				method: 'POST',
				data: send,
				url: pUrl
			}, function(page) {
				chrome.runtime.sendMessage(
					{
						action: 'xhttp',
						method: 'GET',
						url: gUrl + '&tool=segments&url='+url+'&tool='+thisToolName+'&role='+role
 					}, function(list) {
						saved = true; 
						el.remove();
						drawBlocks(list);
					}
				);
			}
		);
}

function drawBlocks(list, isGroundTruth) {
	let col = isGroundTruth ? colorGroundTruth : color;
	for (let i=0; i<list.length; i++) {
		let co = list[i].block.replace(/[\(\)]/g, '').split(',');
		let d = document.createElement("div");
		document.body.appendChild(d);
		let h = co[1] - co[3];
		let w = co[0] - co[2];
		let a = 'z-index:10000;position:absolute; top:' + co[3] +'px;left: ' + co[2] + 'px;background-color:'+col+'; height: '+ h +'px; width: ' + w + 'px;';
		d.setAttribute("style", a);
		if (!isGroundTruth) {
			d.setAttribute('data-coord', list[i].block);
			d.setAttribute('data-role', list[i].role);
			delete rolesToSet[list[i].role];

			let s = document.createElement('button');
			s.setAttribute('type', 'button');
			s.innerHTML = 'Delete';
			d.appendChild(s);
			d.classList.add("stripes");
			s.addEventListener("click", function() {
					saved = true;
					$(this.parentElement).remove();
					deleteDiv(this.parentElement);
				}
			);
		} else {
			let p = document.createElement('p');
			p.innerHTML = list[i].role;
			d.appendChild(p);
		}		
	}
}

/**
 * Delete the current block from the server
 */
function deleteDiv(node) {
		let role = node.getAttribute('data-role')
		var gtUrl = 'http://localhost/helloroles/server/resource/evaluations?single&key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a&delete&url=' + url;
		chrome.runtime.sendMessage (
			{
				action: 'xhttp',
				method: 'POST',
				url: gtUrl,
				data: {"role": role, "tool": thisToolName, "url": url}
			}, function(page) {
					saved = true;
					rolesToSet[role] = roles[role];
				}			
		);
}

function drawEvaluations() {
	var tUrl = 'http://localhost/helloroles/server/resource/evaluations?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a&url='+url+'&tool='+thisToolName;
	chrome.runtime.sendMessage(
		{
			action: 'xhttp',
			method: 'GET',
			url: tUrl
		}, function(list) {
			//node.remove();
			drawBlocks(list);
		}
	);
}

function drawGt() {
	var tUrl = 'http://localhost/helloroles/server/resource/gt_blocks?key=df9795cc-d73a-48d1-82b9-e9e5b0b72d8a&url='+url;
	chrome.runtime.sendMessage(
		{
			action: 'xhttp',
			method: 'GET',
			url: tUrl
		}, function(list) {
			console.log(list);
			//node.remove();
			drawBlocks(list, true);
		}
	);
}

/** 
 * Switch on the extension and set what you need
 */
function on(id) {
	switch (id) {
		case 'showGt':
			drawGt();
			break;
		case 'on':
			chrome.storage.local.set({active: true}, function() {
				//console.log('Value is set to ' + true);
			});			
			active = true;
			break;
		case 'off':
			chrome.storage.local.set({active: false}, function() {
				active = false;
				document.body = firstDOM.cloneNode(true);
			});				

			break;
			 
	}
}

function isEmpty(obj) {
    for(var prop in obj) {
        if(obj.hasOwnProperty(prop))
            return false;
    }

    return true;
}

function orderObj(unordered) {
	let ordered = {};
	Object.keys(unordered).sort(function(a, b){
		if(a > b) { return -1; }
		if(a < b) { return 1; }
		return 0;
	}).forEach(function(key) {
	  ordered[key] = unordered[key];
	});
	return ordered;
}