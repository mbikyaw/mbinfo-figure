/**
 * @fileoverview Upload image to the bucket.
 */


(function() {
    var PROJECT = 'mechanobio.info:api-project-811363880127';
    var clientId = '811363880127-k99foe5pasqmd9oa6sb592lqqpqqugua.apps.googleusercontent.com';
    var apiKey = 'AIzaSyCiXLTDz-qSBuVTo4WjOp2FSUOirTzsBkw';
    var scopes = 'https://www.googleapis.com/auth/devstorage.read_write';
    var API_VERSION = 'v1';
    var BUCKET = 'mbi-figure';
    var PREFIX = 'figure/';

    function getUploadFileName(fn) {
        var m = location.pathname.match(/\/([^\/]+)\/$/);
        if (m) {
            return m[1] + '.jpg';
        } else {
            var out = prompt('Enter file name: ', fn);
            if (!out) {
                throw new Error('Cancel');
            }
            return out;
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
        if (!/\.jpg$/.test(fileData.name)) {
            alert('Image file name must end with .jpg, but "' + fileData.name + '" found.');
            return;
        }
        var file_name = PREFIX + getUploadFileName(fileData.name);
        var message = document.getElementById('message');
        message.textContent = 'Reading ' + fileData.name;
        var boundary = '-------314159265358979323846';
        var delimiter = "\r\n--" + boundary + "\r\n";
        var close_delim = "\r\n--" + boundary + "--";
        var reader = new FileReader();
        reader.readAsBinaryString(fileData);
        message.textContent = 'Uploading to ' + file_name + ' ...';
        reader.onload = function(e) {
            var contentType = fileData.type || 'application/octet-stream';
            var metadata = {
                'name': file_name,
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
                    message.textContent = 'Uploaded to ' + resp.name;
                    var img = document.querySelector('.copyrighted-figure img');
                    if (img) {
                        img.src = '//' + BUCKET + '.storage.googleapis.com/' + resp.name;
                    }
                });
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
        var input = document.getElementById('filePicker');
        if (authResult && !authResult.error) {
            authorizeButton.style.display = 'none';
            input.style.display = '';
            initializeApi();
            var filePicker = document.getElementById('filePicker');
            filePicker.onchange = insertObject;
        } else {
            authorizeButton.style.display = '';
            input.style.display = 'none';
            authorizeButton.onclick = handleAuthClick;
        }
    }

    /**
     * Handle authorization click event.
     */
    function handleAuthClick(event) {
        event.preventDefault();
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
        gapi.client.setApiKey(apiKey);

        var root = document.getElementById('uploader-root') || document.body;
        var btn = document.createElement('a');
        btn.id = 'authorize-button';
        btn.style.display = 'none';
        btn.textContent = 'Update';
        btn.style.cursor = 'pointer';
        root.appendChild(btn);
        var input = document.createElement('input');
        input.type = 'file';
        input.id = 'filePicker';
        input.style.width = 'auto';
        input.style.display = 'none';
        input.setAttribute('accept', '.jpg');
        root.appendChild(input);
        var div = document.createElement('span');
        div.id = 'message';
        root.appendChild(div);

        window.setTimeout(checkAuth, 1);
    }, false);
}());

