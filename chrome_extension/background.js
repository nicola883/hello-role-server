
chrome.runtime.onMessage.addListener(
    function(request, sender, sendResponse) {
		console.log(request);
        if (request.action == "xhttp") {
            var xhttp = new XMLHttpRequest();
            var method = request.method ? request.method.toUpperCase() : 'GET';
            xhttp.onload = function() {
				//return xhttp.responseText;
                sendResponse(JSON.parse(xhttp.responseText));
            };
            xhttp.onerror = function() {
                // Do whatever you want on error. Don't forget to invoke the
                // callback to clean up the communication port.
                sendResponse();
            };
            xhttp.open(method, request.url, true);
            if (method == 'POST') {
                xhttp.setRequestHeader('Content-Type', 'application/json');
			}
            xhttp.send(JSON.stringify(request.data));
            return true; // prevents the callback from being called too early on return
        }
    }
);

