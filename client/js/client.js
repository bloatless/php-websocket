(function() {
    document.addEventListener("DOMContentLoaded", function() {

        const elLog = document.getElementById('log');
        const elStatus = document.getElementById('status');
        const elSend = document.getElementById('send');
        const elAction = document.getElementById('action');
        const elData = document.getElementById('data');

        const log = (msg) => {
            return elLog.insertAdjacentHTML('afterbegin', `${msg}<br />`);
        };

        let socket = null;
        let serverUrl = 'ws://127.0.0.1:8000/demo';
        if (window.MozWebSocket) {
            socket = new MozWebSocket(serverUrl);
        } else if (window.WebSocket) {
            socket = new WebSocket(serverUrl);
        }
        socket.binaryType = 'blob';
        socket.onopen = (msg) => {
            elStatus.classList.remove('offline');
            elStatus.classList.add('online');
            return elStatus.innerText = 'connected';
        };

        socket.onmessage = (msg) => {
            let response = JSON.parse(msg.data);
            log(`Action: ${response.action}`);
            return log(`Data: ${response.data}`);
        };

        socket.onclose = (msg) => {
            elStatus.classList.remove('online');
            elStatus.classList.add('offline');
            return elStatus.innerText = 'disconnected';
        };

        elStatus.addEventListener('click', () => {
            return socket.close();
        });

        elSend.addEventListener('click', () => {
            let payload = {
                action: elAction.value,
                data: elData.value
            };
            return socket.send(JSON.stringify(payload));
        });

    });

}).call();