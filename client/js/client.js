(function() {
    $(document).ready(function() {
        var log, serverUrl, socket;
        log = function(msg) {
            return $('#log').append(`${msg}<br />`);
        };
        serverUrl = 'ws://127.0.0.1:8000/demo';
        if (window.MozWebSocket) {
            socket = new MozWebSocket(serverUrl);
        } else if (window.WebSocket) {
            socket = new WebSocket(serverUrl);
        }
        socket.binaryType = 'blob';
        socket.onopen = function(msg) {
            return $('#status').removeClass().addClass('online').html('connected');
        };
        socket.onmessage = function(msg) {
            var response;
            response = JSON.parse(msg.data);
            log(`Action: ${response.action}`);
            return log(`Data: ${response.data}`);
        };
        socket.onclose = function(msg) {
            return $('#status').removeClass().addClass('offline').html('disconnected');
        };
        $('#status').click(function() {
            return socket.close();
        });
        $('#send').click(function() {
            var payload;
            payload = new Object();
            payload.action = $('#action').val();
            payload.data = $('#data').val();
            return socket.send(JSON.stringify(payload));
        });
        return $('#sendfile').click(function() {
            var data, payload;
            data = document.binaryFrame.file.files[0];
            if (data) {
                payload = new Object();
                payload.action = 'setFilename';
                payload.data = $('#file').val();
                socket.send(JSON.stringify(payload));
                socket.send(data);
            }
            return false;
        });
    });

}).call(this);