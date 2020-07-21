# helloroles

The web service has APIs to create the Base Truth, retriving it, store the evaluations, and obtain statistics.
The extension for Chrome extracts WAI-ARIA roles from pages, retrieves the evaluations from the tool under exam and send them to the server. 

1. Creating the Ground Truth.
a. Open the candidate page for the ground truth 
b. Select "Send ARIA attributes" in the extension button

All WAI-ARIA roles and tags in the pages are stored on the server.

2. Evaluate the tool
a. Open the page <web service base url>/helloroles/client on the browser. A list with all the pages of the ground truth will appear. Only pages that satisfy the requisites will be shown.
b. Click on "Extract semantic blocks". Blocks extracted by the toll will be shown on the page and sent to the web service.

3. Asking the statistics
Run the query on the database helloroles
 
