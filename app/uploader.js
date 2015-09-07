/**
 * @fileoverview Upload image to the bucket.
 */


(function() {
    var PROJECT = 'mechanobio.info:api-project-811363880127';
    var clientId = '811363880127-k99foe5pasqmd9oa6sb592lqqpqqugua.apps.googleusercontent.com';
    var apiKey = 'AIzaSyCiXLTDz-qSBuVTo4WjOp2FSUOirTzsBkw';
    var scopes = 'https://www.googleapis.com/auth/devstorage.full_control';
    var API_VERSION = 'v1';
    var BUCKET = 'mbi-figure';
    var PREFIX = 'test/';

    function getUploadFileName(fn) {
        var m = location.pathname.match(/\/(\w+)\/$/);
        if (m) {
            return m[1] + '.jpg';
        } else {
            return prompt('Enter file name: ', fn);
        }
    }

    /**
     * Google Cloud Storage API request to insert an object into
     * your Google Cloud Storage bucket.
     */
    function insertObject(event) {
        try{
            var fileData = event.target.files[0];
        }
        catch(e) {
            filePicker.style.display = 'block';
            return;
        }
        if (/\.jpg$/.test(fileData.name)) {
            alert('Image file name must end with .jpg');
            return;
        }
        var file_name = getUploadFileName(fileData.name);
        var boundary = '-------314159265358979323846';
        var delimiter = "\r\n--" + boundary + "\r\n";
        var close_delim = "\r\n--" + boundary + "--";
        var reader = new FileReader();
        reader.readAsBinaryString(fileData);
        reader.onload = function(e) {
            var contentType = fileData.type || 'application/octet-stream';
            var metadata = {
                'name': PREFIX + fileData.name,
                'mimeType': contentType
            };
            var base64Data = btoa(reader.result);
            var multipartRequestBody =
                delimiter +
                'Content-Type: application/json\r\n\r\n' +
                JSON.stringify(metadata) +
                delimiter +
                'Content-Type: ' + contentType + '\r\n' +
                'Content-Transfer-Encoding: base64\r\n' +
                '\r\n' +
                base64Data +
                close_delim;
            //Note: gapi.client.storage.objects.insert() can only insert
            //small objects (under 64k) so to support larger file sizes
            //we're using the generic HTTP request method gapi.client.request()
            var request = gapi.client.request({
                'path': '/upload/storage/' + API_VERSION + '/b/' + BUCKET + '/o',
                'method': 'POST',
                'params': {'uploadType': 'multipart'},
                'headers': {
                    'Content-Type': 'multipart/mixed; boundary="' + boundary + '"'
                },
                'body': multipartRequestBody});

            try{
                //Execute the insert object request
                request.execute(function(resp) {
                    console.log(resp);
                });
                //Store the name of the inserted object
                object = fileData.name;
            }
            catch(e) {
                alert('An error has occurred: ' + e.message);
            }
        }
    }

    /**
     * Handle authorization.
     */
    function handleAuthResult(authResult) {
        var authorizeButton = document.getElementById('authorize-button');
        if (authResult && !authResult.error) {
            authorizeButton.style.visibility = 'hidden';
            initializeApi();
            filePicker.onchange = insertObject;
        } else {
            authorizeButton.style.visibility = '';
            authorizeButton.onclick = handleAuthClick;
        }
    }

    /**
     * Handle authorization click event.
     */
    function handleAuthClick(event) {
        gapi.auth.authorize({
            client_id: clientId,
            scope: scopes,
            immediate: false
        }, handleAuthResult);
        return false;
    }

    function checkAuth() {
        gapi.auth.authorize({
            client_id: clientId,
            scope: scopes,
            immediate: true
        }, handleAuthResult);
    }
    /**
     * Load the Google Cloud Storage API.
     */
    function initializeApi() {
        gapi.client.load('storage', API_VERSION);
    }

    window.addEventListener('load', function() {
        var root = document.body;
        var btn = document.createElement('button');
        btn.id = 'authorize-button';
        btn.style.visibility = 'hidden';
        btn.textContent = 'Authorize';
        root.appendChild(btn);
        gapi.client.setApiKey(apiKey);
        window.setTimeout(checkAuth, 1);
    }, false);
}());

